<?php

/*
----------------------------------
 ------  Created: 021626   ------
 ------       nzxl         ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

logger(SYSTEM_LOG, 'Cron: running security');
logger(CRON_SECURITY_LOG, 'run ->');
echo date('c') . ' Cron: security ->' . "\n";

if (!canCronRun('security', $settingsTable)) {
    exit();
}

$security = $security ?? new Security();

$containerList = apiRequest('stats/containers')['result']['result'];
if (empty($containerList)) {
    logger(CRON_SECURITY_LOG, 'Cron run stopped: error fetching container list', 'error');
    echo date('c') . 'Cron run stopped: error fetching container list';
    exit();
}
$imagesScanned    = [];
$payload          = [
    'event'      => 'security',
    'changed'    => false,
    'containers' => 0,
    'critical'   => 0,
    'high'       => 0,
    'medium'     => 0,
    'low'        => 0,
    'unknown'    => 0,
    'details'    => []
];
$skipNotification = false;

foreach ($containerList as $container) {
    $nameHash = md5($container['name']);
    $hash     = substr(preg_replace('/sha256\:/', '', $docker->getImageHash($container['image'])), 0, 4);

    if (in_array($hash, $imagesScanned) || !str_contains($container['status'], 'running') && $settingsTable['securitySkipStopped']) {
        logger(CRON_SECURITY_LOG, ' skipped image ' . $container['image']);
        echo date(format: 'c') . ' skipped image ' . $container['image'] . "\n";
        continue;
    }
    $imagesScanned[] = $hash;

    logger(CRON_SECURITY_LOG, ' scanning image ' . $container['image'] . ' ->');
    echo date(format: 'c') . ' scanning image ' . $container['image'] . ' ->' . "\n";

    $scan = $security->scanImage($container['image'], intval($settingsTable['securityScanner']), $settingsTable['securitySnykAPIKey']);
    if (!empty($scan)) {
        logger(CRON_SECURITY_LOG, $scan);
        echo date('c') . "\n" . $scan;
    }

    $vulns = $security->getVulns($container['image']);
    if (count($vulns) > 0) {
        logger(CRON_SECURITY_LOG, ' found ' . count($vulns) . ' vulns in image ' . $container['image']);
        echo date('c') . ' found ' . count($vulns) . ' vulns in image ' . $container['image'] . "\n";

        $payload['containers']++;
        foreach ($vulns as $vuln) {
            $payload[strtolower($vuln['severity'])]++;
        }
        $payload['details'][$container['image']] = $vulns;
    }

    if (count($security->getNewVulns($container['image'])) > 0) {
        $payload['changed'] = true;
    }

    logger(CRON_SECURITY_LOG, ' scanning image ' . $container['image'] . ' <-');
    echo date(format: 'c') . ' scanning image ' . $container['image'] . ' <-' . "\n";
}

if ($payload['containers'] < 1) {
    $skipNotification = true;
}

if (apiRequest('database/notification/trigger/enabled', ['trigger' => 'security'])['result'] && !$skipNotification) {
    $notifications->notify(0, 'security', $payload);

    logger(CRON_SECURITY_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
    echo date('c') . ' Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";
} else {
    logger(CRON_SECURITY_LOG, 'skipping notification, no notification senders with the security event enabled', 'warn');
}

echo date('c') . ' Cron: security <-' . "\n";
logger(CRON_SECURITY_LOG, 'run <-');
