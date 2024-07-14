<?php

/*
----------------------------------
 ------  Created: 051124   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

logger(SYSTEM_LOG, 'Cron: running sse');
logger(CRON_SSE_LOG, 'run ->');
echo date('c') . ' Cron: sse' . "\n";

if (!$settingsFile['global']['sseEnabled']) {
    logger(CRON_SSE_LOG, 'Cron cancelled: disabled in tasks menu');
    logger(CRON_SSE_LOG, 'run <-');
    echo date('c') . ' Cron: sse cancelled, disabled in tasks menu' . "\n";
    echo date('c') . ' Cron: sse <-' . "\n";
    exit();
}

$getExpandedProcessList = getExpandedProcessList(true, true, true);
$processList            = $getExpandedProcessList['processList'];
$updatedProcessList     = [];

foreach ($processList as $process) {
    $nameHash = md5($process['Names']);
    $updatedProcessList[$nameHash] = renderContainerRow($nameHash, 'json');
}

$updatedProcessList['updated'] = time();

setFile(SSE_FILE, $updatedProcessList);

echo date('c') . ' Cron: sse <-' . "\n";
logger(CRON_SSE_LOG, 'run <-');