<?php

/*
----------------------------------
 ------  Created: 020724   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('IS_STARTUP', true);

if (!defined('ABSOLUTE_PATH')) {
    define('ABSOLUTE_PATH', __DIR__ . '/');
}

echo 'require_once ' . ABSOLUTE_PATH . 'loader.php' . "\n";
require_once ABSOLUTE_PATH . 'loader.php';

if (!IS_MAINTENANCE) {
    //-- INITIALIZE MEMCACHE
    /** @disregard */
    $memcache ??= new Memcached();
    $memcache->addServer(MEMCACHE_HOST, MEMCACHE_PORT);

    //-- INITIALIZE THE DATABASE CLASS
    $database ??= new Database();

    //-- INITIALIZE THE NOTIFY CLASS
    $notifications ??= new Notifications();
}

//-- INITIALIZE THE MAINTENANCE CLASS
$maintenance = new Maintenance();

if (!IS_MAINTENANCE) {
    //-- INITIALIZE SECURITY
    $security ??= new Security();
}

logger(STARTUP_LOG, 'Container init (Start/Restart) ->', 'shell');

$name = IS_MAINTENANCE ? 'dockwatch-maintenance' : 'dockwatch';

//-- STARTUP TELEMETRY CHECK
if ($name == 'dockwatch') {
    telemetry(true);
}

//-- STARTUP NOTIFICATION
if (!IS_MAINTENANCE) {
    $notify['state']['changed'][] = ['container' => $name, 'previous' => '.....', 'current' => 'Started/Restarted'];

    if (apiRequest('database/notification/trigger/enabled', ['trigger' => 'stateChange'])['result']) {
        $payload = ['event' => 'state', 'changes' => $notify['state']['changed']];
        $notifications->notify(0, 'stateChange', $payload);

        logger(STARTUP_LOG, 'Sending ' . $name . ' started notification', 'shell');
    } else {
        logger(STARTUP_LOG, 'Skipping ' . $name . ' started notification, no senders found with stateChange enabled', 'warn');
    }
}

//-- WEBSOCKET SERVER
if (!IS_MAINTENANCE) {
    $cmd = '/usr/bin/php ' . ABSOLUTE_PATH . 'websocket.php > /dev/null 2>&1 &';
    exec($cmd);
}

//-- DOWNLOAD SCANNERS
if (!IS_MAINTENANCE && apiRequestLocal('database/settings')['securityEnabled'] && $name == 'dockwatch') {
    file_put_contents(DOWNLOAD_SCANNERS_FILE, '');
} elseif (is_file(DOWNLOAD_SCANNERS_FILE)) {
    unlink(DOWNLOAD_SCANNERS_FILE);
}

//-- MAINTENANCE CHECK
$maintenance->startup();

logger(STARTUP_LOG, 'Container init (Start/Restart) <-', 'shell');
