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

if ($settingsFile['tasks']['state']['disabled']) {
    logger(CRON_STATE_LOG, 'Cron cancelled: disabled in tasks menu');
    logger(CRON_STATE_LOG, 'run <-');
    echo date('c') . ' Cron: state cancelled, disabled in tasks menu' . "\n";
    echo date('c') . ' Cron: state <-' . "\n";
    exit();
}

$notify = $added = $removed = $previousStates = $currentStates = $previousContainers = $currentContainers = [];

$stateFile = getServerFile('state');
$stateFile = $stateFile['file'];

$previousStates = $stateFile;
$currentStates  = dockerState();

if ($currentStates) {
    setServerFile('state', $currentStates);
} else {
    logger(CRON_STATE_LOG, 'STATE_FILE update skipped, $currentStates empty');
}

//-- GET CURRENT SETTINGS FILE
$settingsFile = getServerFile('settings');
$settingsFile = $settingsFile['file'];

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
foreach ($currentContainers as $currentContainer) {
    if (!in_array($currentContainer, $previousContainers)) {
        $containerHash = md5($currentContainer);

        $updates    = is_array($settingsFile['global']) && array_key_exists('updates', $settingsFile['global']) ? $settingsFile['global']['updates'] : 3; //-- CHECK ONLY FALLBACK
        $frequency  = is_array($settingsFile['global']) && array_key_exists('updatesFrequency', $settingsFile['global']) ? $settingsFile['global']['updatesFrequency'] : DEFAULT_CRON; //-- DAILY FALLBACK
        $settingsFile['containers'][$containerHash] = ['updates' => $updates, 'frequency' => $frequency];

        if (!$settingsFile['containers'][$containerHash]['disableNotifications']) {
            $added[] = ['container' => $currentContainer];
        }
    }
}
if ($added && $settingsFile['notifications']['triggers']['added']['active']) {
    $notify['state']['added'] = $added;
}
logger(CRON_STATE_LOG, 'Added containers: ' . json_encode($added, JSON_UNESCAPED_SLASHES));

//-- CHECK FOR REMOVED CONTAINERS
foreach ($previousContainers as $previousContainer) {
    if (!in_array($previousContainer, $currentContainers)) {
        $containerHash = md5($previousContainer);

        unset($settingsFile['containers'][$containerHash]);

        if (!$settingsFile['containers'][$containerHash]['disableNotifications']) {
            $removed[] = ['container' => $previousContainer];
        }
    }
}
if ($removed && $settingsFile['notifications']['triggers']['removed']['active']) {
    $notify['state']['removed'] = $removed;
}
logger(CRON_STATE_LOG, 'Removed containers: ' . json_encode($removed, JSON_UNESCAPED_SLASHES));

if ($added || $removed) {
    logger(CRON_STATE_LOG, 'updating settings file: ' . json_encode($settingsFile, JSON_UNESCAPED_SLASHES));
    setServerFile('settings', $settingsFile);
}

//-- CHECK FOR STATE CHANGED CONTAINERS
foreach ($currentStates as $currentState) {
    foreach ($previousStates as $previousState) {
        $containerHash = md5($currentState['Names']);

        if ($settingsFile['notifications']['triggers']['stateChange']['active'] && $currentState['Names'] == $previousState['Names'] && !$settingsFile['containers'][$containerHash]['disableNotifications']) {
            if ($previousState['State'] != $currentState['State']) {
                $notify['state']['changed'][] = ['container' => $currentState['Names'], 'previous' => $previousState['State'], 'current' => $currentState['State']];
            }
        }
    }
}
logger(CRON_STATE_LOG, 'State changed containers: ' . json_encode($notify['state']['changed'], JSON_UNESCAPED_SLASHES));

foreach ($currentStates as $currentState) {
    $containerHash = md5($currentState['Names']);

    //-- CHECK FOR HIGH CPU USAGE CONTAINERS
    if ($settingsFile['notifications']['triggers']['cpuHigh']['active'] && floatval($settingsFile['global']['cpuThreshold']) > 0 && !$settingsFile['containers'][$containerHash]['disableNotifications']) {
        if ($currentState['stats']['CPUPerc']) {
            $cpu        = floatval(str_replace('%', '', $currentState['stats']['CPUPerc']));
            $cpuAmount  = intval($settingsFile['global']['cpuAmount']);

            if ($cpuAmount > 0) {
                $cpu = number_format(($cpu / $cpuAmount), 2);
            }

            if ($cpu > floatval($settingsFile['global']['cpuThreshold'])) {
                $notify['usage']['cpu'][] = ['container' => $currentState['Names'], 'usage' => $cpu];
            }
        }
    }

    //-- CHECK FOR HIGH MEMORY USAGE CONTAINERS
    if ($settingsFile['notifications']['triggers']['memHigh']['active'] && floatval($settingsFile['global']['memThreshold']) > 0 && !$settingsFile['containers'][$containerHash]['disableNotifications']) {
        if ($currentState['stats']['MemPerc']) {
            $mem = floatval(str_replace('%', '', $currentState['stats']['MemPerc']));

            if ($mem > floatval($settingsFile['global']['memThreshold'])) {
                $notify['usage']['mem'][] = ['container' => $currentState['Names'], 'usage' => $mem];
            }
        }
    }
}
logger(CRON_STATE_LOG, 'CPU issue containers: ' . json_encode($notify['usage']['cpu'], JSON_UNESCAPED_SLASHES));
logger(CRON_STATE_LOG, 'Mem issue containers: ' . json_encode($notify['usage']['mem'], JSON_UNESCAPED_SLASHES));

if (!$previousStates) {
    $notify = [];
    logger(CRON_STATE_LOG, 'Notification skipped, $previousStates empty');
}

if (!$currentStates) {
    $notify = [];
    logger(CRON_STATE_LOG, 'Notification skipped, $currentStates empty');
}

if ($notify['state']) {
    //-- IF THEY USE THE SAME PLATFORM, COMBINE THEM
    if ($settingsFile['notifications']['triggers']['stateChange']['platform'] == $settingsFile['notifications']['triggers']['added']['platform'] && $settingsFile['notifications']['triggers']['stateChange']['platform'] == $settingsFile['notifications']['triggers']['removed']['platform']) {
        $payload = ['event' => 'state', 'changes' => $notify['state']['changed'], 'added' => $notify['state']['added'], 'removed' => $notify['state']['removed']];
        logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
        $notifications->notify($settingsFile['notifications']['triggers']['stateChange']['platform'], $payload);
    } else {
        if ($notify['state']['changed']) {
            $payload = ['event' => 'state', 'changes' => $notify['state']['changed']];
            logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
            $notifications->notify($settingsFile['notifications']['triggers']['stateChange']['platform'], $payload);
        }

        if ($notify['state']['added']) {
            $payload = ['event' => 'state', 'added' => $notify['state']['added']];
            logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
            $notifications->notify($settingsFile['notifications']['triggers']['added']['platform'], $payload);
        }

        if ($notify['state']['removed']) {
            $payload = ['event' => 'state', 'removed' => $notify['state']['removed']];
            logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
            $notifications->notify($settingsFile['notifications']['triggers']['removed']['platform'], $payload);
        }
    }
}

if ($notify['usage']) {
    //-- IF THEY USE THE SAME PLATFORM, COMBINE THEM
    if ($settingsFile['notifications']['triggers']['cpuHigh']['platform'] == $settingsFile['notifications']['triggers']['memHigh']['platform']) {
        $payload = ['event' => 'usage', 'cpu' => $notify['usage']['cpu'], 'cpuThreshold' => $settingsFile['global']['cpuThreshold'], 'mem' => $notify['usage']['mem'], 'memThreshold' => $settingsFile['global']['memThreshold']];
        logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
        $notifications->notify($settingsFile['notifications']['triggers']['cpuHigh']['platform'], $payload);
    } else {
        if ($notify['usage']['cpu']) {
            $payload = ['event' => 'usage', 'cpu' => $notify['usage']['cpu'], 'cpuThreshold' => $settingsFile['global']['cpuThreshold']];
            logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
            $notifications->notify($settingsFile['notifications']['triggers']['cpuHigh']['platform'], $payload);
        }

        if ($notify['usage']['mem']) {
            $payload = ['event' => 'usage', 'mem' => $notify['usage']['mem'], 'memThreshold' => $settingsFile['global']['memThreshold']];
            logger(CRON_STATE_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
            $notifications->notify($settingsFile['notifications']['triggers']['memHigh']['platform'], $payload);
        }
    }
}

echo date('c') . ' Cron: state <-' . "\n";
logger(CRON_STATE_LOG, 'run <-');