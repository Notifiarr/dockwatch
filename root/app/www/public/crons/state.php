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

$logfile = LOGS_PATH . 'crons/cron-state-' . date('Ymd') . '.log';
logger($logfile, 'Cron run started');
echo 'Cron run started: state' . "\n";

$settings       = getFile(SETTINGS_FILE);
$previousStates = getFile(STATE_FILE);
$currentStates  = dockerState();
setFile(STATE_FILE, $currentStates);
$notify = $added = $removed = [];

//-- CHECK FOR ADDED CONTAINERS
$matches = [];
foreach ($previousStates as $previousIndex => $previousState) {
    $found = false;
    foreach ($currentStates as $currentIndex => $currentState) {
        if ($previousState['Names'] == $currentState['Names']) {
            $found = true;
            break;
        }
    }
    if ($settings['notifications']['triggers']['added']['active'] && !in_array($currentState['Names'], $matches) && !$found) {
        $added[]    = ['container' => $currentState['Names']];
        $matches[]  = $currentState['Names'];
    }
}

if ($added) {
    $notify['state']['added'] = $added;
}
logger($logfile, 'Added containers: ' . json_encode($notify['state']['added']));

//-- CHECK FOR REMOVED CONTAINERS
$matches = [];
foreach ($currentStates as $currentIndex => $currentState) {
    $found = false;
    foreach ($previousStates as $previousIndex => $previousState) {
        if ($previousState['Names'] == $currentState['Names']) {
            $found = true;
            break;
        }
    }
    if ($settings['notifications']['triggers']['removed']['active'] && !in_array($currentState['Names'], $matches) && !$found) {
        $removed[]  = ['container' => $currentState['Names']];
        $matches[]  = $currentState['Names'];
    }
}

if ($removed) {
    $notify['state']['removed'] = $removed;
}
logger($logfile, 'Removed containers: ' . json_encode($notify['state']['removed']));

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
            $cpu = floatval(str_replace('%', '', $currentState['stats']['CPUPerc']));
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
