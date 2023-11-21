<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

$logfile = LOGS_PATH . 'crons/cron-housekeeper-' . date('Ymd') . '.log';
logger($logfile, 'Cron run started');
echo 'Cron run started: housekeeper' . "\n";

$settings = getFile(SETTINGS_FILE);

//-- LOG FILE CLEANUP (DAILY @ MIDNIGHT)
if (date('H') == 0 && date('i') <= 5) {
    logger($logfile, 'Cron log file cleanup (daily @ midnight)');
    $cronLength = $settings['global']['cronLogLength'] <= 1 ? 1 : $settings['global']['cronLogLength'];
    logger($logfile, 'Allowed cron log age: ' . $cronLength);
    $logDir = LOGS_PATH . 'crons/';
    $dir = opendir($logDir);
    while ($log = readdir($dir)) {
        if ($log[0] != '.' && !is_dir($log)) {
            $daysBetween = daysBetweenDates(date('Ymd', filemtime($logDir . $log)), date('Ymd'));
            logger($logfile, 'logfile: ' . $logDir . $log . ', age: ' . $daysBetween);

            if ($daysBetween > $cronLength) {
                logger($logfile, 'removing logfile');
                unlink($logDir . $log);
            }
        }
    }
    closedir($dir);

    logger($logfile, 'Notification log file cleanup (daily @ midnight)');
    $notificationLength = $settings['global']['notificationLogLength'] <= 1 ? 1 : $settings['global']['notificationLogLength'];
    logger($logfile, 'Allowed notification log age: ' . $notificationLength);
    $logDir = LOGS_PATH . 'notifications/';
    $dir = opendir($logDir);
    while ($log = readdir($dir)) {
        if ($log[0] != '.' && !is_dir($log)) {
            $daysBetween = daysBetweenDates(date('Ymd', filemtime($logDir . $log)), date('Ymd'));
            logger($logfile, 'logfile: ' . $logDir . $log . ', age: ' . $daysBetween);

            if ($daysBetween > $notificationLength) {
                logger($logfile, 'removing logfile');
                unlink($logDir . $log);
            }
        }
    }
    closedir($dir);
}

logger($logfile, 'Cron run finished');
