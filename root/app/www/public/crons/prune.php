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
echo date('c') . ' Cron: prune ->' . "\n";

if (!canCronRun('prune', $settingsTable)) {
    exit();
}

$imagePrune = $imageList = $volumePrune = [];
$networkPrune = [];
$images     = json_decode($docker->getOrphanContainers(), true);
$volumes    = json_decode($docker->getOrphanVolumes(), true);
$networks   = json_decode($docker->getOrphanNetworks(), true);

logger(CRON_PRUNE_LOG, 'images=' . json_encode($images));
logger(CRON_PRUNE_LOG, 'volumes=' . json_encode($volumes));
logger(CRON_PRUNE_LOG, 'networks=' . json_encode($networks));

if ($settingsTable['autoPruneImages']) {
    if ($images) {
        foreach ($images as $image) {
            $imagePrune[]   = $image['ID'];
            $imageList[]    = ['cr' => $image['Repository'], 'created' => $image['CreatedSince'], 'size' => $image['Size']];
        }
    }
} else {
    logger(CRON_PRUNE_LOG, 'Auto prune images disabled');
}

if ($settingsTable['autoPruneVolumes']) {
    if ($volumes) {
        foreach ($volumes as $volume) {
            $volumePrune[] = $volume['Name'];
        }
    }
} else {
    logger(CRON_PRUNE_LOG, 'Auto prune volumes disabled');
}

if ($settingsTable['autoPruneNetworks']) {
    if ($networks) {
        foreach ($networks as $network) {
            $networkPrune[] = $network['ID'];
        }
    }
} else {
    logger(CRON_PRUNE_LOG, 'Auto prune networks disabled');
}

if ($imagePrune) {
    logger(CRON_PRUNE_LOG, 'Attempting auto image prune, images: ' . count($imagePrune));
    $images = implode(' ', $imagePrune);
    logger(CRON_PRUNE_LOG, 'images: ' . $images);
    $prune = $docker->pruneImage();
    logger(CRON_PRUNE_LOG, 'result: ' . $prune);
}

if ($volumePrune) {
    logger(CRON_PRUNE_LOG, 'Attempting auto volume prune, volumes: ' . count($volumePrune));
    $volumes = implode(' ', $volumePrune);
    logger(CRON_PRUNE_LOG, 'volumes: ' . $volumes);
    $prune = $docker->pruneVolume();
    logger(CRON_PRUNE_LOG, 'result: ' . $prune);
}

if ($networkPrune) {
    logger(CRON_PRUNE_LOG, 'Attempting auto network prune, networks: ' . count($networkPrune));
    $volumes = implode(' ', $networkPrune);
    logger(CRON_PRUNE_LOG, 'networks: ' . $networks);
    $prune = $docker->pruneNetwork();
    logger(CRON_PRUNE_LOG, 'result: ' . $prune);
}

if (apiRequest('database-isNotificationTriggerEnabled', ['trigger' => 'prune'])['result'] && (count($volumePrune) > 0 || count($imagePrune) > 0 || count($networkPrune) > 0)) {
    $payload = ['event' => 'prune', 'network' => count($networkPrune), 'volume' => count($volumePrune), 'image' => count($imagePrune), 'imageList' => $imageList];
	$notifications->notify(0, 'prune', $payload);

    logger(CRON_PRUNE_LOG, 'Notification payload: ' . json_encode($payload));
}

echo date('c') . ' Cron: prune <-' . "\n";
logger(CRON_PRUNE_LOG, 'run <-');
