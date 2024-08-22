<?php

/*
----------------------------------
 ------  Created: 082124   ------
 ------  Austin Best	   ------
----------------------------------
*/

$q = [];
$q[] = "CREATE TABLE " . SETTINGS_TABLE . " ( 
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL UNIQUE,
        value TEXT NOT NULL
        )";

$q[] = "CREATE TABLE " . CONTAINER_SETTINGS_TABLE . " ( 
        id INTEGER PRIMARY KEY,
        hash TEXT NOT NULL UNIQUE,
        updates INTEGER NOT NULL,
        frequency TEXT NOT NULL,
        restartUnhealthy INTEGER NOT NULL,
        disableNotifications INTEGER NOT NULL,
        shutdownDelay INTEGER NOT NULL,
        shutdownDelaySeconds INTEGER NOT NULL
        )";

$q[] = "CREATE TABLE " . CONTAINER_GROUPS_TABLE . " ( 
        id INTEGER PRIMARY KEY,
        hash TEXT NOT NULL UNIQUE,
        name TEXT NOT NULL
        )";

$q[] = "CREATE TABLE " . CONTAINER_GROUPS_LINK_TABLE . " ( 
        id INTEGER PRIMARY KEY,
        group_id INTEGER NOT NULL,
        container_id INTEGER NOT NULL
        )";

$q[] = "CREATE TABLE " . NOTIFICATION_PLATFORM_TABLE . " ( 
        id INTEGER PRIMARY KEY,
        platform TEXT NOT NULL UNIQUE,
        parameters TEXT NOT NULL
        )";

$q[] = "INSERT INTO " . NOTIFICATION_PLATFORM_TABLE . "
        (`id`, `platform`, `parameters`) 
        VALUES 
        ('" . NotificationPlatforms::NOTIFIARR . "', 'Notifiarr', '{\"apikey\":{\"label\":\"API Key\",\"description\":\"The Notifiarr API key from your profile (integration specific or global)\",\"type\":\"text\",\"required\":\"true\"}}'),
        ('" . NotificationPlatforms::TELEGRAM . "', 'Telegram', '')";

$q[] = "CREATE TABLE " . NOTIFICATION_TRIGGER_TABLE . " ( 
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL UNIQUE,
        label TEXT NOT NULL,
        description TEXT NOT NULL,
        event TEXT NOT NULL
        )";

$q[] = "INSERT INTO " . NOTIFICATION_TRIGGER_TABLE . "
        (`name`, `label`, `description`, `event`) 
        VALUES 
        ('updated', 'Updated', 'Send a notification when a container has had an update applied', 'updates'),
        ('updates', 'Updates', 'Send a notification when a container has an update available', 'updates'),
        ('stateChange', 'State change', 'Send a notification when a container has a state change (running -> down)', 'state'),
        ('added', 'Added', 'Send a notification when a container is added', 'state'),
        ('removed', 'Removed', 'Send a notification when a container is removed', 'state'),
        ('prune', 'Prune', 'Send a notification when an image or volume is pruned', 'prune'),
        ('cpuHigh', 'CPU usage', 'Send a notification when container CPU usage exceeds threshold (set in Settings)', 'usage'),
        ('memHigh', 'Memory usage', 'Send a notification when container memory usage exceeds threshold (set in Settings)', 'usage'),
        ('health', 'Health change', 'Send a notification when container becomes unhealthy', 'health')";

$q[] = "CREATE TABLE " . NOTIFICATION_LINK_TABLE . " ( 
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL UNIQUE,
        platform_id INTEGER NOT NULL,
        platform_parameters TEXT NOT NULL,
        trigger_ids TEXT NOT NULL
        )";

$q[] = "CREATE TABLE " . SERVERS_TABLE . " ( 
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL UNIQUE,
        url TEXT NOT NULL,
        apikey TEXT NOT NULL
        )";

$globalSettings = [
                    'serverName'                => '',
                    'maintenanceIP'             => '',
                    'maintenancePort'           => 9998,
                    'loginFailures'             => 6,
                    'loginTimeout'              => 60,
                    'updates'                   => 2,
                    'updatesFrequency'          => '0 2 * * *',
                    'autoPruneImages'           => false,
                    'autoPruneVolumes'          => false,
                    'autoPruneNetworks'         => false,
                    'autoPruneHour'             => 12,
                    'cpuThreshold'              => '',
                    'cpuAmount'                 => '',
                    'memThreshold'              => '',
                    'sseEnabled'                => false,
                    'cronLogLength'             => 1,
                    'notificationLogLength'     => 1,
                    'uiLogLength'               => 1,
                    'apiLogLength'              => 1,
                    'environment'               => 0,
                    'overrideBlacklist'         => false,
                    'externalLoading'           => 0,
                    'taskStatsDisabled'         => 0,
                    'taskStateDisabled'         => 0,
                    'taskPullsDisabled'         => 0,
                    'taskHousekeepingDisabled'  => 0,
                    'taskHealthDisabled'        => 0,
                    'taskPruneDisabled'         => 0
                ];

foreach ($globalSettings as $key => $val) {
    $settingRows[] = "('" . $key . "', '" . $val . "')";
}

$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`) 
        VALUES " . implode(', ', $settingRows);

//-- PRE-DB SUPPORT, POPULATE THE NEW TABLES WITH EXISTING DATA
if (file_exists(APP_DATA_PATH . 'servers.json')) {
    $serversFile    = getFile(SERVERS_FILE);
    $serverRows     = [];

    foreach ($serversFile as $server) {
        $serverRows[] = "('" . $server['name'] . "', '" . $server['url'] . "', '" . $server['apikey'] . "')";
    }

    if ($serverRows) {
        $q[] = "INSERT INTO " . SERVERS_TABLE . "
                (`name`, `url`, `apikey`) 
                VALUES " . implode(', ', $serverRows);
    }
} else {
    $q[] = "INSERT INTO " . SERVERS_TABLE . "
            (`name`, `url`, `apikey`) 
            VALUES 
            ('" . APP_NAME . "', '" . APP_SERVER_URL . "', '" . generateApikey() . "')";
}

if (file_exists(APP_DATA_PATH . 'settings.json')) {
    $settingsFile = getFile(SETTINGS_FILE);

    if ($settingsFile['tasks']) {
        foreach ($settingsFile['tasks'] as $task => $taskSettings) {
            $q[] = "UPDATE " . SETTINGS_TABLE . "
                    SET value = '" . $taskSettings['disabled'] . "'
                    WHERE name = 'task" . ucfirst($task) . "Disabled'";
        }
    }

    if ($settingsFile['global']) {
        foreach ($settingsFile['global'] as $key => $val) {
            $q[] = "UPDATE " . SETTINGS_TABLE . "
                    SET value = '" . $val . "'
                    WHERE name = '" . $key . "'";
        }
    }

    if ($settingsFile['containers']) {
        $containerSettingsRows = [];

        foreach ($settingsFile['containers'] as $hash => $settings) {
            $containerSettingsRows[] = "('" . $hash . "', '" . intval($settings['updates']) . "', '" . $settings['frequency'] . "', '" . intval($settings['restartUnhealthy']) . "', '" . intval($settings['disableNotifications']) . "', '" . intval($settings['shutdownDelay']) . "', '" . intval($settings['shutdownDelaySeconds']) . "')";
        }

        if ($containerSettingsRows) {
            $q[] = "INSERT INTO " . CONTAINER_SETTINGS_TABLE . "
                    (`hash`, `updates`, `frequency`, `restartUnhealthy`, `disableNotifications`, `shutdownDelay`, `shutdownDelaySeconds`) 
                    VALUES " . implode(', ', $containerSettingsRows);
        }
    }

    if ($settingsFile['containerGroups']) {
        $containerGroupRows = [];

        foreach ($settingsFile['containerGroups'] as $groupHash => $groupData) {
            $containerGroupRows[] = "('" . $groupHash . "', '" . $groupData['name'] . "')";
        }

        if ($containerGroupRows) {
            $q[] = "INSERT INTO " . CONTAINER_GROUPS_TABLE . "
                    (`hash`, `name`) 
                    VALUES " . implode(', ', $containerGroupRows);
        }
    }

    if ($settingsFile['notifications']) {
        if ($settingsFile['notifications']['platforms'] && $settingsFile['notifications']['triggers']) {
            $triggerIds = [];
            if ($settingsFile['notifications']['triggers']['updated']['active']) {
                $triggerIds[] = 1;
            }
            if ($settingsFile['notifications']['triggers']['updates']['active']) {
                $triggerIds[] = 2;
            }
            if ($settingsFile['notifications']['triggers']['stateChange']['active']) {
                $triggerIds[] = 3;
            }
            if ($settingsFile['notifications']['triggers']['added']['active']) {
                $triggerIds[] = 4;
            }
            if ($settingsFile['notifications']['triggers']['removed']['active']) {
                $triggerIds[] = 5;
            }
            if ($settingsFile['notifications']['triggers']['prune']['active']) {
                $triggerIds[] = 6;
            }
            if ($settingsFile['notifications']['triggers']['cpuHigh']['active']) {
                $triggerIds[] = 7;
            }
            if ($settingsFile['notifications']['triggers']['memHigh']['active']) {
                $triggerIds[] = 8;
            }
            if ($settingsFile['notifications']['triggers']['health']['active']) {
                $triggerIds[] = 9;
            }

            $q[] = "INSERT INTO " . NOTIFICATION_LINK_TABLE . "
                    (`id`, `name`, `platform_id`, `platform_parameters`, `trigger_ids`) 
                    VALUES 
                    ('1', 'Notifiarr', '" . NotificationPlatforms::NOTIFIARR . "', '{\"apikey\":\"" . $settingsFile['notifications']['platforms'][NotificationPlatforms::NOTIFIARR]['apikey'] . "\"}', '[" . implode(',', $triggerIds) . "]')";
        }
    }
}

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`) 
        VALUES 
        ('migration', '001')";

foreach ($q as $query) {
	logger(MIGRATION_LOG, '<span class="text-success">[Q]</span> ' . preg_replace('!\s+!', ' ', $query));

    $db->query($query);

	if ($database->error() != 'not an error') {
		logger(MIGRATION_LOG, '<span class="text-info">[R]</span> '  .$database->error(), 'error');
	} else {
		logger(MIGRATION_LOG, '<span class="text-info">[R]</span> query applied!');
	}
}

//-- PRE-DB SUPPORT, POPULATE THE NEW TABLES WITH EXISTING DATA
if ($settingsFile) {
    $q                  = $containerLinkRows = [];
    $containers         = apiRequest('database-getContainers')['result'];
    $containerGroups    = apiRequest('database-getContainerGroups')['result'];

    if ($settingsFile['containerGroups']) {
        foreach ($settingsFile['containerGroups'] as $groupHash => $groupData) {
            if ($groupData['containers']) {
                foreach ($groupData['containers'] as $groupContainerHash) {
                    $container  = apiRequest('database-getContainerFromHash', ['hash' => $groupContainerHash])['result'];
                    $group      = apiRequest('database-getContainerGroupFromHash', ['hash' => $groupHash])['result'];

                    if ($group['id'] && $container['id']) {
                        $containerLinkRows[] = "('" . $group['id'] . "', '" . $container['id'] . "')";
                    }
                }
            }
        }

        if ($containerLinkRows) {
            $q[] = "INSERT INTO " . CONTAINER_GROUPS_LINK_TABLE . "
                    (`group_id`, `container_id`) 
                    VALUES " . implode(', ', $containerLinkRows);
        }
    }

    foreach ($q as $query) {
        logger(MIGRATION_LOG, '<span class="text-success">[Q]</span> ' . preg_replace('!\s+!', ' ', $query));

        $db->query($query);

        if ($database->error() != 'not an error') {
            logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $database->error(), 'error');
        } else {
            logger(MIGRATION_LOG, '<span class="text-info">[R]</span> query applied!');
        }
    }
}
