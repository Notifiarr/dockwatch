<?php

/*
----------------------------------
 ------  Created: 091724   ------
 ------  Austin Best	   ------
----------------------------------
*/

function canCronRun($cron, $settingsTable)
{
    global $database;

    switch ($cron) {
        case 'health':
            $log    = CRON_HEALTH_LOG;
            $field  = 'taskHealthDisabled';
            break;
        case 'housekeeper':
            $log    = CRON_HOUSEKEEPER_LOG;
            $field  = 'taskHousekeepingDisabled';
            break;
        case 'prune':
            $log    = CRON_PRUNE_LOG;
            $field  = 'taskPruneDisabled';
            break;
        case 'pulls':
            $log    = CRON_PULLS_LOG;
            $field  = 'taskPullsDisabled';
            break;
        case 'sse':
            $log    = CRON_SSE_LOG;
            $field  = 'sseEnabled';
            break;
        case 'state':
            $log    = CRON_STATE_LOG;
            $field  = 'taskStateDisabled';
            break;
        case 'stats':
            $log    = CRON_STATS_LOG;
            $field  = 'taskStatsDisabled';
            break;
        case 'commands':
            $log    = CRON_COMMANDS_LOG;
            $field  = 'taskCommandsDisabled';
            break;
    }

    if (IS_MIGRATION_RUNNING) {
        $highestMigration = $database->getNewestMigration();
        $currentMigration = $settingsTable['migration'];

        if ($highestMigration == $currentMigration) {
            deleteFile(MIGRATION_FILE);
        } else {
            logger($log, 'Cron cancelled: migrations are running', 'warn');
            logger($log, 'run <-');
            echo date('c') . ' Cron: ' . $cron . ' cancelled, migrations are running' . "\n";
            echo date('c') . ' Cron: ' . $cron . ' <-' . "\n";
            return false;
        }
    }

    if (($cron == 'sse' && !$settingsTable[$field]) || ($cron != 'sse' && $settingsTable[$field])) {
        logger($log, 'Cron cancelled: disabled in tasks menu', 'warn');
        logger($log, 'run <-');
        echo date('c') . ' Cron: ' . $cron . ' cancelled, disabled in tasks menu' . "\n";
        echo date('c') . ' Cron: ' . $cron . ' <-' . "\n";
        return false;
    }

    //-- EXTRA CHECKS
    switch ($cron) {
        case 'health':
            if (!$settingsTable['restartUnhealthy'] && !apiRequest('database/notification/trigger/enabled', ['trigger' => 'health'])['result']) {
                logger($log, 'Cron cancelled: restart and notify disabled', 'warn');
                logger($log, 'run <-');
                echo date('c') . ' Cron ' . $cron . ' cancelled: restart unhealthy and notify disabled' . "\n";
                echo date('c') . ' Cron: ' . $cron . ' <-' . "\n";
                return false;
            }
            break;
        case 'prune':
            $frequencyHour = $settingsTable['autoPruneHour'] ? $settingsTable['autoPruneHour'] : '12';

            if ($frequencyHour !== date('G')) {
                logger($log, 'Cron: skipped, frequency setting will run at hour ' . $frequencyHour, 'warn');
                logger($log, 'run <-');
                echo date('c') . ' Cron: skipped, frequency setting will run at hour ' . $frequencyHour . "\n";
                echo date('c') . ' Cron: ' . $cron . ' <-' . "\n";
                return false;
            }
            break;
        case 'state':
            if (!array_key_exists('stateCronTime', $settingsTable)) {
                logger($log, 'Cron cancelled: migration 003 has not been applied', 'warn');
                logger($log, 'run <-');
                echo date('c') . ' Cron: ' . $cron . ' cancelled, migration 003 has not been applied' . "\n";
                echo date('c') . ' Cron: ' . $cron . ' <-' . "\n";
                return false;
            }

            if (date('i') % $settingsTable['stateCronTime'] !== 0) {
                logger($log, 'Cron cancelled: not a match to minute interval', 'warn');
                logger($log, 'run <-');
                echo date('c') . ' Cron: ' . $cron . ' cancelled, not a match to minute interval' . "\n";
                echo date('c') . ' Cron: ' . $cron . ' <-' . "\n";
                return false;
            }
            break;
    }

    return true;
}
