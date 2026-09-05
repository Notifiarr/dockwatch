<?php

/*
----------------------------------
 ------  Created: 090426   ------
 ------  nzxl	           ------
----------------------------------
*/

use Ratchet\ConnectionInterface;

trait WebSocketServer
{
    public function getSubProtocols()
    {
        return [self::SUBPROTOCOL];
    }

    public function startup($port = APP_WEBSOCKET_PORT)
    {
        //-- DON'T ALLOW TO BIND ON THOSE PORTS
        if (str_equals_any($port, [80, 443])) {
            $port = APP_WEBSOCKET_PORT;
        }

        //-- WRITE PID FILE
        $this->writePidFile();

        $wsInner   = new \Ratchet\WebSocket\WsServer($this);
        $httpInner = new \Ratchet\Http\HttpServer($wsInner);

        $server = \Ratchet\Server\IoServer::factory($httpInner, $port, '0.0.0.0');
        $server->run();

        logger(WEBSOCKET_LOG, 'WebSocket Server started on 0.0.0.0:' . $port);
    }

    private function writePidFile()
    {
        if (file_put_contents(self::PID_FILE, (string) getmypid()) === false) {
            logger(WEBSOCKET_LOG, 'Failed to write pid file ' . self::PID_FILE, 'error');
        }
        if (is_file(self::PID_FILE)) {
            chmod(self::PID_FILE, 0644);
        }

        register_shutdown_function(function () {
            if (is_file(self::PID_FILE)) {
                unlink(self::PID_FILE);
            }
        });
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $queryParams);

        $type = $queryParams['type'] ?? WebSocketActions::TYPE_SHELL;

        //-- AUTH TOKEN IS DELIVERED VIA `Sec-WebSocket-Protocol`
        $token = $this->extractTokenFromHandshake($conn->httpRequest);

        if ($token === null) {
            logger(WEBSOCKET_LOG, 'Connection attempt missing auth token (' . $conn->resourceId . ')', 'warn');
            $request = getWebSocketIntrusionRequest($conn, $queryParams);
            recordIntrusion('websocket_missing_token', array_merge($request, [
                'type' => $type,
            ]));
            $conn->close();
            return;
        }

        switch ($type) {
            case WebSocketActions::TYPE_COMPOSE:
                //-- COMPOSE CHANNEL: NO CONTAINER SHELL SESSION, JUST AUTHORIZE + REGISTER.
                if (!$this->verifyToken(WebSocketActions::TYPE_COMPOSE, 'action', $token)) {
                    logger(WEBSOCKET_LOG, 'Unauthorized compose connection attempt (' . $conn->resourceId . ')', 'error');
                    $request = getWebSocketIntrusionRequest($conn, $queryParams);
                    recordIntrusion('websocket_invalid_token', array_merge($request, [
                        'type' => $type,
                    ]));
                    $conn->close();
                    return;
                }
                //-- SINGLE-USE: INVALIDATE IMMEDIATELY
                $this->revokeToken(WebSocketActions::TYPE_COMPOSE, 'action', $token);

                $this->clients->attach($conn);
                logger(WEBSOCKET_LOG, 'New compose connection authorized (' . $conn->resourceId . ')');

                $this->registerChannel($conn, $type);
                break;
            case WebSocketActions::TYPE_SHELL:
                //-- SHELL CHANNEL: REQUIRES A CONTAINER IDENTIFIER AND OPENS A CONTAINER SHELL.
                $container = ltrim($queryParams['container'] ?? '', '/');

                if (empty($container)) {
                    logger(WEBSOCKET_LOG, 'Shell connection attempt missing container (' . $conn->resourceId . ')', 'warn');
                    $request = getWebSocketIntrusionRequest($conn, $queryParams);
                    recordIntrusion('websocket_missing_params', array_merge($request, [
                        'container' => $container,
                    ]));
                    $conn->close();
                    return;
                }

                if (!$this->verifyToken(WebSocketActions::TYPE_SHELL, $container, $token)) {
                    logger(WEBSOCKET_LOG, 'Unauthorized shell connection attempt (' . $conn->resourceId . ')', 'error');
                    $request = getWebSocketIntrusionRequest($conn, $queryParams);
                    recordIntrusion('websocket_invalid_token', array_merge($request, [
                        'container' => $container,
                    ]));
                    $conn->close();
                    return;
                }

                //-- SINGLE-USE: INVALIDATE IMMEDIATELY
                $this->revokeToken(WebSocketActions::TYPE_SHELL, $container, $token);

                $this->clients->attach($conn);
                logger(WEBSOCKET_LOG, 'New shell connection authorized (' . $conn->resourceId . ')');

                $this->startContainerSession($conn, $container);
                break;
            //-- ADD MORE CHANNELS IF NEEDED
        }
    }


    public function onClose(ConnectionInterface $conn)
    {
        $this->teardownChannel($conn);
        $this->clients->detach($conn);
        logger(WEBSOCKET_LOG, 'Connection ' . $conn->resourceId . ' has disconnected');
    }


    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        logger(WEBSOCKET_LOG, 'Connection error occurred ' . $e->getMessage(), 'error');
        $this->teardownChannel($conn);
        $conn->close();
    }

    private function sendText(ConnectionInterface $conn, array $payload)
    {
        $conn->send(json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    protected function teardownChannel(ConnectionInterface $conn)
    {
        $this->closeContainerSession($conn);
        $this->closeChannel($conn);
    }
}
