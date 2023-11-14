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

$processList = [];
if (in_array($_POST['page'], $getProc)) {
    $processList = dockerProcessList();
    $processList = json_decode($processList, true);
}

if (in_array($_POST['page'], $getStats)) {
    $dockerStats = dockerStats();
    $dockerStats = json_decode($dockerStats, true);
}

if (!empty($processList)) {
    foreach ($processList as $index => $process) {
        if (in_array($_POST['page'], $getInspect)) {
            $inspect = dockerInspect($process['Names']);
            $processList[$index]['inspect'] = json_decode($inspect, true);
        }

        if (in_array($_POST['page'], $getStats)) {
            foreach ($dockerStats as $dockerStat) {
                if ($dockerStat['Name'] == $process['Names']) {
                    $processList[$index]['stats'] = $dockerStat;
                    break;
                }
            }
        }
    }
}

//-- UPDATE THE STATE FILE WHEN EVERYTHING IS FETCHED
if ($_POST['page'] == 'overview' || $_POST['page'] == 'containers') {
    setFile(STATE_FILE, json_encode($processList));
}

//-- SETTINGS
$settings = getFile(SETTINGS_FILE);

//-- STATE
$state = $processList;