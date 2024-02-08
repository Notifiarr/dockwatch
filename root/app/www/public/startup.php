<?php

if (!defined('ABSOLUTE_PATH')) {
	define('ABSOLUTE_PATH', __DIR__ . '/');
}

echo 'require_once ' . ABSOLUTE_PATH . 'loader.php' . "\n";
require_once ABSOLUTE_PATH . 'loader.php';

//-- SETTINGS
$settingsFile = getFile(SETTINGS_FILE);

//-- INITIALIZE THE NOTIFY CLASS
$notifications = new Notifications();

echo 'Container init (Start/Restart)' . "\n";
logger(SYSTEM_LOG, 'Container init (Start/Restart)');

$notify['state']['changed'][] = ['container' => $currentState['Names'], 'previous' => '.....', 'current' => 'Started/Restarted'];

echo 'Checking if state change notifications are enabled' . "\n";

if ($settingsFile['notifications']['triggers']['stateChange']['platform']) {
	echo 'Sending dockwatch started notification' . "\n";

	$payload = ['event' => 'state', 'changes' => $notify['state']['changed']];
	$notifications->notify($settingsFile['notifications']['triggers']['stateChange']['platform'], $payload);
	logger(SYSTEM_LOG, 'Sending dockwatch started notification');
}
