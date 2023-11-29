<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

logger(SYSTEM_LOG, 'Cron: running housekeeper', 'info');

$logfile = LOGS_PATH . 'crons/housekeeper-' . date('Ymd_Hi') . '.log';
logger($logfile, 'Cron run started');
echo 'Cron run started: housekeeper' . "\n";

if ($settingsFile['tasks']['housekeeping']['disabled']) {
    logger($logfile, 'Cron run stopped: disabled in tasks menu');
    echo 'Cron run cancelled: disabled in tasks menu' . "\n";
    exit();
}

//-- MAKE A BACKUP
$settingsFile = str_replace(APP_DATA_PATH, '', SETTINGS_FILE);
if (!file_exists(BACKUP_PATH . $settingsFile)) {
    $dir = opendir(APP_DATA_PATH);
    while ($item = readdir($dir)) {
        if (strpos($item, '.json') !== false) {
            $copy = false;

            if (!file_exists(BACKUP_PATH . $item)) {
                $copy = true;
            } else {
                $backupDate = date('Ymd', filemtime(BACKUP_PATH . $item));
                if ($backupDate != date('Ymd')) {
                    $copy = true;
                }
            }

            if ($copy) {
                logger($logfile, 'backup file \'' . APP_DATA_PATH . $item . '\' to \'' . BACKUP_PATH . $item . '\'');
                copy(APP_DATA_PATH . $item, BACKUP_PATH . $item);
            }
        }
    }
    closedir($dir);
}

//-- LOG FILE CLEANUP (DAILY @ MIDNIGHT)
if (date('H') == 0 && date('i') <= 5) {
    logger($logfile, 'Cron log file cleanup (daily @ midnight)');
    $cronLength = $settingsFile['global']['cronLogLength'] <= 1 ? 1 : $settingsFile['global']['cronLogLength'];
    logger($logfile, 'Allowed cron log age: ' . $cronLength);
    $logDir = LOGS_PATH . 'crons/';
    $dir    = opendir($logDir);
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
    $notificationLength = $settingsFile['global']['notificationLogLength'] <= 1 ? 1 : $settingsFile['global']['notificationLogLength'];
    logger($logfile, 'Allowed notification log age: ' . $notificationLength);
    $logDir = LOGS_PATH . 'notifications/';
    $dir    = opendir($logDir);
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

    logger($logfile, 'System log file cleanup (daily @ midnight)');
    $notificationLength = 1;
    logger($logfile, 'Allowed notification log age: ' . $notificationLength);
    $logDir = LOGS_PATH . 'system/';
    $dir    = opendir($logDir);
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
