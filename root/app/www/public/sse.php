<?php

/*
----------------------------------
 ------  Created: 051124   ------
 ------  Austin Best	   ------
----------------------------------
*/

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

require 'loader.php';

$sseFile = getServerFile('sse');
$sseFile = $sseFile['code'] != 200 ? [] : $sseFile['file'];

if ($settingsFile['global']['sseEnabled']) {
    //-- DONT SEND ALL THE DATA EVERY TIME, WASTE
    if (!$sseFile['pushed']) {
        $sseFile['pushed'] = time();
        setServerFile('sse', $sseFile);
    } else {
        $sseFile = [];
    }
} else {
    $sseFile = [];
}

echo 'data: ' . json_encode($sseFile) . "\n\n";
flush();