<?php

/*
----------------------------------
 ------  Created: 090426   ------
 ------  nzxl	             ------
----------------------------------
*/

//-- BRING IN THE EXTRAS
loadClassExtras('WebSocket');

use Ratchet\WebSocket\MessageComponentInterface;
use Ratchet\WebSocket\WsServerInterface;

class WebSocket implements MessageComponentInterface, WsServerInterface
{
    /** Sub-protocol used to negotiate the websocket handshake */
    public const SUBPROTOCOL = 'dockwatch-ws';

    private const PID_FILE = '/run/dockwatch/websocket.pid';

    use WebSocketAuth;
    use WebSocketDockerSocket;
    use WebSocketShell;
    use WebSocketServer;
    use WebSocketMessages;
    use WebSocketProcess;
    use WebSocketCompose;

    /** @var \SplObjectStorage */
    protected $clients;

    /** @var array resourceId => session */
    protected $container_sessions;

    /** @var array resourceId => channel (compose/process streams) */
    protected $channels;

    /** @var \Memcached */
    protected $memcached;

    public function __construct()
    {
        logger(WEBSOCKET_LOG, 'websocket ->');
        $this->clients            = new \SplObjectStorage;
        $this->container_sessions = [];
        $this->channels           = [];

        $this->memcached = new Memcached();
        $this->memcached->addServer(MEMCACHE_HOST, MEMCACHE_PORT);
    }
}
