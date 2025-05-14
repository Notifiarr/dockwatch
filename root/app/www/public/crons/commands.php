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
$commandsFile   = apiRequest('file/commands')['result'] ?: [];
$migrate        = [
    "docker-inspect"            => "docker/container/inspect",
    "docker-networks"           => "docker/networks",
    "docker-port"               => "docker/container/ports",
    "docker-startContainer"     => "docker/container/start",
    "docker-stopContainer"      => "docker/container/stop",
    "docker-restartContainer"   => "docker/container/restart",
    "docker-exec"               => "docker/container/shell",
    "docker-processList"        => "docker/processList"
];
foreach ($commandsFile as $id => $command) {
    if ($migrate[$command['command']]) { //-- MIGRATE
        $newCommand = $migrate[$command['command']];

        $commandsFile[$id]['command'] = $newCommand; //-- UPDATE FILE
        $command['command'] = $newCommand; //-- CURRENT CRON RUN
    }

    $cron = !empty($command['cron']) ? Cron\CronExpression::factory($command['cron']) : [];
    if (!empty($cron) && $cron->isDue($startStamp)) {
        //-- SKIP REMOTE INSTANCE
        if ($command['servers'] !== APP_SERVER_ID) {
            logger(CRON_COMMANDS_LOG, 'Skipping command (remote instance): ' . json_encode($command), 'warn');
            continue;
        }

        logger(CRON_COMMANDS_LOG, 'Running command: ' . json_encode($command));

        //-- RUN COMMAND
        if (in_array($command['command'], ["docker/container/start", "docker/container/stop", "docker/container/restart"])) {
            $apiResponse = apiRequest($command['command'], [], ['name' => $command['container'], 'params' => $command['parameters']]);
        } else {
            $apiResponse = apiRequest($command['command'], ['name' => $command['container'], 'params' => $command['parameters']]);
        }
        $apiResponse = $apiResponse['code'] == 200 ? $apiResponse['result'] : $apiResponse['code'] . ': ' . $apiResponse['error'];

        logger(CRON_COMMANDS_LOG, '|__ Response: ' . json_encode($apiResponse));
    } else if (!empty($cron)) {
        logger(CRON_COMMANDS_LOG, 'Skipping command ' . $command['command'] . ', frequency setting will run: ' . $cron->getNextRunDate()->format('Y-m-d H:i:s'), 'warn');
    }
}
apiRequest('file/commands', [], ['contents' => json_encode($commandsFile, JSON_PRETTY_PRINT)]);

echo date('c') . ' Cron: commands <-' . "\n";
logger(CRON_COMMANDS_LOG, 'run <-');
