<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $logFiles = getLogFiles();

    if (!$logFiles) {
        echo 'No log files have been generated yet.';
    } else {
        ?>
        <div class="bg-secondary rounded p-4">
            <div class="row">
                <div class="col-sm-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <?php if ($settingsTable['sseEnabled']) { ?>
                            <span class="text-muted small">Auto-refresh: <span id="auto-refresh-status">3s</span></span>
                            <button id="auto-refresh-toggle" class="btn btn-sm btn-outline-success border-light" onclick="toggleLogAutoRefresh()" title="Toggle auto-refresh">
                                <i class="fas fa-sync"></i>
                            </button>
                        <?php } ?>
                    </div>
                    <div id="log-list">
                        <?= renderLogList() ?>
                    </div>
                </div>
                <div class="col-sm-9">
                    <span id="logHeader"></span>
                    <pre class="bg-dark primary p-3 rounded" id="logViewer" style="color: white; max-height: 600px; overflow: auto; white-space: no-wrap;">Select a log from the left to view</pre>
                </div>
            </div>
        </div>
        <?php
    }
}

if ($_POST['m'] == 'viewLog') {
    logger(SYSTEM_LOG, 'View log: ' . $_POST['name']);
    echo json_encode(getLogView($_POST['name']));
}

if ($_POST['m'] == 'purgeLogs') {
    logger(SYSTEM_LOG, 'Purge logs: ' . $_POST['group']);

    $apiRequest = apiRequest('server/log/purge', [], ['group' => $_POST['group']]);
    logger(UI_LOG, 'purgeLogs:' . json_encode($apiRequest));
}

if ($_POST['m'] == 'deleteLog') {
    logger(SYSTEM_LOG, 'Delete log: ' . $_POST['log']);

    $apiRequest = apiRequest('server/log/delete', [], ['log' => $_POST['log']]);
    logger(UI_LOG, 'deleteLog:' . json_encode($apiRequest));
}
