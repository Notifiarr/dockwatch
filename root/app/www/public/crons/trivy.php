<?php

/*
----------------------------------
 ------  Created: 021626   ------
 ------  nzxl	             ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

logger(SYSTEM_LOG, 'Cron: running trivy');
logger(CRON_TRIVY_LOG, 'run ->');
echo date('c') . ' Cron: trivy' . "\n";

if (!canCronRun('trivy', $settingsTable)) {
    exit();
}

$trivy = $trivy ?? new Trivy();

$containerList = apiRequest('stats/containers')['result']['result'];
if (empty($containerList)) {
    logger(CRON_TRIVY_LOG, 'Cron run stopped: error fetching container list', 'error');
    echo date('c') . 'Cron run stopped: error fetching container list';
    exit();
}
$imagesScanned = [];

logger(logfile: CRON_TRIVY_LOG, msg: ' updating vuln databases.. (might take 1-2 mins)');
echo date(format: 'c') . ' updating vuln databases.. (might take 1-2 mins)' . "\n";
$trivy->downloadDB();
$trivy->downloadJavaDB();

foreach ($containerList as $container) {
    $nameHash = md5($container['name']);
    $hash     = substr(preg_replace('/sha256\:/', '', $docker->getImageHash($container['image'])), 0, 4);

    if (in_array($hash, $imagesScanned)) {
        continue;
    }
    $imagesScanned[] = $hash;

    logger(logfile: CRON_TRIVY_LOG, msg: ' scanning image ' . $container['image'] . ' ->');
    echo date(format: 'c') . ' scanning image ' . $container['image'] . ' ->' . "\n";

    $scan = $trivy->scanImage($hash);
    logger(logfile: CRON_TRIVY_LOG, msg: $scan);
    echo date('c') . "\n" . $scan;

    logger(logfile: CRON_TRIVY_LOG, msg: ' scanning image ' . $container['image'] . ' <-');
    echo date(format: 'c') . ' scanning image ' . $container['image'] . ' <-' . "\n";
}

echo date('c') . ' Cron: trivy <-' . "\n";
logger(CRON_TRIVY_LOG, 'run <-');
