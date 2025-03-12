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
echo date('c') . ' Cron: housekeeper ->' . "\n";

//-- USAGE METRICS DISK/IO (RUN EVERY HOUR)
if (date('i') <= 5) {
    logger(CRON_HOUSEKEEPER_LOG, 'Usage Metrics (run every hour)');
    $usageRetention = apiRequestLocal('database-getSettings')['usageMetricsRetention'];
    $usageMetrics   = cacheUsageMetrics(intval($usageRetention));
    logger(CRON_HOUSEKEEPER_LOG, '$usageMetrics=' . json_encode($usageMetrics));
}

//-- TELEMETRY CHECK (DAILY @ MIDNIGHT)
if (date('H') == 0 && date('i') <= 5) {
    logger(CRON_HOUSEKEEPER_LOG, 'Telemetry (daily @ midnight)');
    $telemetry = telemetry(true);
    logger(CRON_HOUSEKEEPER_LOG, '$telemetry=' . json_encode($telemetry));
}

if (!canCronRun('housekeeper', $settingsTable)) {
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
        $shell->exec('rm -rf ' . TMP_PATH . $file);
    }
    closedir($dir);
}

//-- LOG FILE CLEANUP (DAILY @ MIDNIGHT)
if (date('H') == 0 && date('i') <= 5) {
    $cleanup    = [
                    [
                        'crons'         => [[
                                            'message'   => 'Cron log file cleanup (daily @ midnight)',
                                            'length'    => ($settingsTable['cronLogLength'] <= 1 ? 1 : $settingsTable['cronLogLength'])
                                        ]]
                    ],[
                        'notifications' => [[
                                            'message'   => 'Notification log file cleanup (daily @ midnight)',
                                            'length'    => ($settingsTable['notificationLogLength'] <= 1 ? 1 : $settingsTable['notificationLogLength'])
                                        ]]
                    ],[
                        'system'        => [[
                                            'type'      => 'app',
                                            'message'   => 'System log file cleanup (daily @ midnight)',
                                            'length'    => 1
                                        ],[
                                            'type'      => 'ui',
                                            'message'   => 'UI log file cleanup (daily @ midnight)',
                                            'length'    => ($settingsTable['uiLogLength'] <= 1 ? 1 : $settingsTable['uiLogLength'])
                                        ],[
                                            'type'      => 'api',
                                            'message'   => 'API log file cleanup (daily @ midnight)',
                                            'length'    => ($settingsTable['apiLogLength'] <= 1 ? 1 : $settingsTable['apiLogLength'])
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
                            $shell->exec('rm -rf ' . $thisDir . $log);
                        }
                    }
                }
                closedir($folder);
            }
        }
    }
}

//-- BACKUPS
if (!is_dir(BACKUP_PATH . date('Ymd'))) {
    createDirectoryTree(BACKUP_PATH . date('Ymd'));
    $defines = get_defined_constants();
    foreach ($defines as $define => $defineValue) {
        if (str_contains($define, '_FILE') && str_contains_all($defineValue, ['config/', '.json'])) {
            $backupFiles[] = $defineValue;
        }
    }

    foreach ($backupFiles as $backupFile) {
        $file = explode('/', $backupFile);
        $file = end($file);

        logger(CRON_HOUSEKEEPER_LOG, 'backup file \'' . APP_DATA_PATH . $file . '\' to \'' . BACKUP_PATH . $file . '\'');
        copy(APP_DATA_PATH . $file, BACKUP_PATH . date('Ymd') . '/' . $file);
        $database->backup();
    }
}

$dir = opendir(BACKUP_PATH);
while ($backup = readdir($dir)) {
    if (!is_dir(BACKUP_PATH . $backup)) {
        logger(CRON_HOUSEKEEPER_LOG, 'removing backup: ' . BACKUP_PATH . $backup);
        $shell->exec('rm -rf ' . BACKUP_PATH . $backup);
    } else {
        if ($backup[0] != '.') {
            $daysBetween = daysBetweenDates($backup, date('Ymd'));

            if ($daysBetween >= APP_BACKUPS) {
                logger(CRON_HOUSEKEEPER_LOG, 'removing backup: ' . BACKUP_PATH . $backup);
                $shell->exec('rm -rf ' . BACKUP_PATH . $backup);
            }
        }
    }
}

echo date('c') . ' Cron: housekeeper <-' . "\n";
logger(CRON_HOUSEKEEPER_LOG, 'run <-');
