<?php

/*
----------------------------------
 ------  Created: 111723   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

$logfile = ABSOLUTE_PATH . LOGS_PATH . 'cron-state-' . date('Ymd') . '.log';
logger($logfile, 'Cron run started');
echo 'Cron run started: state' . "\n";
setFile(STATE_FILE, dockerState());
logger($logfile, 'Cron run finished');
