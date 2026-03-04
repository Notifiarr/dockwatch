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
    $notify   = false;

    if (in_array($hash, $imagesScanned)) {
        continue;
    }
    $imagesScanned[] = $hash;

    logger(logfile: CRON_TRIVY_LOG, msg: ' scanning image ' . $container['image'] . ' ->');
    echo date(format: 'c') . ' scanning image ' . $container['image'] . ' ->' . "\n";

    $scan = $trivy->scanImage($hash);
    if (!empty($scan)) {
        logger(logfile: CRON_TRIVY_LOG, msg: $scan);
        echo date('c') . "\n" . $scan;
    }

    $newVulns = $trivy->getNewVulns($hash);
    if (count($newVulns) > 0) {
        logger(logfile: CRON_TRIVY_LOG, msg: ' found ' . count($newVulns) . ' new or updated vulns in image ' . $container['image']);
        echo date('c') . ' found ' . count($newVulns) . ' new or updated vulns in image ' . $container['image'] . "\n";

        if (apiRequest('database/notification/trigger/enabled', ['trigger' => 'security'])['result']) {
            $notify = true;
        } else {
            logger(CRON_TRIVY_LOG, 'skipping notification for \'' . $container['name'] . '\', no notification senders with the security event enabled', 'warn');
        }
    }

    if ($notify) {
        logger(CRON_TRIVY_LOG, 'sending notification for \'' . $container['name'] . '\'');
        echo date('c') . ' sending notification for \'' . $container['name'] . '\'' . "\n";

        $payload = ['event' => 'security', 'container' => $container['name'], 'image' => $container['image'], 'count' => count($newVulns), 'vulns' => $newVulns];
        //-- TODO: MATTERMOST/TELEGRAM MESSAGE & NOTIFIARR INTEGRATION MESSAGE
        // $notifications->notify(0, 'security', $payload);

        logger(CRON_TRIVY_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
        echo date('c') . ' Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";
    }

    logger(logfile: CRON_TRIVY_LOG, msg: ' scanning image ' . $container['image'] . ' <-');
    echo date(format: 'c') . ' scanning image ' . $container['image'] . ' <-' . "\n";
}

echo date('c') . ' Cron: trivy <-' . "\n";
logger(CRON_TRIVY_LOG, 'run <-');
