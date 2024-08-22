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

//-- INITIALIZE THE NOTIFY CLASS
$database = $database ?? new Database();

//-- INITIALIZE THE NOTIFY CLASS
$notifications = $notifications ?? new Notifications();

//-- INITIALIZE THE MAINTENANCE CLASS
$maintenance = $maintenance ?? new Maintenance();

logger(STARTUP_LOG, 'Container init (Start/Restart) ->');

$name = file_exists(TMP_PATH . 'restart.txt') || file_exists(TMP_PATH . 'update.txt') ? 'dockwatch-maintenance' : 'dockwatch';

//-- STARTUP NOTIFICATION
$notify['state']['changed'][] = ['container' => $name, 'previous' => '.....', 'current' => 'Started/Restarted'];

if (apiRequest('database-isNotificationTriggerEnabled', ['trigger' => 'stateChange'])['result']) {
	$payload = ['event' => 'state', 'changes' => $notify['state']['changed']];
	$notifications->notify(0, 'stateChange', $payload);

	logger(STARTUP_LOG, 'Sending ' . $name . ' started notification');
} else {
	logger(STARTUP_LOG, 'Skipping ' . $name . ' started notification, no senders found with stateChange enabled');
}

//-- MAINTENANCE CHECK
$maintenance->startup();

logger(STARTUP_LOG, 'Container init (Start/Restart) <-');
