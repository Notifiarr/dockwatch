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

if (!canCronRun('health', $settingsTable)) {
    exit();
}

$containerList = apiRequest('stats/containers')['result']['result'];
if (empty($containerList)) {
    logger(CRON_HEALTH_LOG, 'Cron run stopped: error fetching container list');
    echo date('c').'Cron run stopped: error fetching container list';
    exit();
}

$healthFile = apiRequest('file/health')['result'] ?: [];
logger(CRON_HEALTH_LOG, '$healthFile=' . json_encode($healthFile, JSON_UNESCAPED_SLASHES));

$unhealthy = $healthFile ?: [];
logger(CRON_HEALTH_LOG, '$unhealthy=' . json_encode($unhealthy, JSON_UNESCAPED_SLASHES));

foreach ($containerList as $container) {
    $nameHash = md5($container['name']);

    if ($container['health'] == 'unhealthy') {
        logger(CRON_HEALTH_LOG, 'container \'' . $container['name'] . '\' (' . $container['id'] . ') is unhealthy');

        if (!$unhealthy[$nameHash]) {
            logger(CRON_HEALTH_LOG, 'container \'' . $container['name'] . '\' has not been restarted or notified for yet');
            $unhealthy[$nameHash] = ['name' => $container['name'], 'image' => $container['image'], 'id' => $container['id']];
        }
    } else if ($container['health'] == 'healthy' && $unhealthy[$nameHash] || $container['health'] == null && $unhealthy[$nameHash]) {
        unset($unhealthy[$nameHash]);
        logger(CRON_HEALTH_LOG, 'container \'' . $container['name'] . '\' has not been removed from the unhealthy list');
    }
}

logger(CRON_HEALTH_LOG, '$unhealthy=' . json_encode($unhealthy, JSON_UNESCAPED_SLASHES));

if ($unhealthy) {
    $containersTable = apiRequest('database/containers')['result'];

    foreach ($unhealthy as $nameHash => $container) {
        $notify = false;

        if ($container['restart'] || $container['notify']) {
            continue;
        }
        $thisContainer  = apiRequest('database/container/hash', ['hash' => $nameHash])['result'];
        $skipActions    = skipContainerActions($container['name'], $skipContainerActions);

        if ($skipActions) {
            logger(CRON_HEALTH_LOG, 'skipping: ' . $container['name'] . ', blacklisted (no state changes) container');
            continue;
        }

        $unhealthy[$nameHash]['notify']     = 0;
        $unhealthy[$nameHash]['restart']    = 0;

        if (apiRequest('database/notification/trigger/enabled', ['trigger' => 'health'])['result']) {
            $unhealthy[$nameHash]['notify'] = time();
            $notify = true;
        } else {
            logger(CRON_HEALTH_LOG, 'skipping notification for \'' . $container['name'] . '\', no notification senders with the health event enabled');
        }

        if ($thisContainer['restartUnhealthy']) {
            $dependencies = $dependencyFile[$container['name']]['containers'];
            $dependencies = is_array($dependencies) ? $dependencies : [];
            logger(CRON_HEALTH_LOG, 'dependencies: ' . (count($dependencies) > 0 ? implode(', ', $dependencies) : 'none'));

            logger(CRON_HEALTH_LOG, 'restarting unhealthy \'' . $container['name'] . '\'');
            $unhealthy[$nameHash]['restart'] = time();

            $apiRequest = apiRequest('docker/container/stop', [], ['name' => $container['name']]);
            logger(CRON_HEALTH_LOG, 'docker/container/stop:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
            $apiRequest = apiRequest('docker/container/start', [], ['name' => $container['name']]);
            logger(CRON_HEALTH_LOG, 'docker/container/start:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));

            if ($dependencies) {
                logger(CRON_HEALTH_LOG, 'restarting dependencies...');

                foreach ($dependencies as $dependency) {
                    $apiRequest = apiRequest('docker/container/stop', [], ['name' => $dependency]);
                    logger(CRON_HEALTH_LOG, 'docker/container/stop:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                    $apiRequest = apiRequest('docker/container/start', [], ['name' => $dependency]);
                    logger(CRON_HEALTH_LOG, 'docker/container/start:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                }
            }
        } else {
            logger(CRON_HEALTH_LOG, 'skipping: ' . $container['name'] . ', restart unhealthy option not enabled');
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
    apiRequest('file/health', [], ['contents' => $unhealthy]);
} else {
    logger(CRON_HEALTH_LOG, 'no unhealthy containers, empty the file');
    apiRequest('file/health', [], ['contents' => []]);
}

echo date('c') . ' Cron: health <-' . "\n";
logger(CRON_HEALTH_LOG, 'run <-');
