<?php

/*
----------------------------------
 ------  Created: 111723   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

logger(SYSTEM_LOG, 'Cron: running state');
logger(CRON_STATE_LOG, 'run ->');
echo date('c') . ' Cron: state' . "\n";

if (!canCronRun('state', $settingsTable)) {
    exit();
}

$notify = $added = $removed = $previousStates = $currentStates = $previousContainers = $currentContainers = [];

$stateFile      = apiRequest('file/state')['result'];
$previousStates = $stateFile;
$currentStates  = dockerState();

if ($currentStates) {
    apiRequest('file/state', [], ['contents' => $currentStates]);
} else {
    logger(CRON_STATE_LOG, 'STATE_FILE update skipped, $currentStates empty', 'warn');
}

logger(CRON_STATE_LOG, 'previousStates: ' . json_encode($previousStates, JSON_UNESCAPED_SLASHES));
logger(CRON_STATE_LOG, 'currentStates: ' . json_encode($currentStates, JSON_UNESCAPED_SLASHES));

foreach ($previousStates as $previousState) {
    $previousContainers[] = $previousState['Names'];
}

foreach ($currentStates as $currentState) {
    $currentContainers[] = $currentState['Names'];
}

logger(CRON_STATE_LOG, 'previousContainers: ' . json_encode($previousContainers, JSON_UNESCAPED_SLASHES));
logger(CRON_STATE_LOG, 'currentContainers: ' . json_encode($currentContainers, JSON_UNESCAPED_SLASHES));

//-- CHECK FOR ADDED CONTAINERS
$containersTable = apiRequest('database/containers')['result'];
foreach ($currentContainers as $currentContainer) {
    if (!in_array($currentContainer, $previousContainers)) {
        $containerHash  = md5($currentContainer);
        $exists         = apiRequest('database/container/hash', ['hash' => $containerHash]);

        if (!$exists) {
            $updates    = $settingsTable['updates'] ?: 3; //-- CHECK ONLY FALLBACK
            $frequency  = $settingsTable['updatesFrequency'] ?: DEFAULT_CRON; //-- DAILY FALLBACK

            apiRequest('database/container/add', [], ['hash' => $containerHash, 'updates' => $updates, 'frequency' => $frequency]);
        }

        $added[] = ['container' => $currentContainer];
    }
}

if ($added && apiRequest('database/notification/trigger/enabled', ['trigger' => 'added'])['result']) {
    $notify['state']['added'] = $added;
    logger(CRON_STATE_LOG, 'Added containers: ' . json_encode($added, JSON_UNESCAPED_SLASHES));
}

logger(CRON_STATE_LOG, 'Added containers: ' . json_encode($added, JSON_UNESCAPED_SLASHES));

//-- CHECK FOR REMOVED CONTAINERS
foreach ($previousContainers as $previousContainer) {
    if (!in_array($previousContainer, $currentContainers)) {
        $containerHash  = md5($previousContainer);
        $container      = apiRequest('database/container/hash', ['hash' => $containerHash])['result'];

        if (!$container['disableNotifications']) {
            $removed[] = ['container' => $previousContainer];
        }
    }
}

if ($removed && apiRequest('database/notification/trigger/enabled', ['trigger' => 'removed'])['result']) {
    $notify['state']['removed'] = $removed;
    logger(CRON_STATE_LOG, 'Removed containers: ' . json_encode($removed, JSON_UNESCAPED_SLASHES));
}

//-- CHECK FOR STATE CHANGED CONTAINERS
foreach ($currentStates as $currentState) {
    foreach ($previousStates as $previousState) {
        $containerHash  = md5($currentState['Names']);
        $container      = apiRequest('database/container/hash', ['hash' => $containerHash])['result'];

        if (apiRequest('database/notification/trigger/enabled', ['trigger' => 'stateChange'])['result'] && !$container['disableNotifications'] && $currentState['Names'] == $previousState['Names']) {
            if ($previousState['State'] != $currentState['State']) {
                $notify['state']['changed'][] = ['container' => $currentState['Names'], 'previous' => $previousState['State'], 'current' => $currentState['State']];
            }
        }
    }
}
logger(CRON_STATE_LOG, 'State changed containers: ' . json_encode($notify['state']['changed'], JSON_UNESCAPED_SLASHES));

foreach ($currentStates as $currentState) {
    $containerHash  = md5($currentState['Names']);
    $container      = apiRequest('database/container/hash', ['hash' => $containerHash])['result'];

    //-- CHECK FOR HIGH CPU USAGE CONTAINERS
    if (apiRequest('database/notification/trigger/enabled', ['trigger' => 'cpuHigh'])['result'] && !$container['disableNotifications'] && floatval($settingsTable['cpuThreshold']) > 0) {
        if ($currentState['stats']['CPUPerc']) {
            $cpu        = floatval(str_replace('%', '', $currentState['stats']['CPUPerc']));
            $cpuAmount  = intval($settingsTable['cpuAmount']);

            if ($cpuAmount > 0) {
                $cpu = number_format(($cpu / $cpuAmount), 2);
            }

            if ($cpu > floatval($settingsTable['cpuThreshold'])) {
                $notify['usage']['cpu'][] = ['container' => $currentState['Names'], 'usage' => $cpu];
            }
        }
    }

    //-- CHECK FOR HIGH MEMORY USAGE CONTAINERS
    if (apiRequest('database/notification/trigger/enabled', ['trigger' => 'memHigh'])['result'] && !$container['disableNotifications'] && floatval($settingsTable['memThreshold']) > 0) {
        if ($currentState['stats']['MemPerc']) {
            $mem = floatval(str_replace('%', '', $currentState['stats']['MemPerc']));

            if ($mem > floatval($settingsTable['memThreshold'])) {
                $notify['usage']['mem'][] = ['container' => $currentState['Names'], 'usage' => $mem];
            }
        }
    }
}
logger(CRON_STATE_LOG, 'CPU issue containers: ' . json_encode($notify['usage']['cpu'], JSON_UNESCAPED_SLASHES));
logger(CRON_STATE_LOG, 'Mem issue containers: ' . json_encode($notify['usage']['mem'], JSON_UNESCAPED_SLASHES));

if (!$previousStates) {
    $notify = [];
    logger(CRON_STATE_LOG, 'Notification skipped, $previousStates empty', 'warn');
}

if (!$currentStates) {
    $notify = [];
    logger(CRON_STATE_LOG, 'Notification skipped, $currentStates empty', 'warn');
}

if ($notify['state']) {
    //-- IF THEY USE THE SAME PLATFORM, COMBINE THEM
    if (
        apiRequest('database/notification/link/platform/name', ['name' => 'stateChange'])['result'] == apiRequest('database/notification/link/platform/name', ['name' => 'added'])['result'] &&
        apiRequest('database/notification/link/platform/name', ['name' => 'stateChange'])['result'] == apiRequest('database/notification/link/platform/name', ['name' => 'removed'])['result']
        ) {
        $payload = ['event' => 'state', 'changes' => $notify['state']['changed'], 'added' => $notify['state']['added'], 'removed' => $notify['state']['removed']];
        $notifications->notify(0, 'stateChange', $payload);

        logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
    } else {
        if ($notify['state']['changed']) {
            $payload = ['event' => 'state', 'changes' => $notify['state']['changed']];
            $notifications->notify(0, 'stateChange', $payload);

            logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
        }

        if ($notify['state']['added']) {
            $payload = ['event' => 'state', 'added' => $notify['state']['added']];
            $notifications->notify(0, 'added', $payload);

            logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
        }

        if ($notify['state']['removed']) {
            $payload = ['event' => 'state', 'removed' => $notify['state']['removed']];
            $notifications->notify(0, 'removed', $payload);

            logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
        }
    }
}

if ($notify['usage']) {
    if ($notify['usage']['cpu']) {
        $payload = ['event' => 'usage', 'cpu' => $notify['usage']['cpu'], 'cpuThreshold' => $settingsTable['cpuThreshold']];
        $notifications->notify(0, 'cpuHigh', $payload);

        logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    if ($notify['usage']['mem']) {
        $payload = ['event' => 'usage', 'mem' => $notify['usage']['mem'], 'memThreshold' => $settingsTable['memThreshold']];
        $notifications->notify(0, 'memHigh', $payload);

        logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
    }
}

echo date('c') . ' Cron: state <-' . "\n";
logger(CRON_STATE_LOG, 'run <-');
