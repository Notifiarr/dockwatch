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
    $loadTimes[]    = trackTime('dockerProcessList ->');
    $processList    = apiRequest('dockerProcessList', ['format' => true]);
    $processList    = json_decode($processList['response']['docker'], true);
    $loadTimes[]    = trackTime('dockerProcessList <-');

    $loadTimes[]    = trackTime('dockerImageSizes ->');
    $imageSizes     = apiRequest('dockerImageSizes');
    $imageSizes     = json_decode($imageSizes['response']['docker'], true);
    $loadTimes[]    = trackTime('dockerImageSizes <-');
}

if (in_array($_POST['page'], $getStats)) {
    $loadTimes[] = trackTime('dockerStats ->');
    $dockerStats = apiRequest('stats');
    $dockerStats = json_decode($dockerStats['response']['docker'], true);
    $loadTimes[] = trackTime('dockerStats <-');
}

if (!empty($processList)) {
    $loadTimes[] = trackTime('dockerInspect ->');
    foreach ($processList as $index => $process) {
        if (in_array($_POST['page'], $getInspect)) {
            $inspect = apiRequest('dockerInspect', ['name' => $process['Names'], 'useCache' => true, 'format' => true]);
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
    $loadTimes[] = trackTime('dockerInspect <-');
}

//-- UPDATE THE STATE FILE WHEN EVERYTHING IS FETCHED
if ($_POST['page'] == 'overview' || $_POST['page'] == 'containers') {
    setServerFile('state', json_encode($processList));
}

//-- STATE
$stateFile = $processList;