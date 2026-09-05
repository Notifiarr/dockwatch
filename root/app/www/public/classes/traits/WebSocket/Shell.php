<?php

/*
----------------------------------
 ------  Created: 090426   ------
 ------  nzxl	           ------
----------------------------------
*/

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\Frame;
use React\EventLoop\Loop;

trait WebSocketShell
{
    protected function startContainerSession(ConnectionInterface $client, $containerId)
    {
        $sessionId = $client->resourceId;

        $this->container_sessions[$sessionId] = [
            'sock'          => null,
            'execId'        => null,
            'containerId'   => $containerId,
            'started'       => false,
            'closed'        => false,
            'timer'         => null,
            'pending_input' => '',
        ];

        $this->setupExec($client, $containerId);
    }

    protected function setupExec(ConnectionInterface $client, $containerId)
    {
        $sessionId = $client->resourceId;
        $session   = &$this->container_sessions[$sessionId];

        if (!$session || $session['closed']) {
            return;
        }

        //-- CHECK IF CONTAINER IS RUNNING
        $check = $this->dockerRequest('GET', '/containers/' . urlencode($containerId) . '/json');
        if ($check === null) {
            $this->failSession($client, "Unable to reach the docker daemon");
            return;
        }
        if ($check['status'] !== 200) {
            $this->failSession($client, "Container '$containerId' is not running or does not exist");
            return;
        }

        //-- DETECT A USABLE SHELL
        $shell = $this->detectShell($containerId);
        if ($shell === null) {
            $this->failSession($client, "No shell found in container '$containerId' (tried sh, bash, ash)");
            return;
        }

        //-- CREATE THE EXEC WITH A TTY
        $ttySize = $this->currentTerminalSize($client);
        $execId  = $this->dockerExecCreate($containerId, [$shell, '-i'], $ttySize);
        if ($execId === null) {
            $this->failSession($client, "Failed to create an exec session for container '$containerId'");
            return;
        }

        //-- START THE EXEC STREAM (REAL TTY)
        $start = $this->dockerExecStart($execId);
        if ($start === null) {
            $this->failSession($client, "Failed to start the container shell for '$containerId'");
            return;
        }

        $session['sock']    = $start['sock'];
        $session['execId']  = $execId;
        $session['started'] = true;

        $client->send(json_encode([
            'type'    => 'ready',
            'message' => 'READY!',
        ]));

        //-- RESET THE TERMINAL TO NORMAL CURSOR MODE (DECCKM OFF) SO APPLICATION-MODE
        //-- CURSOR KEYS (ESC O A/B/C/D) ARE NOT USED; BUSYBOX/SH LINE EDITORS ONLY
        //-- RECOGNIZE NORMAL-MODE KEYS (ESC [ A/B/C/D) FOR ARROW/NAVIGATION.
        $client->send(new Frame("\x1b[?1l", true, Frame::OP_BINARY));

        $this->startOutputLoop($client);
    }

    protected function detectShell($containerId)
    {
        //-- TRY TO READ FROM CACHE FIRST
        $cacheKey = sprintf(MEMCACHE_SHELL_PATH_KEY, $containerId);
        $cached   = $this->memcached->get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        //-- PREFER BASH FIRST AND FALL BACK TO SH/ASH FOR MINIMAL IMAGES
        $execId = $this->dockerExecCreate($containerId, ['sh', '-c', 'command -v bash || command -v sh || command -v ash || echo no_shell']);
        if ($execId === null) {
            return null;
        }

        $start = $this->dockerExecStart($execId);
        if ($start === null) {
            return null;
        }

        //-- READ THE ONE-SHOT OUTPUT (TIMEOUT + SIZE CAP KEEP A HUNG EXEC FROM FREEZING THE LOOP)
        stream_set_blocking($start['sock'], true);
        stream_set_timeout($start['sock'], 5);
        $output = '';
        while (!feof($start['sock'])) {
            $chunk = fread($start['sock'], 4096);
            $meta  = stream_get_meta_data($start['sock']);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $output .= $chunk;
            if (($meta['timed_out'] ?? false) || strlen($output) > 65536) {
                break;
            }
        }
        fclose($start['sock']);

        $shell = trim($output);
        if ($shell === '' || $shell === 'no_shell') {
            return null;
        }

        //-- CACHE THE RESULT FOR 10 MINUTES
        $this->memcached->set($cacheKey, $shell, MEMCACHE_SHELL_PATH_TIME);

        return $shell;
    }

    protected function currentTerminalSize(ConnectionInterface $client)
    {
        $session = $this->container_sessions[$client->resourceId] ?? null;
        if ($session && isset($session['cols'], $session['rows'])) {
            return ['cols' => $session['cols'], 'rows' => $session['rows']];
        }

        return null;
    }

    protected function failSession(ConnectionInterface $client, $message)
    {
        $client->send(json_encode([
            'type'    => 'error',
            'message' => $message,
        ]));

        $this->closeContainerSession($client);
    }

    protected function writeToContainer(ConnectionInterface $client, $data)
    {
        $sessionId = $client->resourceId;

        if (!isset($this->container_sessions[$sessionId]) || $this->container_sessions[$sessionId]['closed'] || !$this->container_sessions[$sessionId]['sock']) {
            return;
        }

        $this->container_sessions[$sessionId]['pending_input'] .= $data;

        $this->flushInput($client, $sessionId);
    }

    private function flushInput(ConnectionInterface $client, $sessionId)
    {
        if (!isset($this->container_sessions[$sessionId])) {
            return;
        }

        $pending = $this->container_sessions[$sessionId]['pending_input'] ?? '';
        $sock    = $this->container_sessions[$sessionId]['sock'] ?? null;

        if ($pending === '' || !is_resource($sock)) {
            return;
        }

        $written = fwrite($sock, $pending);
        if ($written === false) {
            logger(WEBSOCKET_LOG, 'fwrite to container tty failed', 'error');
            $this->closeContainerSession($client);
            return;
        }

        if ($written < strlen($pending)) {
            //-- SOCKET COULDN'T ACCEPT IT ALL YET - KEEP THE REST FOR THE NEXT TIMER TICK
            $this->container_sessions[$sessionId]['pending_input'] = substr($pending, $written);
        } else {
            $this->container_sessions[$sessionId]['pending_input'] = '';
        }

        if (!fflush($sock)) {
            logger(WEBSOCKET_LOG, 'fflush to container tty failed', 'error');
        }
    }

    protected function startOutputLoop(ConnectionInterface $client)
    {
        $sessionId = $client->resourceId;
        $loop      = Loop::get();

        $timer = $loop->addPeriodicTimer(0.005, function () use ($client, $sessionId) {
            $session = $this->container_sessions[$sessionId] ?? null;

            if (!$session || $session['closed'] || !$session['sock']) {
                return;
            }

            $sock = $session['sock'];

            //-- FLUSH ANY PENDING INPUT TO THE CONTAINER'S TTY
            if (!empty($session['pending_input'])) {
                $this->flushInput($client, $sessionId);
            }

            if (feof($sock)) {
                $err = stream_get_contents($sock);
                $client->send(json_encode([
                    'type'    => 'exit',
                    'code'    => 1,
                    'message' => 'Container shell exited' . ($err ? " ($err)" : ''),
                ]));
                $this->closeContainerSession($client);
                return;
            }

            if (is_resource($sock)) {
                $read   = [$sock];
                $write  = null;
                $except = null;

                if (stream_select($read, $write, $except, 0, 0) === 1) {
                    do {
                        $chunk = fread($sock, 4096);
                        if ($chunk === false || $chunk === '') {
                            break;
                        }
                        //-- FORWARD RAW TTY OUTPUT AS A BINARY FRAME
                        try {
                            $client->send(new Frame($chunk, true, Frame::OP_BINARY));
                        } catch (\Throwable $e) {
                            $this->closeContainerSession($client);
                            return;
                        }
                    } while (!$this->bufferUnderflow($sock));
                }
            }
        });

        if (isset($this->container_sessions[$sessionId])) {
            $this->container_sessions[$sessionId]['timer'] = $timer;
        }
    }

    private function bufferUnderflow($sock)
    {
        if (!is_resource($sock)) {
            return true;
        }

        $read   = [$sock];
        $write  = null;
        $except = null;
        return stream_select($read, $write, $except, 0, 0) !== 1;
    }

    protected function resizeTerminal(ConnectionInterface $client, $cols, $rows)
    {
        if (!isset($this->container_sessions[$client->resourceId])) {
            return;
        }
        $session = &$this->container_sessions[$client->resourceId];
        if ($session['closed'] || !$session['execId']) {
            return;
        }

        $cols = (int) $cols;
        $rows = (int) $rows;

        if ($cols < 1 || $rows < 1) {
            return;
        }

        //-- REMEMBER THE SIZE SO EXEC CREATE USES THE LATEST DIMENSIONS
        $session['cols'] = $cols;
        $session['rows'] = $rows;

        if ($session['execId']) {
            $this->dockerExecResize($session['execId'], $cols, $rows);
        }
    }

    protected function closeContainerSession(ConnectionInterface $client)
    {
        $sessionId = $client->resourceId;

        if (!isset($this->container_sessions[$sessionId])) {
            return;
        }

        $session = $this->container_sessions[$sessionId];

        //-- CANCEL THE STREAM TIMER
        if ($session['timer'] instanceof \React\EventLoop\TimerInterface) {
            Loop::get()->cancelTimer($session['timer']);
        }

        //-- CLOSE THE DOCKER SOCKET
        if (is_resource($session['sock'])) {
            fclose($session['sock']);
        }

        unset($this->container_sessions[$sessionId]);
    }
}
