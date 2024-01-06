<?php

/*
----------------------------------
 ------  Created: 010624   ------
 ------  Austin Best	   ------
----------------------------------
*/

header('Content-Type: text/event-stream');
header('Cache-Control: no-store, no-cache');

require 'loader.php';

session_write_close();

$sse = [];

$processList = apiRequest('dockerProcessList', ['format' => true]);
$processList = json_decode($processList['response']['docker'], true);

foreach ($processList as $process) {
    $nameHash = md5($process['Names']);
    $sse[] = ['hash' => $nameHash, 'row' => renderContainerRow($nameHash, 'json')];
}

echo 'id: ' . time() . PHP_EOL;
echo 'data: ' . json_encode(['title' => 'dockerProcessList', 'message' => $sse]) . PHP_EOL . PHP_EOL;
echo 'retry: 60000' . PHP_EOL . PHP_EOL;

flush();
