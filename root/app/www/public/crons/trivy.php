<?php

/*
----------------------------------
 ------  Created: 021626   ------
 ------       nzxl         ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

logger(SYSTEM_LOG, 'Cron: running trivy');
logger(CRON_TRIVY_LOG, 'run ->');
echo date('c') . ' Cron: trivy ->' . "\n";

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
$payload       = [
    'event'      => 'security',
    'containers' => 0,
    'critical'   => 0,
    'high'       => 0,
    'medium'     => 0,
    'low'        => 0,
    'unknown'    => 0,
    'details'    => []
];

logger(CRON_TRIVY_LOG, ' updating vuln databases.. (might take 1-2 mins)');
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

    logger(CRON_TRIVY_LOG, ' scanning image ' . $container['image'] . ' ->');
    echo date(format: 'c') . ' scanning image ' . $container['image'] . ' ->' . "\n";

    $scan = $trivy->scanImage($hash);
    if (!empty($scan)) {
        logger(CRON_TRIVY_LOG, $scan);
        echo date('c') . "\n" . $scan;
    }

    $newVulns = $trivy->getNewVulns($hash);
    if (count($newVulns) > 0) {
        logger(CRON_TRIVY_LOG, ' found ' . count($newVulns) . ' new or updated vulns in image ' . $container['image']);
        echo date('c') . ' found ' . count($newVulns) . ' new or updated vulns in image ' . $container['image'] . "\n";

        $payload['containers']++;
        foreach ($newVulns as $vuln) {
            if ($vuln['severity'] === "CRITICAL") {
                $payload['critical']++;
            }

            if ($vuln['severity'] === "HIGH") {
                $payload['high']++;
            }

            if ($vuln['severity'] === "MEDIUM") {
                $payload['medium']++;
            }

            if ($vuln['severity'] === "LOW") {
                $payload['low']++;
            }

            if ($vuln['severity'] === "UNKNOWN") {
                $payload['unknown']++;
            }
        }
        $payload['details'][$container['image']] = $newVulns;
    }

    logger(CRON_TRIVY_LOG, ' scanning image ' . $container['image'] . ' <-');
    echo date(format: 'c') . ' scanning image ' . $container['image'] . ' <-' . "\n";
}

if (apiRequest('database/notification/trigger/enabled', ['trigger' => 'security'])['result']) {
    $notifications->notify(0, 'security', $payload);

    logger(CRON_TRIVY_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
    echo date('c') . ' Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";
} else {
    logger(CRON_TRIVY_LOG, 'skipping notification, no notification senders with the security event enabled', 'warn');
}

echo date('c') . ' Cron: trivy <-' . "\n";
logger(CRON_TRIVY_LOG, 'run <-');
