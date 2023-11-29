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
    $processList = apiRequest('dockerProcessList');
    $processList = json_decode($processList['response']['docker'], true);
}

if (in_array($_POST['page'], $getStats)) {
    $dockerStats = apiRequest('dockerStats');
    $dockerStats = json_decode($dockerStats['response']['docker'], true);
}

if (!empty($processList)) {
    foreach ($processList as $index => $process) {
        if (in_array($_POST['page'], $getInspect)) {
            $inspect = apiRequest('dockerInspect', ['name' => $process['Names'], 'useCache' => true]);
            $processList[$index]['inspect'] = json_decode($inspect['response']['docker'], true);
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
    setServerFile('state', json_encode($processList));
}

//-- STATE
$stateFile = $processList;