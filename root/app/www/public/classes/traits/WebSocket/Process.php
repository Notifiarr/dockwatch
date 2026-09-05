<?php

/*
----------------------------------
 ------  Created: 090426   ------
 ------  nzxl	           ------
----------------------------------
*/

use Ratchet\ConnectionInterface;
use React\EventLoop\Loop;

trait WebSocketProcess
{
    /**
     * Start an external process and stream its combined output to the client.
     *
     * State is kept in $this->channels[$resourceId]:
     *   ['closed'=>bool, 'proc'=>resource, 'pipes'=>array, 'timer'=>TimerInterface, 'buffer'=>string, 'onComplete'=>callable|null]
     *
     * Output is delivered as text frames (stdout/stderr merged). A final
     * ['type'=>'done','code'=>int,'message'=>string] frame is sent on exit.
     * When $onComplete is provided it is called with (string $buffer, int $exitCode)
     * just before the 'done' frame is sent.
     */
    protected function startProcessStream(ConnectionInterface $client, $cmd, callable $onComplete = null)
    {
        $sessionId = $client->resourceId;

        if (isset($this->channels[$sessionId]['proc']) && is_resource($this->channels[$sessionId]['proc'])) {
            $this->sendText($client, ['type' => 'error', 'message' => 'A process is already running on this connection']);
            return;
        }

        $descriptors = [
            0 => ['pipe', 'r'], //-- STDIN (unused - kept open)
            1 => ['pipe', 'w'], //-- STDOUT
            2 => ['pipe', 'w'], //-- STDERR
        ];

        $process = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            $this->sendText($client, ['type' => 'error', 'message' => 'Failed to start process']);
            $this->closeChannel($client);
            return;
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        fclose($pipes[0]); //-- CLOSE STDIN - WE ONLY READ OUTPUT

        $this->channels[$sessionId]['proc']       = $process;
        $this->channels[$sessionId]['pipes']      = [$pipes[1], $pipes[2]];
        $this->channels[$sessionId]['buffer']     = '';
        $this->channels[$sessionId]['onComplete'] = $onComplete;

        $loop                                = Loop::get();
        $timer                               = $loop->addPeriodicTimer(0.05, function () use ($client, $sessionId) {
            $this->streamProcessOutput($client, $sessionId);
        });
        $this->channels[$sessionId]['timer'] = $timer;
    }

    /**
     * Loop tick: drain stdout+stderr, forward as text frames, detect process exit.
     */
    protected function streamProcessOutput(ConnectionInterface $client, $sessionId)
    {
        $channel = $this->channels[$sessionId] ?? null;

        if (!$channel || $channel['closed'] || !is_resource($channel['proc'])) {
            return;
        }

        $chunks = '';
        foreach ($channel['pipes'] as $pipe) {
            if (!is_resource($pipe)) {
                continue;
            }
            while (($chunk = fread($pipe, 4096)) !== false && $chunk !== '') {
                $chunks .= $chunk;
            }
        }

        if ($chunks !== '') {
            $this->channels[$sessionId]['buffer'] .= $chunks;
            $client->send($chunks);
        }

        $status = proc_get_status($channel['proc']);
        if ($status && !$status['running']) {
            $exitCode = is_int($status['exitcode']) ? $status['exitcode'] : 0;

            if (!empty($channel['onComplete']) && is_callable($channel['onComplete'])) {
                call_user_func($channel['onComplete'], $this->channels[$sessionId]['buffer'] ?? '', $exitCode);
            }

            $client->send(json_encode([
                'type'    => 'done',
                'code'    => $exitCode,
                'message' => $exitCode === 0 ? 'complete' : 'failed',
            ]));
            $this->closeChannel($client);
        }
    }

    /**
     * Register/initialize a non-shell channel (compose, ...). No process starts here.
     */
    protected function registerChannel(ConnectionInterface $client, $type)
    {
        $this->channels[$client->resourceId] = [
            'type'       => $type,
            'closed'     => false,
            'proc'       => null,
            'pipes'      => [],
            'timer'      => null,
            'buffer'     => '',
            'onComplete' => null,
        ];
    }

    /**
     * Tear down any running process/timer and remove the channel entry.
     */
    protected function closeChannel(ConnectionInterface $client)
    {
        $sessionId = $client->resourceId;

        if (!isset($this->channels[$sessionId])) {
            return;
        }

        $channel = $this->channels[$sessionId];

        if (!empty($channel['timer']) && $channel['timer'] instanceof \React\EventLoop\TimerInterface) {

            Loop::get()->cancelTimer($channel['timer']);
        }

        foreach ($channel['pipes'] as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        if (is_resource($channel['proc'])) {
            proc_terminate($channel['proc']);
            proc_close($channel['proc']);
        }

        $this->channels[$sessionId]['closed'] = true;
        unset($this->channels[$sessionId]);
    }
}
