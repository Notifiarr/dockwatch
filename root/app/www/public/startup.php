<?php

/*
----------------------------------
 ------  Created: 020724   ------
 ------  Austin Best	   ------
----------------------------------
*/

if (!defined('ABSOLUTE_PATH')) {
	define('ABSOLUTE_PATH', __DIR__ . '/');
}

echo 'require_once ' . ABSOLUTE_PATH . 'loader.php' . "\n";
require_once ABSOLUTE_PATH . 'loader.php';

//-- SETTINGS
$settingsFile = getFile(SETTINGS_FILE);

//-- INITIALIZE THE NOTIFY CLASS
$notifications = new Notifications();

//-- INITIALIZE THE MAINTENANCE CLASS
$maintenance = new Maintenance();

logger(STARTUP_LOG, 'Container init (Start/Restart) ->');

$name = file_exists(TMP_PATH . 'restart.txt') || file_exists(TMP_PATH . 'update.txt') ? 'dockwatch-maintenance' : 'dockwatch';

//-- STARTUP NOTIFICATION
$notify['state']['changed'][] = ['container' => $name, 'previous' => '.....', 'current' => 'Started/Restarted'];
if ($settingsFile['notifications']['triggers']['stateChange']['platform']) {
	$payload = ['event' => 'state', 'changes' => $notify['state']['changed']];
	$notifications->notify($settingsFile['notifications']['triggers']['stateChange']['platform'], $payload);
	logger(STARTUP_LOG, 'Sending dockwatch started notification');
}

//-- MAINTENANCE CHECK
$maintenance->startup();

logger(STARTUP_LOG, 'Container init (Start/Restart) <-');
