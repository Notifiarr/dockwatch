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

    //-- CHECK FOR LOGIN FAILURE FILES
    $dir = opendir(APP_DATA_PATH);
    while ($file = readdir($dir)) {
        if (str_contains($file, 'login_failures')) {
            $logFiles['login failures'][] = ['name' => $file, 'size' => filesize(APP_DATA_PATH . $file)];
        }
    }
    closedir($dir);

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
        <ol class="breadcrumb rounded p-1 ps-2">
            <li class="breadcrumb-item"><a href="#" onclick="initPage('overview')"><?= $_SESSION['activeServerName'] ?></a><span class="ms-2">↦</span></li>
            <li class="breadcrumb-item active" aria-current="page">Logs</li>
        </ol>
        <div class="bg-secondary rounded p-4">
            <div class="row">
                <div class="col-sm-3">
                    <ul>
                        <?php
                        foreach ($logFiles as $group => $groupLogs) {
                            ?>
                            <h4 style="display: inline;" class="mt-3 mb-0"><?= $group ?></h4> <i class="far fa-trash-alt" style="display: inline; color: red; cursor: pointer;" title="Delete all <?= $group ?> log files" onclick="purgeLogs('<?= $group ?>')"></i><br>
                            <?php if ($group == 'login failures') { ?>
                                <span class="small-text text-muted"><?= APP_DATA_PATH ?></span>
                            <?php } else { ?>
                                <span class="small-text text-muted"><?= LOGS_PATH . $group ?>/</span>
                            <?php } ?>
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
                                $group = str_contains_any($group, ['login failures']) ? '' : $group . '/';
                                ?>
                                <li>
                                    <span id="logList-<?= $logHash ?>" onclick="viewLog('<?= $group . $log['name'] ?>', '<?= $logHash ?>')" style="cursor: pointer;" class="text-secondary"><?= $log['name'] ?></span> (<?= byteConversion($log['size']) ?>)
                                    <i class="far fa-trash-alt text-danger" style="display: inline; cursor: pointer;" title="Delete <?= $log['name'] ?>" onclick="deleteLog('<?= $group . $log['name'] ?>')"></i>
                                    <i class="fas fa-file-download text-primary" style="display: inline; cursor: pointer;" title="Download <?= $log['name'] ?>" onclick="downloadLog('<?= $group . $log['name'] ?>')"></i>
                                </li>
                                <?php
                            }

                            if ($rotated) {
                                ?><hr><?php
                                foreach ($rotated as $log) {
                                    $logHash = md5($log['name']);
                                    ?>
                                    <li>
                                        <span id="logList-<?= $logHash ?>" onclick="viewLog('<?= $group .'/'. $log['name'] ?>', '<?= $logHash ?>')" style="cursor: pointer;" class="text-secondary"><?= $log['name'] ?></span> (<?= byteConversion($log['size']) ?>)
                                        <i class="far fa-trash-alt text-danger" style="display: inline; cursor: pointer;" title="Delete <?= $log['name'] ?>" onclick="deleteLog('<?= $group .'/'. $log['name'] ?>')"></i>
                                        <i class="fas fa-file-download text-primary" style="display: inline; cursor: pointer;" title="Download <?= $log['name'] ?>" onclick="downloadLog('<?= $group . $log['name'] ?>')"></i>
                                    </li>
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

    $apiRequest = apiRequest('server/log', ['name' => $_POST['name']]);

    if ($apiRequest['code'] == 200) {
        $result = json_decode($apiRequest['result'], true);
    } else {
        $error = 'Failed to get log from server ' . ACTIVE_SERVER_NAME;
    }

    $log = str_contains($_POST['name'], 'migrations') ? $result['log'] : htmlspecialchars($result['log']);
    echo json_encode(['error' => $error, 'header' => $result['header'], 'log' => $log, 'server' => ACTIVE_SERVER_NAME]);
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
