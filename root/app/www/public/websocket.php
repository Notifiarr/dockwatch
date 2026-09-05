<?php

/*
----------------------------------
 ------  Created: 040625   ------
 ------  nzxl	           ------
----------------------------------
*/

if (!defined('ABSOLUTE_PATH')) {
    define('ABSOLUTE_PATH', __DIR__ . '/');
}
require_once ABSOLUTE_PATH . 'loader.php';

if (IS_MAINTENANCE) {
    exit(0);
}

require __DIR__ . '/../vendor/autoload.php';

//-- EXECUTION TIME
set_time_limit(0);
ob_implicit_flush();

//-- BUILD THE WEBSOCKET CLASS + TRAITS
require_once ABSOLUTE_PATH . 'classes/WebSocket.php';

$websocket = new WebSocket();
$websocket->startup($settingsTable['websocketPort'] ?: APP_WEBSOCKET_PORT);
