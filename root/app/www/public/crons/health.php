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
    echo date('c') . ' Cron run cancelled: disabled in tasks menu' . "\n";
    exit();
}

if (!$settingsFile['global']['restartUnhealthy'] && !$settingsFile['notifications']['triggers']['health']['active']) {
    logger(CRON_HEALTH_LOG, 'Cron run stopped: restart and notify disabled');
    echo date('c') . ' Cron run stopped: restart and notify disabled' . "\n";
    exit();
}

$processList = getExpandedProcessList(true, true, true);
$processList = $processList['processList'];

$healthFile = getServerFile('health');
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
    }
}

logger(CRON_HEALTH_LOG, '$unhealthy=' . json_encode($unhealthy, JSON_UNESCAPED_SLASHES));

if ($unhealthy) {
    foreach ($unhealthy as $nameHash => $container) {
        $notify = false;

        if ($container['restart'] || $container['notify']) {
            continue;
        }

        $skipActions = skipContainerActions($container['name'], $skipContainerActions);

        if ($skipActions) {
            logger(CRON_HEALTH_LOG, 'skipping: ' . $container['name'] . ', blacklisted (no state changes) container');
            continue;
        }

        if (!$settingsFile['containers'][$nameHash]['restartUnhealthy']) {
            logger(CRON_HEALTH_LOG, 'skipping: ' . $container['name'] . ', restart unhealthy option not enabled');
            continue;
        }

        $unhealthy[$nameHash]['notify']     = 0;
        $unhealthy[$nameHash]['restart']    = 0;

        if ($settingsFile['notifications']['triggers']['health']['active'] && $settingsFile['notifications']['triggers']['health']['platform']) {
            $unhealthy[$nameHash]['notify'] = time();
            $notify = true;
        }

        $dependencies = $dependencyFile[$container['name']]['containers'];
        $dependencies = is_array($dependencies) ? $dependencies : [];
        logger(CRON_HEALTH_LOG, 'dependencies: ' . (count($dependencies) > 0 ? implode(', ', $dependencies) : 'none'));

        logger(CRON_HEALTH_LOG, 'restarting unhealthy \'' . $container['name'] . '\'');
        $unhealthy[$nameHash]['restart'] = time();

        $apiResult = apiRequest('dockerStopContainer', [], ['name' => $container['name']]);
        logger(CRON_HEALTH_LOG, 'dockerStopContainer:' . json_encode($apiResult, JSON_UNESCAPED_SLASHES));
        $apiResult = apiRequest('dockerStartContainer', [], ['name' => $container['name']]);
        logger(CRON_HEALTH_LOG, 'dockerStartContainer:' . json_encode($apiResult, JSON_UNESCAPED_SLASHES));

        if ($dependencies) {
            logger(CRON_HEALTH_LOG, 'restarting dependenices...');

            foreach ($dependencies as $dependency) {
                $apiResult = apiRequest('dockerStopContainer', [], ['name' => $dependency]);
                logger(CRON_HEALTH_LOG, 'dockerStopContainer:' . json_encode($apiResult, JSON_UNESCAPED_SLASHES));
                $apiResult = apiRequest('dockerStartContainer', [], ['name' => $dependency]);
                logger(CRON_HEALTH_LOG, 'dockerStartContainer:' . json_encode($apiResult, JSON_UNESCAPED_SLASHES));
            }
        }

        if ($settingsFile['containers'][$nameHash]['disableNotifications']) {
            logger(CRON_HEALTH_LOG, 'skipping notification for \'' . $container['name'] . '\', container set to not notify');
            $notify = false;
        }

        if ($notify) {
            logger(CRON_HEALTH_LOG, 'sending notification for \'' . $container['name'] . '\'');
            $payload = ['event' => 'health', 'container' => $container['name'], 'restarted' => $unhealthy[$nameHash]['restart']];
            logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
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
