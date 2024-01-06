<?php

/*
----------------------------------
 ------  Created: 120723   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

logger(SYSTEM_LOG, 'Cron: running health');
logger(CRON_HEALTH_LOG, 'run ->');
echo 'Cron run started: health' . "\n";

if ($settingsFile['tasks']['health']['disabled']) {
    logger(CRON_HEALTH_LOG, 'Cron run stopped: disabled in tasks menu');
    echo 'Cron run cancelled: disabled in tasks menu' . "\n";
    exit();
}

if (!$settingsFile['global']['restartUnhealthy'] && !$settingsFile['notifications']['triggers']['health']['active']) {
    logger(CRON_HEALTH_LOG, 'Cron run stopped: restart and notify disabled');
    echo 'Cron run stopped: restart and notify disabled' . "\n";
    exit();
}

$processList = apiRequest('dockerProcessList', ['format' => true]);
$processList = json_decode($processList['response']['docker'], true);

$healthFile = getServerFile('health');
logger(CRON_HEALTH_LOG, '$healthFile=' . json_encode($healthFile));

if ($healthFile['code'] != 200) {
    $apiError = $healthFile['file'];
}
$healthFile = $healthFile['file'];

if ($apiError) {
    logger(CRON_HEALTH_LOG, 'Cron run stopped: error fetching health file (' . $apiError . ')');
    echo 'Cron run stopped: error fetching health file (' . $apiError . ')' . "\n";
    exit();
}

$unhealthy = !empty($healthFile) ? $healthFile : [];
logger(CRON_HEALTH_LOG, '$unhealthy=' . json_encode($unhealthy));

foreach ($processList as $process) {
    $nameHash = md5($process['Names']);

    if (str_contains($process['Status'], 'unhealthy')) {
        logger(CRON_HEALTH_LOG, 'container \'' . $process['Names'] . '\' (' . $nameHash . ') is unhealthy');

        if (!$unhealthy[$nameHash]) {
            logger(CRON_HEALTH_LOG, 'container \'' . $process['Names'] . '\' has not been restarted or notified for yet');
            $unhealthy[$nameHash] = ['name' => $process['Names']];
        }
    } else {
        unset($unhealthy[$nameHash]);
    }
}

logger(CRON_HEALTH_LOG, '$unhealthy=' . json_encode($unhealthy));

if ($unhealthy) {
    foreach ($unhealthy as $nameHash => $container) {
        $notify = false;

        if ($container['restart'] || $container['notify']) {
            continue;
        }

        $unhealthy[$nameHash]['notify']     = 0;
        $unhealthy[$nameHash]['restart']    = 0;

        if ($settingsFile['notifications']['triggers']['health']['active'] && $settingsFile['notifications']['triggers']['health']['platform']) {
            $unhealthy[$nameHash]['notify'] = time();
            $notify = true;
        }

        if ($settingsFile['global']['restartUnhealthy']) {
            logger(CRON_HEALTH_LOG, 'restarting unhealthy \'' . $container['name'] . '\'');
            $unhealthy[$nameHash]['restart'] = time();

            $apiResult = apiRequest('dockerStopContainer', [], ['name' => $container['name']]);
            logger(CRON_HEALTH_LOG, 'dockerStopContainer:' . json_encode($apiResult));
            $apiResult = apiRequest('dockerStartContainer', [], ['name' => $container['name']]);
            logger(CRON_HEALTH_LOG, 'dockerStartContainer:' . json_encode($apiResult));
        }

        if ($notify) {
            logger(CRON_HEALTH_LOG, 'sending notification for \'' . $container['name'] . '\'');
            $payload = ['event' => 'health', 'container' => $container['name'], 'restarted' => $unhealthy[$nameHash]['restart']];
            logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload));
            $notifications->notify($settingsFile['notifications']['triggers']['health']['platform'], $payload);
        }
    }

    logger(CRON_HEALTH_LOG, 'updating health file with unhealthy containers');
    setServerFile('health', $unhealthy);
} else {
    logger(CRON_HEALTH_LOG, 'no unhealthly containers, empty the file');
    setServerFile('health', []);
}

logger(CRON_HEALTH_LOG, 'run <-');
