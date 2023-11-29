<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

logger(SYSTEM_LOG, 'Cron: running housekeeper');
logger(CRON_HOUSEKEEPER_LOG, 'Cron run started');
echo 'Cron run started: housekeeper' . "\n";

if ($settingsFile['tasks']['housekeeping']['disabled']) {
    logger(CRON_HOUSEKEEPER_LOG, 'Cron run stopped: disabled in tasks menu');
    echo 'Cron run cancelled: disabled in tasks menu' . "\n";
    exit();
}

//-- LOG FILE CLEANUP (DAILY @ MIDNIGHT)
//if (date('H') == 0 && date('i') <= 5) {
if (date('H') == 10) {
    $cleanup    = [
                    [
                        'crons'         => [
                                            'message'   => 'Cron log file cleanup (daily @ midnight)',
                                            'length'    => ($settingsFile['global']['cronLogLength'] <= 1 ? 1 : $settingsFile['global']['cronLogLength'])
                                            ]
                    ],[
                        'notifications' => [
                                            'message'   => 'Notification log file cleanup (daily @ midnight)',
                                            'length'    => ($settingsFile['global']['notificationLogLength'] <= 1 ? 1 : $settingsFile['global']['notificationLogLength'])
                                            ]
                    ],[
                        'system'        => [
                                            'message'   => 'System log file cleanup (daily @ midnight)',
                                            'length'    => 1
                                            ]
                    ],[
                        'ui'            => [
                                            'message'   => 'UI log file cleanup (daily @ midnight)',
                                            'length'    => ($settingsFile['global']['uiLogLength'] <= 1 ? 1 : $settingsFile['global']['uiLogLength'])
                                            ]
                     ],[
                        'api'           => [
                                            'message'   => 'API log file cleanup (daily @ midnight)',
                                            'length'    => ($settingsFile['global']['apiLogLength'] <= 1 ? 1 : $settingsFile['global']['apiLogLength'])
                                            ]
                    ]
                ];

    foreach ($cleanup as $folders) {
        foreach ($folders as $dir => $settings) {
            logger(CRON_HOUSEKEEPER_LOG, $settings['message']);
            logger(CRON_HOUSEKEEPER_LOG, 'Allowed ' . $dir . ' log age: ' . $settings['length']);

            $thisDir    = LOGS_PATH . $dir . '/';
            $dir        = opendir($thisDir);
            while ($log = readdir($dir)) {
                if ($log[0] != '.' && !is_dir($log)) {
                    $daysBetween = daysBetweenDates(date('Ymd', filemtime($thisDir . $log)), date('Ymd'));
                    logger(CRON_HOUSEKEEPER_LOG, 'logfile: ' . $thisDir . $log . ', age: ' . $daysBetween);
        
                    if ($daysBetween > $settings['length']) {
                        logger(CRON_HOUSEKEEPER_LOG, 'removing logfile');
                        unlink($thisDir . $log);
                    }
                }
            }
            closedir($dir);
        }
    }
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
                logger(CRON_HOUSEKEEPER_LOG, 'backup file \'' . APP_DATA_PATH . $item . '\' to \'' . BACKUP_PATH . $item . '\'');
                copy(APP_DATA_PATH . $item, BACKUP_PATH . $item);
            }
        }
    }
    closedir($dir);
}

logger(CRON_HOUSEKEEPER_LOG, 'Cron run finished');
