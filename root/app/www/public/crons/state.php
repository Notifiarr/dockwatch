<?php

/*
----------------------------------
 ------  Created: 111723   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

logger($systemLog, 'Cron: running housekeeper', 'info');

$logfile = LOGS_PATH . 'crons/state-' . date('Ymd_Hi') . '.log';
logger($logfile, 'Cron run started');
echo 'Cron run started: state' . "\n";

$notify = $added = $removed = $previousStates = $currentStates = $previousContainers = $currentContainers = [];
$settings       = getFile(SETTINGS_FILE);
$previousStates = getFile(STATE_FILE);
$currentStates  = dockerState();
if ($currentStates) {
    setFile(STATE_FILE, $currentStates);
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
        if ($settings['notifications']['triggers']['added']['active']) {
            $added[] = ['container' => $currentContainer];
        }

        $updates    = array_key_exists('updates', $settings['global']) ? $settings['global']['updates'] : 3; //-- CHECK ONLY FALLBACK
        $frequency  = array_key_exists('updatesFrequency', $settings['global']) ? $settings['global']['updatesFrequency'] : '1d'; //-- DAILY FALLBACK
        $hour       = array_key_exists('updatesHour', $settings['global']) ? $settings['global']['updatesHour'] : 3; //-- 3AM FALLBACK
        $settings['containers'][md5($currentContainer)] = ['updates' => $updates, 'frequency' => $frequency, 'hour' => $hour];
    }
}
if ($added) {
    $notify['state']['added'] = $added;
}
logger($logfile, 'Added containers: ' . json_encode($notify['state']['added']));

//-- CHECK FOR REMOVED CONTAINERS
foreach ($previousContainers as $previousContainer) {
    if (!in_array($previousContainer, $currentContainers)) {
        if ($settings['notifications']['triggers']['removed']['active']) {
            $removed[] = ['container' => $previousContainer];
        }
        unset($settings['containers'][md5($currentContainer)]);
    }
}
if ($removed) {
    $notify['state']['removed'] = $removed;
}
logger($logfile, 'Removed containers: ' . json_encode($notify['state']['removed']));

setFile(SETTINGS_FILE, $settings);

//-- CHECK FOR STATE CHANGED CONTAINERS
foreach ($currentStates as $currentState) {
    foreach ($previousStates as $previousState) {
        if ($settings['notifications']['triggers']['stateChange']['active'] && $currentState['Names'] == $previousState['Names']) {
            if ($previousState['State'] != $currentState['State']) {
                $notify['state']['changed'][] = ['container' => $currentState['Names'], 'previous' => $previousState['State'], 'current' => $currentState['State']];
            }
        }
    }
}
logger($logfile, 'State changed containers: ' . json_encode($notify['state']['changed']));

foreach ($currentStates as $currentState) {
    //-- CHECK FOR HIGH CPU USAGE CONTAINERS
    if ($settings['notifications']['triggers']['cpuHigh']['active'] && floatval($settings['global']['cpuThreshold']) > 0) {
        if ($currentState['stats']['CPUPerc']) {
            $cpu        = floatval(str_replace('%', '', $currentState['stats']['CPUPerc']));
            $cpuAmount  = intval($settings['global']['cpuAmount']);

            if ($cpuAmount > 0) {
                $cpu = number_format(($cpu / $cpuAmount), 2);
            }

            if ($cpu > floatval($settings['global']['cpuThreshold'])) {
                $notify['usage']['cpu'][] = ['container' => $currentState['Names'], 'usage' => $cpu];
            }
        }
    }

    //-- CHECK FOR HIGH MEMORY USAGE CONTAINERS
    if ($settings['notifications']['triggers']['memHigh']['active'] && floatval($settings['global']['memThreshold']) > 0) {
        if ($currentState['stats']['MemPerc']) {
            $mem = floatval(str_replace('%', '', $currentState['stats']['MemPerc']));
            if ($mem > floatval($settings['global']['memThreshold'])) {
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
    if ($settings['notifications']['triggers']['stateChange']['platform'] == $settings['notifications']['triggers']['added']['platform'] && $settings['notifications']['triggers']['stateChange']['platform'] == $settings['notifications']['triggers']['removed']['platform']) {
        $payload = ['event' => 'state', 'changes' => $notify['state']['changed'], 'added' => $notify['state']['added'], 'removed' => $notify['state']['removed']];
        logger($logfile, 'Notification payload: ' . json_encode($payload));
        $notifications->notify($settings['notifications']['triggers']['stateChange']['platform'], $payload);
    } else {
        if ($notify['state']['changed']) {
            $payload = ['event' => 'state', 'changes' => $notify['state']['changed']];
            logger($logfile, 'Notification payload: ' . json_encode($payload));
            $notifications->notify($settings['notifications']['triggers']['stateChange']['platform'], $payload);
        }

        if ($notify['state']['added']) {
            $payload = ['event' => 'state', 'added' => $notify['state']['added']];
            logger($logfile, 'Notification payload: ' . json_encode($payload));
            $notifications->notify($settings['notifications']['triggers']['added']['platform'], $payload);
        }

        if ($notify['state']['removed']) {
            $payload = ['event' => 'state', 'removed' => $notify['state']['removed']];
            logger($logfile, 'Notification payload: ' . json_encode($payload));
            $notifications->notify($settings['notifications']['triggers']['removed']['platform'], $payload);
        }
    }
}

if ($notify['usage']) {
    //-- IF THEY USE THE SAME PLATFORM, COMBINE THEM
    if ($settings['notifications']['triggers']['cpuHigh']['platform'] == $settings['notifications']['triggers']['memHigh']['platform']) {
        $payload = ['event' => 'usage', 'cpu' => $notify['usage']['cpu'], 'cpuThreshold' => $settings['global']['cpuThreshold'], 'mem' => $notify['usage']['mem'], 'memThreshold' => $settings['global']['memThreshold']];
        logger($logfile, 'Notification payload: ' . json_encode($payload));
        $notifications->notify($settings['notifications']['triggers']['cpuHigh']['platform'], $payload);
    } else {
        if ($notify['usage']['cpu']) {
            $payload = ['event' => 'usage', 'cpu' => $notify['usage']['cpu'], 'cpuThreshold' => $settings['global']['cpuThreshold']];
            logger($logfile, 'Notification payload: ' . json_encode($payload));
            $notifications->notify($settings['notifications']['triggers']['cpuHigh']['platform'], $payload);
        }

        if ($notify['usage']['mem']) {
            $payload = ['event' => 'usage', 'mem' => $notify['usage']['mem'], 'memThreshold' => $settings['global']['memThreshold']];
            logger($logfile, 'Notification payload: ' . json_encode($payload));
            $notifications->notify($settings['notifications']['triggers']['memHigh']['platform'], $payload);
        }
    }
}

logger($logfile, 'Cron run finished');
