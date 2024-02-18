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
echo date('c') . ' Cron run started: stats' . "\n";

if ($settingsFile['tasks']['stats']['disabled']) {
    logger(CRON_STATS_LOG, 'Cron run stopped: disabled in tasks menu');
    echo date('c') . ' Cron run cancelled: disabled in tasks menu' . "\n";
    exit();
}

$dockerStats = dockerStats(false);
setServerFile('stats', $dockerStats);

logger(CRON_STATS_LOG, 'run <-');
