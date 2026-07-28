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

    $version    = gitVersion();
    $branch     = gitBranch();
    $image_hash = getDockwatchContainerHash();

    $q   = "SELECT * FROM `" . VERSION_TABLE . "` LIMIT 1";
    $res = $database->mysqli_query($q);
    $row = $database->mysqli_fetchAssoc($res);

    if (empty($row)) {
        $q = "INSERT INTO " . VERSION_TABLE . "
                      (`version`, `branch`, `image_hash`, `updated_at`)
                      VALUES
                      ('" . $database->prepare($version) . "', '" . $database->prepare($branch) . "', '" . $database->prepare($image_hash) . "', '" . time() . "')";
        $database->mysqli_query($q);
    } else {
        if ($row['version'] !== $version || $row['branch'] !== $branch || $row['image_hash'] !== $image_hash) {
            echo "* Updated " . $name . " from " . $row['version'] . " [" . $row['branch'] . "]" . " → " . $version . " [" . $branch . "]" . " *\n";

            $q = "UPDATE " . VERSION_TABLE . "
                          SET version = '" . $database->prepare($version) . "', branch = '" . $database->prepare($branch) . "', image_hash = '" . $database->prepare($image_hash) . "', updated_at = '" . time() . "'
                          WHERE id = '" . $database->prepare($row['id']) . "'";
            $database->mysqli_query($q);

            if (apiRequest('database/notification/trigger/enabled', ['trigger' => 'updated'])['result']) {
                $payload = [
                    'event'   => 'updates',
                    'updated' => [
                        [
                            'container' => getDockwatchContainerName(),
                            'image'     => str_replace(':main', '', APP_IMAGE),
                            'pre'       => ['digest' => $row['image_hash'], 'version' => $row['version'] . ' [' . $row['branch'] . ']'],
                            'post'      => ['digest' => $image_hash, 'version' => $version . ' [' . $branch . ']']
                        ]
                    ]
                ];
                $notifications->notify(0, 'updated', $payload);
            }
        }
    }
}

//-- WEBSOCKET SERVER
if (!IS_MAINTENANCE) {
    logger(WEBSOCKET_LOG, 'Starting websocket server');
    $cmd = '/usr/bin/php ' . ABSOLUTE_PATH . 'websocket.php > /dev/null 2>&1 &';

    logger(WEBSOCKET_LOG, 'Websocket command: ' . $cmd);
    logger(WEBSOCKET_LOG, 'Websocket response:'); //-- websocket.php WILL LOG TO THE FILE FROM HERE
    exec($cmd);
}

//-- DOWNLOAD SCANNERS
if (!IS_MAINTENANCE && apiRequestLocal('database/settings')['securityEnabled'] && $name == 'dockwatch') {
    setFile(DOWNLOAD_SCANNERS_FILE, ['downloaded' => date('c')]);
} elseif (is_file(DOWNLOAD_SCANNERS_FILE)) {
    deleteFile(DOWNLOAD_SCANNERS_FILE);
}

//-- MAINTENANCE CHECK
$maintenance->startup();

logger(STARTUP_LOG, 'Container init (Start/Restart) <-', 'shell');
