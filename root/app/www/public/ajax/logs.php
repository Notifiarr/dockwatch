<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $logFiles = [];

    $logDir = LOGS_PATH;
    if (is_dir($logDir)) {
        $dir = opendir($logDir);
        while ($group = readdir($dir)) {
            if ($group[0] != '.' && is_dir($logDir . $group)) {
                $groupDir = opendir($logDir . $group . '/');
                while ($log = readdir($groupDir)) {
                    if ($log[0] != '.' && !is_dir($log)) {
                        $logFiles[$group][] = ['name' => $log, 'size' => filesize($logDir . $group . '/' . $log)];
                    }
                }
                closedir($groupDir);
            }

            if (is_array($logFiles[$group])) {
                sort($logFiles[$group]);
            }
        }
        closedir($dir);
    }

    if (!$logFiles) {
        echo 'No log files have been generated yet.';
    } else {
        ?>
        <div class="container-fluid pt-4 px-4">
            <div class="bg-secondary rounded h-100 p-4">
                <div class="row">
                    <div class="col-sm-3">
                        <ul>
                            <?php 
                            foreach ($logFiles as $group => $groupLogs) { 
                                ?>
                                <i class="far fa-trash-alt" style="display: inline; color: red; cursor: pointer;" title="Delete all <?= $group ?> log files" onclick="purgeLogs('<?= $group ?>')"></i> <h4 style="display: inline;" class="mt-3 mb-0"><?= $group ?></h4><br>
                                <span class="small-text text-muted"><?= LOGS_PATH . $group ?>/</span>
                                <div class="mb-3" style="max-height: 300px; overflow: auto;">
                                <?php

                                if (!$groupLogs) {
                                    continue;
                                }
                                $rotated = [];

                                foreach ($groupLogs as $log) {
                                    if (str_contains($log['name'], '-')) {
                                        $rotated[] = $log;
                                        continue;
                                    }

                                    $logHash = md5($log['name']);
                                    ?>
                                    <li><span id="logList-<?= $logHash ?>" onclick="viewLog('<?= $group .'/'. $log['name'] ?>', '<?= $logHash ?>')" style="cursor: pointer;" class="text-info"><?= $log['name'] ?></span> (<span class="text-danger" style="cursor:pointer;" onclick="deleteLog('<?= $group .'/'. $log['name'] ?>')"><?= byteConversion($log['size']) ?></span>)</li>
                                    <?php
                                }

                                if ($rotated) {
                                    ?><hr><?php
                                    foreach ($rotated as $log) {
                                        $logHash = md5($log['name']);
                                        ?>
                                        <li><span id="logList-<?= $logHash ?>" onclick="viewLog('<?= $group .'/'. $log['name'] ?>', '<?= $logHash ?>')" style="cursor: pointer;" class="text-info"><?= $log['name'] ?></span> (<span class="text-danger" style="cursor:pointer;" onclick="deleteLog('<?= $group .'/'. $log['name'] ?>')"><?= byteConversion($log['size']) ?></span>)</li>
                                        <?php
                                    }
                                }
                                ?>
                                </div>
                                <?php
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="col-sm-9"><span id="logHeader"></span><pre id="logViewer" style="max-height: 500px; overflow: auto;">Select a log from the left to view</pre></div>
                </div>
            </div>
        </div>
        <?php
    }
}

if ($_POST['m'] == 'viewLog') {
    logger(SYSTEM_LOG, 'View log: ' . $_POST['name']);

    $apiResponse = apiRequest('viewLog', ['name' => $_POST['name']]);

    if ($apiResponse['code'] == 200) {
        $result = json_decode($apiResponse['response']['result'], true);
    } else {
        $error = 'Failed to get log from server ' . ACTIVE_SERVER_NAME;
    }

    echo json_encode(['error' => $error, 'header' => $result['header'], 'log' => $result['log'], 'server' => ACTIVE_SERVER_NAME]);
}

if ($_POST['m'] == 'purgeLogs') {
    logger(SYSTEM_LOG, 'Purge logs: ' . $_POST['group']);

    $apiResponse = apiRequest('purgeLogs', [], ['group' => $_POST['group']]);
    logger(UI_LOG, 'purgeLogs:' . json_encode($apiResponse));
}

if ($_POST['m'] == 'deleteLog') {
    logger(SYSTEM_LOG, 'Delete log: ' . $_POST['log']);

    $apiResponse = apiRequest('deleteLog', [], ['log' => $_POST['log']]);
    logger(UI_LOG, 'deleteLog:' . json_encode($apiResponse));
}