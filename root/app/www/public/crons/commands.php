<?php

/*
----------------------------------
 ------  Created: 042925   ------
 ------  nzxl         	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

logger(SYSTEM_LOG, 'Cron: running commands');
logger(CRON_COMMANDS_LOG, 'run ->');
echo date('c') . ' Cron: commands' . "\n";

if (!canCronRun('commands', $settingsTable)) {
    exit();
}

//-- RUN COMMANDS
$startStamp     = new DateTime();
$commandsFile   = getFile(COMMANDS_FILE) ?: [];
foreach ($commandsFile as $command) {
    $cron = !empty($command['cron']) ? Cron\CronExpression::factory($command['cron']) : [];
    if (!empty($cron) && $cron->isDue($startStamp)) {
        //-- SKIP REMOTE INSTANCE
        if ($command['servers'] !== APP_SERVER_ID) {
            logger(CRON_COMMANDS_LOG, '[SKIP] Skipping command (remote instance): ' . json_encode($command));
            continue;
        }

        logger(CRON_COMMANDS_LOG, 'Payload: ' . json_encode($command));

        //-- RUN COMMAND
        $apiResponse = apiRequest($command['command'], ['name' => $command['container'], 'params' => $command['parameters']]);
        $apiResponse = $apiResponse['code'] == 200 ? $apiResponse['result'] : $apiResponse['code'] . ': ' . $apiResponse['error'];

        logger(CRON_COMMANDS_LOG, '|__ Response: ' . json_encode($apiResponse));
    }
}

echo date('c') . ' Cron: commands <-' . "\n";
logger(CRON_COMMANDS_LOG, 'run <-');
