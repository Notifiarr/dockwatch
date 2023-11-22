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
                                echo '<h4 class="mt-3 mb-0">' . $group . '</h4>';
                                echo '<span class="small-text text-muted">' . LOGS_PATH . $group . '/</span>';

                                if (!$groupLogs) {
                                    continue;
                                }

                                foreach ($groupLogs as $log) {
                                    $logHash = md5($log['name']);
                                    ?>
                                    <li id="logList-<?= $logHash ?>" onclick="viewLog('<?= $group .'/'. $log['name'] ?>', '<?= $logHash ?>')" style="cursor: pointer;" class="text-info"><?= $log['name'] ?> (<?= byteConversion($log['size']) ?>)</li>
                                    <?php
                                }
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
    $error = $header = $content = '';
    if (file_exists(LOGS_PATH . $_POST['name'])) {
        $log        = file(LOGS_PATH . $_POST['name']);
        $header     = 'Lines: ' . count($log);
        
        foreach ($log as $index => $line) {
            $content .= str_pad(($index + 1), strlen(count($log)), ' ', STR_PAD_RIGHT) .' | '. $line;
        }
    } else {
        $error = 'Selected log file not found';
    }
    echo json_encode(['header' => $header, 'log' => str_replace('[ERROR]', '<span class="text-danger">[ERROR]</span>', $content), 'error' => $error]);
}