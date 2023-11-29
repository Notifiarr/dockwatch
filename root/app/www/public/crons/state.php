<?php

/*
----------------------------------
 ------  Created: 111723   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

logger(SYSTEM_LOG, 'Cron: running housekeeper', 'info');

$logfile = LOGS_PATH . 'crons/state-' . date('Ymd_Hi') . '.log';
logger($logfile, 'Cron run started');
echo 'Cron run started: state' . "\n";

if ($settingsFile['tasks']['state']['disabled']) {
    logger($logfile, 'Cron run stopped: disabled in tasks menu');
    echo 'Cron run cancelled: disabled in tasks menu' . "\n";
    exit();
}

$notify = $added = $removed = $previousStates = $currentStates = $previousContainers = $currentContainers = [];
$previousStates = $stateFile;
$currentStates  = dockerState();
if ($currentStates) {
    setServerFile('state', $currentStates);
} else {
    logger($logfile, 'STATE_FILE update skipped, $currentStates empty');
}

logger($logfile, 'previousStates: ' . json_encode($previousStates));
logger($logfile, 'currentStates: ' . json_encode($currentStates));

foreach ($previousStates as $previousState) {
    $previousContainers[] = $previousState['Names'];
}

foreach ($currentStates as $currentState) {
    $currentContainers[] = $currentState['Names'];
}

logger($logfile, 'previousContainers: ' . json_encode($previousContainers));
logger($logfile, 'currentContainers: ' . json_encode($currentContainers));

//-- CHECK FOR ADDED CONTAINERS
foreach ($currentContainers as $currentContainer) {
    if (!in_array($currentContainer, $previousContainers)) {
        if ($settingsFile['notifications']['triggers']['added']['active']) {
            $added[] = ['container' => $currentContainer];
        }

        $updates    = array_key_exists('updates', $settingsFile['global']) ? $settingsFile['global']['updates'] : 3; //-- CHECK ONLY FALLBACK
        $frequency  = array_key_exists('updatesFrequency', $settingsFile['global']) ? $settingsFile['global']['updatesFrequency'] : '1d'; //-- DAILY FALLBACK
        $hour       = array_key_exists('updatesHour', $settingsFile['global']) ? $settingsFile['global']['updatesHour'] : 3; //-- 3AM FALLBACK
        $settingsFile['containers'][md5($currentContainer)] = ['updates' => $updates, 'frequency' => $frequency, 'hour' => $hour];
    }
}
if ($added) {
    $notify['state']['added'] = $added;
}
logger($logfile, 'Added containers: ' . json_encode($notify['state']['added']));

//-- CHECK FOR REMOVED CONTAINERS
foreach ($previousContainers as $previousContainer) {
    if (!in_array($previousContainer, $currentContainers)) {
        if ($settingsFile['notifications']['triggers']['removed']['active']) {
            $removed[] = ['container' => $previousContainer];
        }
        unset($settingsFile['containers'][md5($currentContainer)]);
    }
}
if ($removed) {
    $notify['state']['removed'] = $removed;
}
logger($logfile, 'Removed containers: ' . json_encode($notify['state']['removed']));

setServerFile('settings', $settings);

//-- CHECK FOR STATE CHANGED CONTAINERS
foreach ($currentStates as $currentState) {
    foreach ($previousStates as $previousState) {
        if ($settingsFile['notifications']['triggers']['stateChange']['active'] && $currentState['Names'] == $previousState['Names']) {
            if ($previousState['State'] != $currentState['State']) {
                $notify['state']['changed'][] = ['container' => $currentState['Names'], 'previous' => $previousState['State'], 'current' => $currentState['State']];
            }
        }
    }
}
logger($logfile, 'State changed containers: ' . json_encode($notify['state']['changed']));

foreach ($currentStates as $currentState) {
    //-- CHECK FOR HIGH CPU USAGE CONTAINERS
    if ($settingsFile['notifications']['triggers']['cpuHigh']['active'] && floatval($settingsFile['global']['cpuThreshold']) > 0) {
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
    if ($settingsFile['notifications']['triggers']['memHigh']['active'] && floatval($settingsFile['global']['memThreshold']) > 0) {
        if ($currentState['stats']['MemPerc']) {
            $mem = floatval(str_replace('%', '', $currentState['stats']['MemPerc']));
            if ($mem > floatval($settingsFile['global']['memThreshold'])) {
                $notify['usage']['mem'][] = ['container' => $currentState['Names'], 'usage' => $mem];
            }
        }
    }
}
logger($logfile, 'CPU issue containers: ' . json_encode($notify['usage']['cpu']));
logger($logfile, 'Mem issue containers: ' . json_encode($notify['usage']['mem']));

if (!$previousStates) {
    $notify = [];
    logger($logfile, 'Notification skipped, $previousStates empty');
}

if (!$currentStates) {
    $notify = [];
    logger($logfile, 'Notification skipped, $currentStates empty');
}

if ($notify['state']) {
    //-- IF THEY USE THE SAME PLATFORM, COMBINE THEM
    if ($settingsFile['notifications']['triggers']['stateChange']['platform'] == $settingsFile['notifications']['triggers']['added']['platform'] && $settingsFile['notifications']['triggers']['stateChange']['platform'] == $settingsFile['notifications']['triggers']['removed']['platform']) {
        $payload = ['event' => 'state', 'changes' => $notify['state']['changed'], 'added' => $notify['state']['added'], 'removed' => $notify['state']['removed']];
        logger($logfile, 'Notification payload: ' . json_encode($payload));
        $notifications->notify($settingsFile['notifications']['triggers']['stateChange']['platform'], $payload);
    } else {
        if ($notify['state']['changed']) {
            $payload = ['event' => 'state', 'changes' => $notify['state']['changed']];
            logger($logfile, 'Notification payload: ' . json_encode($payload));
            $notifications->notify($settingsFile['notifications']['triggers']['stateChange']['platform'], $payload);
        }

        if ($notify['state']['added']) {
            $payload = ['event' => 'state', 'added' => $notify['state']['added']];
            logger($logfile, 'Notification payload: ' . json_encode($payload));
            $notifications->notify($settingsFile['notifications']['triggers']['added']['platform'], $payload);
        }

        if ($notify['state']['removed']) {
            $payload = ['event' => 'state', 'removed' => $notify['state']['removed']];
            logger($logfile, 'Notification payload: ' . json_encode($payload));
            $notifications->notify($settingsFile['notifications']['triggers']['removed']['platform'], $payload);
        }
    }
}

if ($notify['usage']) {
    //-- IF THEY USE THE SAME PLATFORM, COMBINE THEM
    if ($settingsFile['notifications']['triggers']['cpuHigh']['platform'] == $settingsFile['notifications']['triggers']['memHigh']['platform']) {
        $payload = ['event' => 'usage', 'cpu' => $notify['usage']['cpu'], 'cpuThreshold' => $settingsFile['global']['cpuThreshold'], 'mem' => $notify['usage']['mem'], 'memThreshold' => $settingsFile['global']['memThreshold']];
        logger($logfile, 'Notification payload: ' . json_encode($payload));
        $notifications->notify($settingsFile['notifications']['triggers']['cpuHigh']['platform'], $payload);
    } else {
        if ($notify['usage']['cpu']) {
            $payload = ['event' => 'usage', 'cpu' => $notify['usage']['cpu'], 'cpuThreshold' => $settingsFile['global']['cpuThreshold']];
            logger($logfile, 'Notification payload: ' . json_encode($payload));
            $notifications->notify($settingsFile['notifications']['triggers']['cpuHigh']['platform'], $payload);
        }

        if ($notify['usage']['mem']) {
            $payload = ['event' => 'usage', 'mem' => $notify['usage']['mem'], 'memThreshold' => $settingsFile['global']['memThreshold']];
            logger($logfile, 'Notification payload: ' . json_encode($payload));
            $notifications->notify($settingsFile['notifications']['triggers']['memHigh']['platform'], $payload);
        }
    }
}

logger($logfile, 'Cron run finished');
