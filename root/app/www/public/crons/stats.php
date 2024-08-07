<?php

/*
----------------------------------
 ------  Created: 010324   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

logger(SYSTEM_LOG, 'Cron: running stats');
logger(CRON_STATS_LOG, 'run ->');
echo date('c') . ' Cron: stats' . "\n";

if ($settingsFile['tasks']['stats']['disabled']) {
    logger(CRON_STATS_LOG, 'Cron cancelled: disabled in tasks menu');
    logger(CRON_STATS_LOG, 'run <-');
    echo date('c') . ' Cron: stats cancelled, disabled in tasks menu' . "\n";
    echo date('c') . ' Cron: stats <-' . "\n";
    exit();
}

$dockerStats = $docker->stats(false);
setServerFile('stats', $dockerStats);

echo date('c') . ' Cron: stats <-' . "\n";
logger(CRON_STATS_LOG, 'run <-');