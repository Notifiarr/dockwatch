<?php
/*
----------------------------------
 ------  Created: 040625   ------
 ------  nzxl         	   ------
----------------------------------
*/

if (!defined('ABSOLUTE_PATH')) {
    define('ABSOLUTE_PATH', __DIR__ . '/');
}
require_once ABSOLUTE_PATH . 'loader.php';

require __DIR__ . '/../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\Loop;

//-- EXECUTION TIME
set_time_limit(0);
ob_implicit_flush();

class WebSocket implements MessageComponentInterface
{
    protected $clients;
    protected $container_sessions;
    protected $memcached;

    public function __construct()
    {
        logger(WEBSOCKET_LOG, 'websocket ->');
        $this->clients = new \SplObjectStorage;
        $this->container_sessions = [];
        $this->memcached = new Memcached();
        $this->memcached->addServer(MEMCACHE_HOST, MEMCACHE_PORT);
    }

    public function startup($port = APP_WEBSOCKET_PORT)
    {
        //-- DON'T ALLOW TO BIND ON THOSE PORTS
        if (str_equals_any($port, [80, 443])) {
            $port = APP_WEBSOCKET_PORT;
        }

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    $this
                )
            ),
            $port,
            '0.0.0.0'
        );
        logger(WEBSOCKET_LOG, 'WebSocket Server started on 0.0.0.0:' . $port);
        $server->run();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $queryParams);

        if (!isset($queryParams['token']) || !isset($queryParams['container'])) {
            logger(WEBSOCKET_LOG, 'Connection attempt missing params (' . $conn->resourceId . ')', 'warn');
            $conn->close();
            return;
        }

        $container  = $queryParams['container'];
        $key        = sprintf(MEMCACHE_SHELL_TOKEN_KEY, $container);
        $token      = $this->memcached->get($key);

        if ($queryParams['token'] !== $token) {
            logger(WEBSOCKET_LOG, 'Unauthorized connection attempt (' . $conn->resourceId . ')', 'error');
            $conn->close();
            return;
        }

        $this->clients->attach($conn);
        $this->startContainerSession($conn, $container);
        logger(WEBSOCKET_LOG, 'New connection authorized (' . $conn->resourceId . ')');
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        if (!$data) {
            $from->send(json_encode(['error' => 'Invalid JSON message']));
            return;
        }

        logger(WEBSOCKET_LOG, 'Received message: ' . substr($msg, 0, 100) . ')');

        $session = &$this->container_sessions[$from->resourceId] ?? null;
        if (!$session) {
            $from->send(json_encode(['error' => 'Not connected to any container']));
            return;
        }

        switch ($data['action'] ?? '') {
            case 'command':
                $this->sendCommand($from, $data['command']);
                break;
            case 'resize':
                $this->resizeTerminal($from, $data['cols'], $data['rows']);
                break;
            case 'get_pwd':
                $this->sendCommand($from, "echo \$PWD\n");
                break;
            default:
                $from->send(json_encode(['error' => 'Unknown action']));
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->closeContainerSession($conn);
        $this->clients->detach($conn);
        logger(WEBSOCKET_LOG, 'Connection ' . $conn->resourceId . ' has disconnected');
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        logger(WEBSOCKET_LOG, 'Connection error occurred ' . $e->getMessage(), 'error');
        $this->closeContainerSession($conn);
        $conn->close();
    }

    //-- START CONTAINER SESSION WITH EXPECT
    protected function startContainerSession(ConnectionInterface $client, $containerId)
    {
        //-- CHECK IF CONTAINER IS RUNNING
        $checkCmd = "docker ps --filter name=" . escapeshellarg($containerId) . " --format '{{.Names}}' 2>&1";
        $containerCheck = trim(shell_exec($checkCmd));
        if (empty($containerCheck)) {
            $client->send(json_encode(['error' => "Container '$containerId' is not running or does not exist"]));
            $client->close();
            return;
        }

        //-- DETECT AVAILABLE SHELL
        $shellCheckCmd = "docker exec " . escapeshellarg($containerId) . " sh -c 'command -v sh || command -v bash || command -v ash || echo no_shell'";
        $shellCheck = trim(shell_exec($shellCheckCmd));
        $shell = '/bin/sh';
        if ($shellCheck === 'no_shell') {
            $client->send(json_encode(['error' => "No shell found in container '$containerId' (tried sh, bash, ash)"]));
            $client->close();
            return;
        } else if ($shellCheck !== '' && $shellCheck !== '/bin/sh') {
            $shell = $shellCheck;
        }

        //-- CHECK IF EXPECT IS AVAILABLE
        $expectCheck = shell_exec('which expect 2>/dev/null');
        if (empty($expectCheck)) {
            $client->send(json_encode(['error' => "Expect binary not found on server"]));
            $client->close();
            return;
        }

        //-- EXPECT SCRIPT WITH FULL INTERACTION
        $expectScript = sprintf(
            "expect -c 'set timeout -1; log_user 0; spawn -noecho docker exec -it %s %s; send \"export TERM=xterm\"; interact;'",
            escapeshellarg($containerId),
            escapeshellarg($shell),
            escapeshellarg($containerId)
        );
        logger(WEBSOCKET_LOG, "Executing expect command: $expectScript");

        $descriptors = [
            0 => ['pipe', 'r'], //-- STDIN
            1 => ['pipe', 'w'], //-- STDOUT
            2 => ['pipe', 'w']  //-- STDERR
        ];

        $process = proc_open($expectScript, $descriptors, $pipes);
        if (!is_resource($process)) {
            $client->send(json_encode(['error' => "Failed to start expect process for container '$containerId'"]));
            $client->close();
            return;
        }

        //-- SET NON-BLOCKING AND BUFFERING
        foreach ($pipes as $pipe) {
            stream_set_blocking($pipe, false);
            stream_set_write_buffer($pipe, 0); //-- REDUCE BUFFERING
        }

        $this->container_sessions[$client->resourceId] = [
            'process' => $process,
            'pipes' => $pipes,
            'containerId' => $containerId,
            'buffer' => '',
            'ready' => false
        ];

        $client->send(json_encode(['success' => true, 'message' => "Connected to container $containerId"]));

        //-- DELAY TO ENSURE SHELL IS READY
        $loop = Loop::get();
        $loop->addTimer(0.5, function () use ($client, $containerId) {
            if (isset($this->container_sessions[$client->resourceId])) {
                $this->container_sessions[$client->resourceId]['ready'] = true;

                fwrite($this->container_sessions[$client->resourceId]['pipes'][0], "\n");
                fflush($this->container_sessions[$client->resourceId]['pipes'][0]);

                $client->send(json_encode(['type' => 'ready', 'data' => 'READY!']));
            }
        });

        $this->startOutputLoop($client);
    }

    //-- SEND COMMAND
    protected function sendCommand(ConnectionInterface $client, $command)
    {
        $session = &$this->container_sessions[$client->resourceId];
        if (!$session['ready']) {
            return;
        }

        $pipes = $session['pipes'];

        $command = str_replace(
            ["\x7f", "\r", "\t", "\x03", "\x04"],
            ["\x08", "\n", "\t", "\x03", "\x04"],
            $command
        );

        $status = proc_get_status($session['process']);
        if (!$status['running']) {
            $client->send(json_encode(['type' => 'exit', 'code' => $status['exitcode'] ?? 1]));
            $this->closeContainerSession($client);
            return;
        }

        fwrite($pipes[0], $command);
        fflush($pipes[0]);
    }

    //-- OUTPUT LOOP
    protected function startOutputLoop(ConnectionInterface $client)
    {
        $session = &$this->container_sessions[$client->resourceId];
        $loop = Loop::get();

        $loop->addPeriodicTimer(0.005, function () use ($client, &$session) { //-- FASTER POLLING
            if (!isset($this->container_sessions[$client->resourceId])) {
                return;
            }

            $process = $session['process'];
            $pipes = $session['pipes'];

            $status = proc_get_status($process);
            if (!$status['running']) {
                $errorOutput = stream_get_contents($pipes[2], -1, 0);
                $client->send(json_encode([
                    'type' => 'exit',
                    'code' => $status['exitcode'],
                    'message' => "Container shell exited with code {$status['exitcode']}" . ($errorOutput ? " ($errorOutput)" : "")
                ]));
                $this->closeContainerSession($client);
                return;
            }

            $output = '';
            while ($data = fread($pipes[1], 4096)) { //-- READ IN CHUNKS
                $output .= $data;
            }
            $error = '';
            while ($data = fread($pipes[2], 4096)) {
                $error .= $data;
            }

            if ($output !== '' && $session['ready']) {
                $session['buffer'] .= $output;
                $client->send(json_encode([
                    'type' => 'stdout',
                    'data' => base64_encode($session['buffer'])
                ]));
                $session['buffer'] = '';
            }

            if ($error !== '' && $session['ready']) {
                $client->send(json_encode([
                    'type' => 'stderr',
                    'data' => base64_encode($error)
                ]));
            }
        });
    }

    //-- RESIZE TERMINAL
    protected function resizeTerminal(ConnectionInterface $client, $cols, $rows)
    {
        $session = &$this->container_sessions[$client->resourceId] ?? null;
        if (!$session || !$cols || !$rows || !$session['ready']) {
            return;
        }

        $cols = (int)$cols;
        $rows = (int)$rows;
        $resizeCmd = "stty cols $cols rows $rows\r";
        fwrite($session['pipes'][0], $resizeCmd);
        fflush($session['pipes'][0]);
    }

    //-- CLOSE SESSION
    protected function closeContainerSession(ConnectionInterface $client)
    {
        if (!isset($this->container_sessions[$client->resourceId])) {
            return;
        }

        $session = $this->container_sessions[$client->resourceId];

        foreach ($session['pipes'] as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        proc_terminate($session['process'], 9);
        proc_close($session['process']);

        unset($this->container_sessions[$client->resourceId]);
    }
}

$websocket = new WebSocket();
$websocket->startup($settingsTable['websocketPort'] ?: APP_WEBSOCKET_PORT);