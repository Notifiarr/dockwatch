<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $logFiles = $cronLogs = [];

    $logDir = LOGS_PATH . 'crons';
    if (is_dir($logDir)) {
        $dir = opendir($logDir);
        while ($log = readdir($dir)) {
            if ($log[0] != '.' && !is_dir($log)) {
                $cronLogs[] = ['name' => $log, 'size' => filesize($logDir . $log)];
            }
        }
        closedir($dir);

        if ($cronLogs) {
            $logFiles['crons'] = $cronLogs;
        }
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
                                echo '<h4>' . $group . '</h4>';

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
    $log        = file(LOGS_PATH . $_POST['name']);
    $header     = 'Lines: ' . count($log);
    
    foreach ($log as $index => $line) {
        $content .= str_pad(($index + 1), strlen(count($log)), ' ', STR_PAD_RIGHT) .' | '. $line;
    }

    echo json_encode(['header' => $header, 'log' => $content]);
}