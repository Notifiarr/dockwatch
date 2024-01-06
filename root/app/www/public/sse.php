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

$dockerStats = apiRequest('stats');

if (!$dockerStats['response']['state']) { //-- NOT WRITTEN YET
    $dockerStats = apiRequest('dockerStats');
    $dockerStats = json_decode($dockerStats['response']['docker'], true);
} else {
    $dockerStats = $dockerStats['response']['state'];
}

$processList = apiRequest('dockerProcessList', ['format' => true]);
$processList = json_decode($processList['response']['docker'], true);

//-- GATHER ALL CONTAINERS FOR A SINGLE INSPECT
$inspectResults = [];
foreach ($processList as $index => $process) {
    $inspectContainers[] = $process['Names'];
}
$inspect        = apiRequest('dockerInspect', ['name' => implode(' ', $inspectContainers), 'format' => true]);
$inspectResults = json_decode($inspect['response']['docker'], true);

//-- ADD INSPECT AND STATS TO PROCESS LIST
foreach ($processList as $index => $process) {
    //-- ADD THE INSPECT OBJECT IF IT EXISTS
    $processList[$index]['inspect'][] = $inspectResults[$index];

    foreach ($dockerStats as $dockerStat) {
        if ($dockerStat['Name'] == $process['Names']) {
            $processList[$index]['stats'] = $dockerStat;
            break;
        }
    }
}

foreach ($processList as $process) {
    $nameHash = md5($process['Names']);
    $sse[] = ['hash' => $nameHash, 'row' => renderContainerRow($nameHash, 'json')];
}

echo 'id: ' . time() . PHP_EOL;
echo 'data: ' . json_encode(['title' => 'dockerProcessList', 'message' => $sse]) . PHP_EOL . PHP_EOL;
echo 'retry: 60000' . PHP_EOL . PHP_EOL;

flush();
