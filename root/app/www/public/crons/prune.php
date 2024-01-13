<?php

/*
----------------------------------
 ------  Created: 112423   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

set_time_limit(0);

logger(SYSTEM_LOG, 'Cron: running prune');
logger(CRON_PRUNE_LOG, 'run ->');
echo 'Cron run started: prune' . "\n";

if ($settingsFile['tasks']['prune']['disabled']) {
    logger(CRON_PRUNE_LOG, 'Cron run stopped: disabled in tasks menu');
    echo 'Cron run cancelled: disabled in tasks menu' . "\n";
    exit();
}

$imagePrune = $imageList = $volumePrune = [];
$images     = json_decode(dockerGetOrphanContainers(), true);
$volumes    = json_decode(dockerGetOrphanVolumes(), true);

logger(CRON_PRUNE_LOG, 'images=' . json_encode($images));
logger(CRON_PRUNE_LOG, 'volumes=' . json_encode($volumes));

if ($settingsFile['global']['autoPruneImages']) {
    if ($images) {
        foreach ($images as $image) {
            $imagePrune[]   = $image['ID'];
            $imageList[]    = ['cr' => $image['Repository'], 'created' => $image['CreatedSince'], 'size' => $image['Size']];
        }
    }
} else {
    logger(CRON_PRUNE_LOG, 'Auto prune images disabled');
}

if ($settingsFile['global']['autoPruneVolumes']) {
    if ($volumes) {
        foreach ($volumes as $volume) {
            $volumePrune[] = $volume['Name'];
        }
    }
} else {
    logger(CRON_PRUNE_LOG, 'Auto prune volumes disabled');
}

if ($imagePrune) {
    logger(CRON_PRUNE_LOG, 'Attempting auto image prune, images: ' . count($imagePrune));
    $images = implode(' ', $imagePrune);
    logger(CRON_PRUNE_LOG, 'images: ' . $images);
    $prune = dockerPruneImage();
    logger(CRON_PRUNE_LOG, 'result: ' . $prune);
}

if ($volumePrune) {
    logger(CRON_PRUNE_LOG, 'Attempting auto volume prune, volumes: ' . count($volumePrune));
    $volumes = implode(' ', $volumePrune);
    logger(CRON_PRUNE_LOG, 'volumes: ' . $volumes);
    $prune = dockerPruneVolume();
    logger(CRON_PRUNE_LOG, 'result: ' . $prune);
}

if ($settingsFile['notifications']['triggers']['prune']['active'] && (count($volumePrune) > 0 || count($imagePrune) > 0)) {
    $payload = ['event' => 'prune', 'volume' => count($volumePrune), 'image' => count($imagePrune), 'imageList' => $imageList];
    logger(CRON_PRUNE_LOG, 'Notification payload: ' . json_encode($payload));
    $notifications->notify($settingsFile['notifications']['triggers']['prune']['platform'], $payload);
}

logger(CRON_PRUNE_LOG, 'run <-');
