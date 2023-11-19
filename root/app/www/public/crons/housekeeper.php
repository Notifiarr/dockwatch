<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

$logfile = ABSOLUTE_PATH . LOGS_PATH . 'cron-housekeeper-' . date('Ymd') . '.log';
logger($logfile, 'Cron run started');
echo 'Cron run started: housekeeper' . "\n";

//-- LOG FILE CLEANUP (DAILY @ MIDNIGHT)
logger($logfile, 'Checking time: Log file cleanup (daily @ midnight)');
if (date('H') == 0) {
    $logDir = ABSOLUTE_PATH . LOGS_PATH;
    $dir = opendir($logDir);
    while ($log = readdir($dir)) {
        if ($log[0] != '.' && !is_dir($log)) {
            if (date('Ymd', filemtime($logDir . $log)) != date('Ymd')) {
                logger($logfile, 'Removing logfile: ' . $logDir . $log);
                unlink($logDir . $log);
            }
        }
    }
    closedir($dir);
}

logger($logfile, 'Cron run finished');
