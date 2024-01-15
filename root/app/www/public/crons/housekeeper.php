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
logger(CRON_HOUSEKEEPER_LOG, 'run ->');
echo 'Cron run started: housekeeper' . "\n";

if ($settingsFile['tasks']['housekeeping']['disabled']) {
    logger(CRON_HOUSEKEEPER_LOG, 'Cron run stopped: disabled in tasks menu');
    echo 'Cron run cancelled: disabled in tasks menu' . "\n";
    exit();
}

//-- UPDATE FILES CLEANUP (DAILY @ MIDNIGHT)
if (date('H') == 0 && date('i') <= 5) {
    logger(CRON_HOUSEKEEPER_LOG, 'Update tmp file cleanup (daily @ midnight)');

    $dir = opendir(TMP_PATH);
    while ($file = readdir($dir)) {
        if (!str_contains($file, 'json')) {
            continue;
        }

        logger(CRON_HOUSEKEEPER_LOG, 'removing \'' . TMP_PATH . $file . '\'');
        unlink(TMP_PATH . $file);
    }
    closedir($dir);
}

//-- LOG FILE CLEANUP (DAILY @ MIDNIGHT)
if (date('H') == 0 && date('i') <= 5) {
    $cleanup    = [
                    [
                        'crons'         => [[
                                            'message'   => 'Cron log file cleanup (daily @ midnight)',
                                            'length'    => ($settingsFile['global']['cronLogLength'] <= 1 ? 1 : $settingsFile['global']['cronLogLength'])
                                        ]]
                    ],[
                        'notifications' => [[
                                            'message'   => 'Notification log file cleanup (daily @ midnight)',
                                            'length'    => ($settingsFile['global']['notificationLogLength'] <= 1 ? 1 : $settingsFile['global']['notificationLogLength'])
                                        ]]
                    ],[
                        'system'        => [[
                                            'type'      => 'app',
                                            'message'   => 'System log file cleanup (daily @ midnight)',
                                            'length'    => 1
                                        ],[
                                            'type'      => 'ui',
                                            'message'   => 'UI log file cleanup (daily @ midnight)',
                                            'length'    => ($settingsFile['global']['uiLogLength'] <= 1 ? 1 : $settingsFile['global']['uiLogLength'])
                                        ],[
                                            'type'      => 'api',
                                            'message'   => 'API log file cleanup (daily @ midnight)',
                                            'length'    => ($settingsFile['global']['apiLogLength'] <= 1 ? 1 : $settingsFile['global']['apiLogLength'])
                                        ]]
                    ]
                ];

    foreach ($cleanup as $folders) {
        foreach ($folders as $dir => $dirTypes) {
            $thisDir = LOGS_PATH . $dir . '/';
            if (!is_dir($thisDir)) {
                continue;
            }

            foreach ($dirTypes as $settings) {
                logger(CRON_HOUSEKEEPER_LOG, $settings['message']);
                logger(CRON_HOUSEKEEPER_LOG, 'Allowed ' . $dir . ' log age: ' . $settings['length']);

                $folder = opendir($thisDir);
                while ($log = readdir($folder)) {
                    if ($log[0] != '.' && !is_dir($thisDir . $log)) {
                        $daysBetween = daysBetweenDates(date('Ymd', filemtime($thisDir . $log)), date('Ymd'));
                        logger(CRON_HOUSEKEEPER_LOG, 'logfile: ' . $thisDir . $log . ', days: ' . $daysBetween);
            
                        if ($daysBetween > $settings['length']) {
                            logger(CRON_HOUSEKEEPER_LOG, 'removing logfile');
                            unlink($thisDir . $log);
                        }
                    }
                }
                closedir($folder);
            }
        }
    }
}

//-- MAKE A BACKUP
$settingsFile = str_replace(APP_DATA_PATH, '', SETTINGS_FILE);
if (!file_exists(BACKUP_PATH . $settingsFile)) {
    $dir = opendir(APP_DATA_PATH);
    while ($item = readdir($dir)) {
        if (str_contains($item, '.json')) {
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

logger(CRON_HOUSEKEEPER_LOG, 'run <-');
