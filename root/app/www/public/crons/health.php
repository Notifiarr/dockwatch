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
echo date('c') . ' Cron: health ->' . "\n";

if ($settingsTable['taskHealthDisabled']) {
    logger(CRON_HEALTH_LOG, 'Cron cancelled: disabled in tasks menu');
    logger(CRON_HEALTH_LOG, 'run <-');
    echo date('c') . ' Cron: health cancelled, disabled in tasks menu' . "\n";
    echo date('c') . ' Cron: health <-' . "\n";
    exit();
}

if (!$settingsTable['restartUnhealthy'] && !apiRequest('database-isNotificationTriggerEnabled', ['trigger' => 'health'])['result']) {
    logger(CRON_HEALTH_LOG, 'Cron cancelled: restart and notify disabled');
    logger(CRON_HEALTH_LOG, 'run <-');
    echo date('c') . ' Cron health cancelled: restart unhealthy and notify disabled' . "\n";
    echo date('c') . ' Cron: health <-' . "\n";
    exit();
}

$processList = getExpandedProcessList(true, true, true);
$processList = $processList['processList'];

$healthFile = getFile(APP_DATA_PATH . 'health.json');
logger(CRON_HEALTH_LOG, '$healthFile=' . json_encode($healthFile, JSON_UNESCAPED_SLASHES));

if ($healthFile['code'] != 200) {
    $apiError = $healthFile['file'];
}
$healthFile = $healthFile['file'];

if ($apiError) {
    logger(CRON_HEALTH_LOG, 'Cron run stopped: error fetching health file (' . $apiError . ')');
    echo date('c') . ' Cron run stopped: error fetching health file (' . $apiError . ')' . "\n";
    exit();
}

$unhealthy = !empty($healthFile) ? $healthFile : [];
logger(CRON_HEALTH_LOG, '$unhealthy=' . json_encode($unhealthy, JSON_UNESCAPED_SLASHES));

foreach ($processList as $process) {
    $nameHash = md5($process['Names']);

    if (str_contains($process['Status'], 'unhealthy')) {
        logger(CRON_HEALTH_LOG, 'container \'' . $process['Names'] . '\' (' . $nameHash . ') is unhealthy');

        if (!$unhealthy[$nameHash]) {
            logger(CRON_HEALTH_LOG, 'container \'' . $process['inspect'][0]['Config']['Image'] . '\' has not been restarted or notified for yet');
            $unhealthy[$nameHash] = ['name' => $process['Names'], 'image' => $process['inspect'][0]['Config']['Image'], 'id' => $process['ID']];
        }
    } else {
        unset($unhealthy[$nameHash]);
        logger(CRON_HEALTH_LOG, 'container \'' . $process['inspect'][0]['Config']['Image'] . '\' has not been removed from the unhealthy list');
    }
}

logger(CRON_HEALTH_LOG, '$unhealthy=' . json_encode($unhealthy, JSON_UNESCAPED_SLASHES));

if ($unhealthy) {
    $containersTable = apiRequest('database-getContainers')['result'];

    foreach ($unhealthy as $nameHash => $container) {
        $notify = false;

        if ($container['restart'] || $container['notify']) {
            continue;
        }
        $thisContainer  = apiRequest('database-getContainerFromHash', ['hash' => $nameHash])['result'];
        $skipActions    = skipContainerActions($container['name'], $skipContainerActions);

        if ($skipActions) {
            logger(CRON_HEALTH_LOG, 'skipping: ' . $container['name'] . ', blacklisted (no state changes) container');
            continue;
        }

        if (!$thisContainer['restartUnhealthy']) {
            logger(CRON_HEALTH_LOG, 'skipping: ' . $container['name'] . ', restart unhealthy option not enabled');
            continue;
        }

        $unhealthy[$nameHash]['notify']     = 0;
        $unhealthy[$nameHash]['restart']    = 0;

        if (apiRequest('database-isNotificationTriggerEnabled', ['trigger' => 'health'])['result']) {
            $unhealthy[$nameHash]['notify'] = time();
            $notify = true;
        } else {
            logger(CRON_HEALTH_LOG, 'skipping notification for \'' . $container['name'] . '\', no notification senders with the health event enabled');
        }

        $dependencies = $dependencyFile[$container['name']]['containers'];
        $dependencies = is_array($dependencies) ? $dependencies : [];
        logger(CRON_HEALTH_LOG, 'dependencies: ' . (count($dependencies) > 0 ? implode(', ', $dependencies) : 'none'));

        logger(CRON_HEALTH_LOG, 'restarting unhealthy \'' . $container['name'] . '\'');
        $unhealthy[$nameHash]['restart'] = time();

        $apiRequest = apiRequest('docker-stopContainer', [], ['name' => $container['name']]);
        logger(CRON_HEALTH_LOG, 'docker-stopContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
        $apiRequest = apiRequest('docker-startContainer', [], ['name' => $container['name']]);
        logger(CRON_HEALTH_LOG, 'docker-startContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));

        if ($dependencies) {
            logger(CRON_HEALTH_LOG, 'restarting dependenices...');

            foreach ($dependencies as $dependency) {
                $apiRequest = apiRequest('docker-stopContainer', [], ['name' => $dependency]);
                logger(CRON_HEALTH_LOG, 'docker-stopContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                $apiRequest = apiRequest('docker-startContainer', [], ['name' => $dependency]);
                logger(CRON_HEALTH_LOG, 'docker-startContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
            }
        }

        if ($notify && $thisContainer['disableNotifications']) {
            logger(CRON_HEALTH_LOG, 'skipping notification for \'' . $container['name'] . '\', container set to not notify');
            $notify = false;
        }

        if ($notify) {
            logger(CRON_HEALTH_LOG, 'sending notification for \'' . $container['name'] . '\'');

            $payload = ['event' => 'health', 'container' => $container['name'], 'restarted' => $unhealthy[$nameHash]['restart']];
            $notifications->notify(0, 'health', $payload);

            logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
        }
    }

    logger(CRON_HEALTH_LOG, 'updating health file with unhealthy containers');
    setFile(APP_DATA_PATH . 'health.json', $unhealthy);
} else {
    logger(CRON_HEALTH_LOG, 'no unhealthly containers, empty the file');
    setFile(APP_DATA_PATH . 'health.json', []);
}

echo date('c') . ' Cron: health <-' . "\n";
logger(CRON_HEALTH_LOG, 'run <-');
