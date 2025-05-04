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

$database       = new Database();
$settingsTable  = apiRequestLocal('database/settings');
$sseFile        = getFile(SSE_FILE);

if ($settingsTable['sseEnabled']) {
    //-- DONT SEND ALL THE DATA EVERY TIME, WASTE
    if (!$sseFile['pushed']) {
        $sseFile['pushed'] = time();
        setFile(SSE_FILE, $sseFile);
    } else {
        $sseFile = [];
    }
} else {
    $sseFile = [];
}

echo 'data: ' . json_encode($sseFile) . "\n\n";
flush();
