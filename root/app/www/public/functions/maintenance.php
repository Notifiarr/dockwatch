<?php

/*
----------------------------------
 ------  Created: 020424   ------
 ------  Austin Best	   ------
----------------------------------
*/

function initiaiteMaintenance($action)
{
    global $globalSettings;

    switch ($action) {
        case 'restart':
            file_put_contents(TMP_PATH . 'restart.txt', 'restart');
            createMaintenanceContainer('restart', $globalSettings['maintenanceIP'], $globalSettings['maintenancePort']);
            break;
    }
}

function maintenanceStartupCheck()
{
    logger(MAINTENANCE_LOG, 'maintenanceStartupCheck() ->');

    $getExpandedProcessList = getExpandedProcessList(true, true, true, true);
    $processList            = is_array($getExpandedProcessList['processList']) ? $getExpandedProcessList['processList'] : [];
    $imageMatch             = str_replace(':main', '', APP_IMAGE);
    $dockwatchContainer     = $maintenanceContainer = [];

    logger(MAINTENANCE_LOG, 'Process list: ' . count($processList) . ' containers');

    foreach ($processList as $process) {
        logger(MAINTENANCE_LOG, 'Checking \'' . $process['inspect'][0]['Config']['Image'] . '\' contains \'' . $imageMatch . '\'');

        if (str_contains($process['inspect'][0]['Config']['Image'], $imageMatch) && $process['Names'] != 'dockwatch-maintenance') {
            $dockwatchContainer = $process;
        }

        if ($process['Names'] == 'dockwatch-maintenance') {
            $maintenanceContainer = $process;
        }

        if ($dockwatchContainer && $maintenanceContainer) {
            break;
        }
    }

    if (file_exists(TMP_PATH . 'restart.txt')) { //-- dockwatch-maintenance CHECKING ON dockwatch RESTART
        logger(MAINTENANCE_LOG, 'restart requested for \'' . $dockwatchContainer['Names'] . '\'');
        maintenanceRestartHost($dockwatchContainer);
    } elseif (file_exists(TMP_PATH . 'update.txt')) { //-- dockwatch-maintenance CHECKING ON dockwatch UPDATE
        logger(MAINTENANCE_LOG, 'update requested for \'' . $dockwatchContainer['Names'] . '\'');
        // stop/remove/pull/create/start dockwatch
    } else { //-- dockwatch CHECKING ON dockwatch-maintenance REMOVAL
        logger(MAINTENANCE_LOG, 'removing \'dockwatch-maintenance\'');
        removeMaintenanceContainer();
    }

    logger(MAINTENANCE_LOG, 'maintenanceStartupCheck() <-');
}

function pullMaintenanceContainer()
{
    logger(MAINTENANCE_LOG, 'pullMaintenanceContainer() ->');

    $pull = apiRequest('dockerPullContainer', [], ['name' => APP_MAINTENANCE_IMAGE]);
    logger(MAINTENANCE_LOG, 'dockerPullContainer:' . json_encode($pull, JSON_UNESCAPED_SLASHES));

    logger(MAINTENANCE_LOG, 'pullMaintenanceContainer() <-');
}

function createMaintenanceContainer($action, $ip, $port)
{
    $getExpandedProcessList = getExpandedProcessList(true, true, true);
    $processList            = $getExpandedProcessList['processList'];
    $imageMatch             = str_replace(':main', '', APP_IMAGE);
    $container              = [];
    foreach ($processList as $process) {
        if (str_contains($process['inspect'][0]['Config']['Image'], $imageMatch) && $process['Names'] != 'dockwatch-maintenance') {
            $container = $process;
            break;
        }
    }

    $port = intval($port) > 0 ? intval($port) : 9998;
    logger(MAINTENANCE_LOG, 'createMaintenanceContainer() ->');
    logger(MAINTENANCE_LOG, 'using port ' . $port);

    pullMaintenanceContainer();

    $apiResponse = apiRequest('dockerInspect', ['name' => $container['Names'], 'useCache' => false, 'format' => true]);
    logger(MAINTENANCE_LOG, 'dockerInspect:' . json_encode($apiResponse, JSON_UNESCAPED_SLASHES));
    $inspectImage = $apiResponse['response']['docker'];
    $inspectImage = json_decode($inspectImage, true);

    $inspectImage[0]['Name']                                                = '/dockwatch-maintenance';
    $inspectImage[0]['Config']['Image']                                     = APP_MAINTENANCE_IMAGE;
    $inspectImage[0]['HostConfig']['PortBindings']['80/tcp'][0]['HostPort'] = strval($port);
    $inspectImage[0]['NetworkSettings']['Ports']['80/tcp'][0]['HostPort']   = strval($port);

    //-- STATIC IP CHECK
    if ($ip) {
        if ($inspectImage[0]['NetworkSettings']['Networks']) {
            $network = array_keys($inspectImage[0]['NetworkSettings']['Networks'])[0];

            if ($inspectImage[0]['NetworkSettings']['Networks'][$network]['IPAMConfig']['IPv4Address']) {
                $inspectImage[0]['NetworkSettings']['Networks'][$network]['IPAMConfig']['IPv4Address'] = $ip;
            }
            if ($inspectImage[0]['NetworkSettings']['Networks'][$network]['IPAddress']) {
                $inspectImage[0]['NetworkSettings']['Networks'][$network]['IPAddress'] = $ip;
            }
        }
    }

    removeMaintenanceContainer();

    $inspectImage   = json_encode($inspectImage);
    $apiResponse    = apiRequest('dockerCreateContainer', [], ['inspect' => $inspectImage]);
    $update         = $apiResponse['response']['docker'];
    logger(MAINTENANCE_LOG, 'dockerCreateContainer:' . json_encode($apiResponse, JSON_UNESCAPED_SLASHES));

    if (strlen($update['Id']) == 64) {
        startMaintenanceContainer();
    }

    logger(MAINTENANCE_LOG, 'createMaintenanceContainer() <-');
}

function startMaintenanceContainer()
{
    logger(MAINTENANCE_LOG, 'startMaintenanceContainer() ->');

    $start = dockerStartContainer('dockwatch-maintenance');
    logger(MAINTENANCE_LOG, 'dockerStartContainer() ' . trim($start));

    logger(MAINTENANCE_LOG, 'startMaintenanceContainer() <-');
}

function maintenanceRestartHost($container)
{
    logger(MAINTENANCE_LOG, 'maintenanceRestartHost() ->');

    unlink(TMP_PATH . 'restart.txt');
    logger(MAINTENANCE_LOG, 'removed ' . TMP_PATH . 'restart.txt');

    maintenanceStoptHost($container);
    maintenanceStartHost($container);

    logger(MAINTENANCE_LOG, 'maintenanceRestartHost() <-');
}

function maintenanceStoptHost($container)
{
    logger(MAINTENANCE_LOG, 'maintenanceStoptHost() ->');

    $stop = dockerStopContainer($container['Names']);
    logger(MAINTENANCE_LOG, 'dockerStopContainer() ' . trim($stop));

    logger(MAINTENANCE_LOG, 'maintenanceStoptHost() <-');
}

function maintenanceStartHost($container)
{
    logger(MAINTENANCE_LOG, 'maintenanceStartHost() ->');

    $start = dockerStartContainer($container['Names']);
    logger(MAINTENANCE_LOG, 'dockerStartContainer() ' . trim($start));

    logger(MAINTENANCE_LOG, 'maintenanceStartHost() <-');
}

function maintenanceRemoveHost($container)
{
    logger(MAINTENANCE_LOG, 'maintenanceRemoveHost() ->');

    $stop = dockerStopContainer($container['Names']);
    logger(MAINTENANCE_LOG, 'dockerStopContainer() ' . trim($stop));

    $remove = dockerRemoveContainer($container['Names']);
    logger(MAINTENANCE_LOG, 'dockerRemoveContainer() ' . trim($remove));

    logger(MAINTENANCE_LOG, 'maintenanceRemoveHost() <-');
}

function removeMaintenanceContainer()
{
    logger(MAINTENANCE_LOG, 'removeMaintenanceContainer() ->');

    $stop = dockerStopContainer('dockwatch-maintenance');
    logger(MAINTENANCE_LOG, 'dockerStopContainer() ' . trim($stop));

    $remove = dockerRemoveContainer('dockwatch-maintenance');
    logger(MAINTENANCE_LOG, 'dockerRemoveContainer() ' . trim($remove));

    logger(MAINTENANCE_LOG, 'removeMaintenanceContainer() <-');
}

function applyDockwatchUpdate()
{

}

function applyDockwatchRestart()
{

}
