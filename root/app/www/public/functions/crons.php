<?php

/*
----------------------------------
 ------  Created: 091724   ------
 ------  Austin Best	   ------
----------------------------------
*/

function canCronRun($cron, $settingsTable)
{
    switch ($cron) {
        case 'health':
            $log    = CRON_HEALTH_LOG;
            $field  = 'taskHealthDisabled';
            break;
        case 'housekeeper':
            $log    = CRON_HOUSEKEEPER_LOG;
            $field  = 'tasksHousekeepingDisabled';
            break;
        case 'prune':
            $log    = CRON_PRUNE_LOG;
            $field  = 'tasksPruneDisabled';
            break;
        case 'pulls':
            $log    = CRON_PULLS_LOG;
            $field  = 'tasksPullsDisabled';
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
            $field  = 'tasksStatsDisabled';
            break;
    }

    if (IS_MIGRATION_RUNNING) {
        logger($log, 'Cron cancelled: migrations are running');
        logger($log, 'run <-');
        echo date('c') . ' Cron: ' . $cron . ' cancelled, migrations are running' . "\n";
        echo date('c') . ' Cron: ' . $cron . ' <-' . "\n";
        return false;
    }

    if ($settingsTable[$field]) {
        logger($log, 'Cron cancelled: disabled in tasks menu');
        logger($log, 'run <-');
        echo date('c') . ' Cron: ' . $cron . ' cancelled, disabled in tasks menu' . "\n";
        echo date('c') . ' Cron: ' . $cron . ' <-' . "\n";
        return false;
    }

    //-- EXTRA CHECKS
    switch ($cron) {
        case 'health':
            if (!$settingsTable['restartUnhealthy'] && !apiRequest('database-isNotificationTriggerEnabled', ['trigger' => 'health'])['result']) {
                logger($log, 'Cron cancelled: restart and notify disabled');
                logger($log, 'run <-');
                echo date('c') . ' Cron ' . $cron . ' cancelled: restart unhealthy and notify disabled' . "\n";
                echo date('c') . ' Cron: ' . $cron . ' <-' . "\n";
                return false;
            }
        case 'prune':
            $frequencyHour = $settingsTable['autoPruneHour'] ? $settingsTable['autoPruneHour'] : '12';

            if ($frequencyHour !== date('G')) {
                logger($log, 'Cron: skipped, frequency setting will run at hour ' . $frequencyHour);
                logger($log, 'run <-');
                echo date('c') . ' Cron: skipped, frequency setting will run at hour ' . $frequencyHour . "\n";
                echo date('c') . ' Cron: ' . $cron . ' <-' . "\n";
                return false;
            }
        case 'state':
            if (!array_key_exists('stateCronTime', $settingsTable)) {
                logger($log, 'Cron cancelled: migration 003 has not been applied');
                logger($log, 'run <-');
                echo date('c') . ' Cron: ' . $cron . ' cancelled, migration 003 has not been applied' . "\n";
                echo date('c') . ' Cron: ' . $cron . ' <-' . "\n";
                return false;
            }

            if (date('i') % $settingsTable['stateCronTime'] !== 0) {
                logger($log, 'Cron cancelled: not a match to minute interval');
                logger($log, 'run <-');
                echo date('c') . ' Cron: ' . $cron . ' cancelled, not a match to minute interval' . "\n";
                echo date('c') . ' Cron: ' . $cron . ' <-' . "\n";
                return false;
            }
    }

    return true;
}
