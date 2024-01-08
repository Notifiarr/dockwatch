<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

if (file_exists('loader.php')) {
    define('ABSOLUTE_PATH', './');
}
if (file_exists('../loader.php')) {
    define('ABSOLUTE_PATH', '../');
}
if (file_exists('../../loader.php')) {
    define('ABSOLUTE_PATH', '../../');
}
require ABSOLUTE_PATH . 'loader.php';

$fetchProc      = in_array($_POST['page'], $getProc) || $_POST['hash'];
$fetchStats     = in_array($_POST['page'], $getStats) || $_POST['hash'];
$fetchInspect   = in_array($_POST['page'], $getInspect) || $_POST['hash'];

$processList = [];
if ($fetchProc) {
    $loadTimes[]    = trackTime('dockerProcessList ->');
    $processList    = apiRequest('dockerProcessList', ['format' => true]);
    $processList    = json_decode($processList['response']['docker'], true);
    $loadTimes[]    = trackTime('dockerProcessList <-');

    $loadTimes[]    = trackTime('dockerImageSizes ->');
    $imageSizes     = apiRequest('dockerImageSizes');
    $imageSizes     = json_decode($imageSizes['response']['docker'], true);
    $loadTimes[]    = trackTime('dockerImageSizes <-');
}

if ($fetchStats) {
    $loadTimes[] = trackTime('dockerStats ->');
    $dockerStats = apiRequest('stats');

    if (!$dockerStats['response']['state']) { //-- NOT WRITTEN YET
        $dockerStats = apiRequest('dockerStats');
        $dockerStats = json_decode($dockerStats['response']['docker'], true);
    } else {
        $dockerStats = $dockerStats['response']['state'];
    }

    $loadTimes[] = trackTime('dockerStats <-');
}

if (!empty($processList)) {
    $loadTimes[] = trackTime('dockerInspect ->');
    //-- GATHER ALL CONTAINERS FOR A SINGLE INSPECT
    $inspectResults = [];
    if ($fetchInspect) {
        foreach ($processList as $index => $process) {
            $inspectContainers[] = $process['Names'];
        }
        $inspect        = apiRequest('dockerInspect', ['name' => implode(' ', $inspectContainers), 'format' => true]);
        $inspectResults = json_decode($inspect['response']['docker'], true);
    }

    //-- ADD INSPECT AND STATS TO PROCESS LIST
    foreach ($processList as $index => $process) {
        //-- ADD THE INSPECT OBJECT IF IT EXISTS
        $processList[$index]['inspect'][] = $inspectResults[$index];

        if ($fetchStats) {
            foreach ($dockerStats as $dockerStat) {
                if ($dockerStat['Name'] == $process['Names']) {
                    $processList[$index]['stats'] = $dockerStat;
                    break;
                }
            }
        }
    }
    $loadTimes[] = trackTime('dockerInspect <-');
}

//-- UPDATE THE STATE FILE WHEN EVERYTHING IS FETCHED
if ($_POST['page'] == 'overview' || $_POST['page'] == 'containers') {
    setServerFile('state', json_encode($processList));
}

//-- STATE
$stateFile = $processList;