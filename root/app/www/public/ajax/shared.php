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

$loadTimes[] = trackTime('getExpandedProcessList ->');
$getExpandedProcessList = getExpandedProcessList($fetchProc, $fetchStats, $fetchInspect);
$processList            = $getExpandedProcessList['processList'];
foreach ($getExpandedProcessList['loadTimes'] as $loadTime) {
    $loadTimes[] = $loadTime;
}
$loadTimes[] = trackTime('getExpandedProcessList <-');

//-- UPDATE THE STATE FILE WHEN EVERYTHING IS FETCHED
if ($_POST['page'] == 'overview' || $_POST['page'] == 'containers') {
    setServerFile('state', json_encode($processList));
}

//-- STATE
$stateFile = $processList;