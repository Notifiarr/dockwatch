<?php

/*
----------------------------------
 ------  Created: 112423   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

logger($systemLog, 'Cron: running prune', 'info');

set_time_limit(0);

$logfile = LOGS_PATH . 'crons/prune-' . date('Ymd_Hi') . '.log';
logger($logfile, 'Cron run started');
echo 'Cron run started: prune' . "\n";

$imagePrune = $volumePrune = [];
$settings   = getFile(SETTINGS_FILE);
$images     = json_decode(dockerGetOrphanContainers(), true);
$volumes    = json_decode(dockerGetOrphanVolumes(), true);

logger($logfile, 'images=' . json_encode($images));
logger($logfile, 'volumes=' . json_encode($volumes));

if ($settings['global']['autoPruneImages']) {
    if ($images) {
        foreach ($images as $image) {
            $imagePrune[] = $image['ID'];
        }
    }
} else {
    logger($logfile, 'Auto prune images disabled');
}

if ($settings['global']['autoPruneVolumes']) {
    if ($volumes) {
        foreach ($volumes as $volume) {
            $volumePrune[] = $volume['Name'];
        }
    }
} else {
    logger($logfile, 'Auto prune volumes disabled');
}

if ($imagePrune) {
    logger($logfile, 'Attempting auto image prune, images: ' . count($imagePrune));
    $images = implode(' ', $imagePrune);
    logger($logfile, 'images: ' . $images);
    $prune = dockerPruneImage($images);
    logger($logfile, 'result: ' . $prune);
}

if ($volumePrune) {
    logger($logfile, 'Attempting auto volume prune, volumes: ' . count($volumePrune));
    $volumes = implode(' ', $volumePrune);
    logger($logfile, 'volumes: ' . $volumes);
    $prune = dockerPruneVolume($volumes);
    logger($logfile, 'result: ' . $prune);
}

if ($settings['notifications']['triggers']['prune']['active'] && (count($volumePrune) > 0 || count($imagePrune) > 0)) {
    $payload = ['event' => 'prune', 'volume' => count($volumePrune), 'image' => count($imagePrune)];
    logger($logfile, 'Notification payload: ' . json_encode($payload));
    $notifications->notify($settings['notifications']['triggers']['prune']['platform'], $payload);
}

logger($logfile, 'Cron run finished');
