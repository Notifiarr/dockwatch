<?php

/*
----------------------------------
 ------  Created: 051124   ------
 ------  Austin Best	   ------
----------------------------------
*/

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

require 'loader.php';

if (IS_MAINTENANCE) {
    echo 'data: ' . json_encode([]) . "\n\n";
    flush();
    exit;
}

if (empty($_SESSION['authenticated'])) {
    http_response_code(401);
    echo 'data: ' . json_encode([]) . "\n\n";
    flush();
    exit;
}

//-- NAMED EVENTS: ?events=containers,logList,log&log=...
//-- ANSWERS ONCE AND EXITS, THE BROWSER'S EVENTSOURCE RECONNECTS ON ITS OWN
//-- AN EVENT IS ONLY SENT WHEN ITS CONTENT CHANGED SINCE THE LAST-EVENT-ID THE BROWSER SENDS BACK
//-- SSE RUNS WITHOUT THE LOADER UI INIT, ACTIVE SERVER NAME LIVES IN THE SESSION AND THE DATABASE IS ONLY NEEDED BY APIREQUEST
$database = new Database();
define('ACTIVE_SERVER_NAME', $_SESSION['activeServerName'] ?? 'local');

//-- ONLY LOGS THE LOG LIST KNOWS ABOUT, THE NAME ENDS UP IN API REQUESTS
$requested = array_intersect(explode(',', $_GET['events'] ?? ''), ['containers', 'logList', 'log']);

$settingsTable = apiRequestLocal('database/settings');

$events = [];

//-- THE SSE TASK CHECKBOX (sseEnabled), THE CRON HAS THE SAME GATE
if ($settingsTable['sseEnabled']) {
    $events['containers'] = function () {
        //-- CONTAINER ROWS FROM THE SSE CRON, THE CRON KEEPS PRODUCING THE FILE, THE CHECKSUM HANDLES CHANGE DETECTION
        $sseFile = file_exists(SSE_FILE) ? json_decode((string) file_get_contents(SSE_FILE), true) : [];
        unset($sseFile['updated'], $sseFile['pushed']);

        return $sseFile ?: [];
    };
}

$events['logList'] = fn() => renderLogList();

if (in_array('log', $requested)) {
    $log      = '';
    $logFiles = getLogFiles();

    foreach ($logFiles as $group => $groupLogs) {
        $prefix = str_contains_any($group, ['login failures']) ? '' : $group . '/';

        foreach ($groupLogs as $groupLog) {
            if ($prefix . $groupLog['name'] === ($_GET['log'] ?? '')) {
                $log = $prefix . $groupLog['name'];
            }
        }
    }

    if ($log) {
        $events['log'] = fn() => getLogView($log);
    }
}

$lastIds = json_decode($_SERVER['HTTP_LAST_EVENT_ID'] ?? '', true) ?: [];
$ids     = [];
$changed = [];

foreach ($requested as $event) {
    if (!isset($events[$event])) {
        continue;
    }

    $changed[$event] = $events[$event]();
    $ids[$event]     = hash('crc32b', serialize($changed[$event]));

    if ($ids[$event] === ($lastIds[$event] ?? '')) {
        unset($changed[$event]);
    }
}

echo "retry: 3000\n\n";

foreach ($changed as $event => $data) {
    echo 'id: ' . json_encode($ids) . "\nevent: " . $event . "\ndata: " . json_encode($data) . "\n\n";
}

flush();
