<?php

/*
----------------------------------
 ------  Created: 092824   ------
 ------  Austin Best	   ------
----------------------------------
*/

function telemetry($send = false)
{
    global $database;

    $database       = $database ?: new Database();
    $settingsTable  = $database->getSettings();
    $serversTable   = $database->getServers();

    //-- ONE WAY HASH() OF AN MD5() HASHED HOSTNAME + APIKEY TO KEEP THINGS UNIQUE
    $telemetry['token'] = hash('sha256', md5($_SERVER['HOSTNAME'] . $serversTable[1]['apikey']));

    //-- INSTANCE INFO
    $telemetry['branch']    = gitBranch();
    $telemetry['version']   = gitVersion();

    if ($settingsTable['telemetry']) {
        $containersTable    = $database->getContainers();
        $groupsTable        = $database->getContainerGroups();
        $notificationTable  = $database->getNotificationLinks();

        //-- CONTAINER INFO
        $auto = $check = $ignore = 0;
        foreach ($containersTable as $container) {
            if ($container['updates'] == 1) {
                $auto++;
            } elseif ($container['updates'] == 2) {
                $check++;
            } else {
                $ignore++;
            }
        }

        $telemetry['telemetry']['containers']['update']['auto']      = $auto;
        $telemetry['telemetry']['containers']['update']['check']     = $check;
        $telemetry['telemetry']['containers']['update']['ignore']    = $ignore;
        $telemetry['telemetry']['containers']['total']               = count($containersTable);

        //-- GROUP INFO
        $telemetry['telemetry']['groups']['total'] = count($groupsTable);

        //-- SERVER INFO
        $telemetry['telemetry']['servers']['total'] = count($serversTable);

        //-- COMPOSE INFO
        $existingComposeFolders = [];
        $dir = opendir(COMPOSE_PATH);
        while ($folder = readdir($dir)) {
            if ($folder[0] == '.') {
                continue;
            }
        
            $existingComposeFolders[] = COMPOSE_PATH . $folder;
        }
        closedir($dir);

        $telemetry['telemetry']['compose']['total'] = count($existingComposeFolders);

        //-- NOTIFICATIONS INFO
        $telemetry['telemetry']['notifications']['notifiarr']    = 0;
        $telemetry['telemetry']['notifications']['telegram']     = 0;

        if ($notificationTable) {
            foreach ($notificationTable as $notificationLink) {
                if ($notificationLink['platform_id'] == NotificationPlatforms::NOTIFIARR) {
                    $telemetry['telemetry']['notifications']['notifiarr']++;
                }
                if ($notificationLink['platform_id'] == NotificationPlatforms::TELEGRAM) {
                    $telemetry['telemetry']['notifications']['telegram']++;
                }
            }
        }
    } else { //-- TELEMETRY IS DISABLED :(
        $telemetry['telemetry']['error'] = 'Telemetry is disabled';
    }

    //-- PUSH THE TELEMETRY DATA
    if ($send) {
        curl(TELEMETRY_URL, [], 'POST', json_encode($telemetry), [], 10);
    }

    return $telemetry;
}
