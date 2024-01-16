<?php

if (!defined('ABSOLUTE_PATH')) {
	define('ABSOLUTE_PATH', __DIR__ . '/');
}

require_once ABSOLUTE_PATH . 'loader.php';

//-- SETTINGS
$settingsFile = getServerFile('settings');
if ($settingsFile['code'] != 200) {
	$apiError = $settingsFile['file'];
}
$settingsFile = $settingsFile['file'];

if (!$settingsFile['global']['socketEnabled']) {
	echo 'Socket usage is disabled in the settings';
	exit();
}

if ($_SESSION['serverIndex'] != 0) {
	echo 'Real time updates do not work on remote servers';
	exit();
}

$null 			= null;
$socket 		= new DockwatchSocket();
$socketResource = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socketResource, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socketResource, 0, $socketPort);
socket_listen($socketResource);

$clientSocketArray = [$socketResource];
while (true) {
	$newSocketArray = $clientSocketArray;
	socket_select($newSocketArray, $null, $null, 0, 10);

	if (in_array($socketResource, $newSocketArray)) {
		$newSocket = socket_accept($socketResource);
		$clientSocketArray[] = $newSocket;
		
		$header = socket_read($newSocket, 1024);
		$socket->doHandshake($header, $newSocket, 'localhost', $socketPort);
		
		socket_getpeername($newSocket, $client_ip_address);
		$connectionACK = $socket->newConnectionACK($client_ip_address);
		
		$socket->send($connectionACK);
		
		$newSocketIndex = array_search($socketResource, $newSocketArray);
		unset($newSocketArray[$newSocketIndex]);
	}

	foreach ($newSocketArray as $newSocketArrayResource) {	
		while (socket_recv($newSocketArrayResource, $socketData, 1024, 0) >= 1) {
			$socketMessage 	= $socket->unseal($socketData);
			$messageObj 	= json_decode($socketMessage);
			
			$socketMessage = $socket->createSocketMessage($messageObj->type, $messageObj->message);
			$socket->send($socketMessage);
			break 2;
		}

		$socketData = @socket_read($newSocketArrayResource, 1024, PHP_NORMAL_READ);
		if ($socketData === false) { 
			socket_getpeername($newSocketArrayResource, $client_ip_address);
			$connectionACK = $socket->connectionDisconnectACK($client_ip_address);
			$socket->send($connectionACK);
			$newSocketIndex = array_search($newSocketArrayResource, $clientSocketArray);
			unset($clientSocketArray[$newSocketIndex]);			
		}
	}
}

socket_close($socketResource);
