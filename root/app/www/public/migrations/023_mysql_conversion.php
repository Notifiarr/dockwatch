<?php

/*
----------------------------------
 ------  Created: 042426   ------
 ------  Austin Best	   ------
----------------------------------
*/

if (!MYSQL_SETUP) {
    // TODO: DELETE THIS CONDITION AFTER AN UPDATE OR TWO (LEAVE THE return; ON LN 28 ONLY), IT IS JUST A FALLBACK FOR SOME BUGS DURING THE MIGRATION PROCESS
    if (!$currentMigration) {
        //-- CONNECT TO THE MYSQL DATABASE
        $database->mysql = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        $sql = "UPDATE " . SETTINGS_TABLE . "
                SET value = '023'
                WHERE name = 'migration'";
        $database->mysqli_query($sql);

        if ($database->error() != 'not an error') {
            logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $database->error(), 'error');
            $error = true;
        } else {
            logger(MIGRATION_LOG, '<span class="text-info">[R]</span> query applied!');
        }
    }
    return;
}

//-- CREATE THE MYSQL DATABASE
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
logger(MIGRATION_LOG, '<span class="text-success">[Q]</span> ' . preg_replace('!\s+!', ' ', $sql));

$database->mysqli_query($sql);
if ($error = $database->mysqli_error()) {
    logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $error, 'error');
    logger(MIGRATION_LOG, 'ERROR: Creating MySQL database "' . DB_NAME . '" failed', 'shell');
    return;
}
//-- CONNECT TO THE MYSQL DATABASE
$database->mysql = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$q = [];

//-- CONTAINER_SETTINGS_TABLE
$q[] = "DROP TABLE IF EXISTS `" . CONTAINER_SETTINGS_TABLE . "`;";
$q[] = "CREATE TABLE `" . CONTAINER_SETTINGS_TABLE . "` (
        `id` int(11) NOT NULL,
        `hash` varchar(100) NOT NULL,
        `updates` smallint(6) NOT NULL,
        `frequency` varchar(25) NOT NULL,
        `restartUnhealthy` smallint(6) NOT NULL,
        `disableNotifications` smallint(6) NOT NULL,
        `shutdownDelay` smallint(6) NOT NULL,
        `shutdownDelaySeconds` smallint(6) NOT NULL,
        `minAge` smallint(6) NOT NULL DEFAULT 0,
        `autoRestart` smallint(6) NOT NULL DEFAULT 0,
        `autoRestartFrequency` varchar(100) NOT NULL DEFAULT '0 2 * * *',
        `containerGui` text NULL,
        `lastUpdateCronTime` int(11) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$q[] = "ALTER TABLE `" . CONTAINER_SETTINGS_TABLE . "`
        ADD PRIMARY KEY (`id`),
        ADD UNIQUE KEY `hash` (`hash`);";
$q[] = "ALTER TABLE `" . CONTAINER_SETTINGS_TABLE . "`
        MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";

if (SQLITE_MIGRATION) { //-- EXISTING INSTALL
    $sql = $db->query("SELECT * FROM " . CONTAINER_SETTINGS_TABLE);
    while ($row = $database->fetchAssoc($sql)) {
        $containerGuiSql = (!array_key_exists('containerGui', $row) || $row['containerGui'] == null) ? 'NULL' : "'" . $database->prepare($row['containerGui']) . "'";
        $minAge          = $row['minAge'] ?? $row['minage'] ?? 0;

        $q[] = "INSERT INTO " . CONTAINER_SETTINGS_TABLE . " 
                (`id`, `hash`, `updates`, `frequency`, `restartUnhealthy`, `disableNotifications`, `shutdownDelay`, `shutdownDelaySeconds`, `minAge`, `autoRestart`, `autoRestartFrequency`, `containerGui`, `lastUpdateCronTime`) 
                VALUES 
                ('" . $row['id'] . "', '" . $database->prepare($row['hash']) . "', '" . $row['updates'] . "', '" . $database->prepare($row['frequency']) . "', '" . $row['restartUnhealthy'] . "', '" . $row['disableNotifications'] . "', '" . $row['shutdownDelay'] . "', '" . $row['shutdownDelaySeconds'] . "', '" . $minAge . "', '" . ($row['autoRestart'] ?? 0) . "', '" . $database->prepare($row['autoRestartFrequency'] ?? '0 2 * * *') . "', " . $containerGuiSql . ", '" . ($row['lastUpdateCronTime'] ?? 0) . "')";
    }
}

//-- CONTAINER_GROUPS_TABLE
$q[] = "DROP TABLE IF EXISTS `" . CONTAINER_GROUPS_TABLE . "`;";
$q[] = "CREATE TABLE `" . CONTAINER_GROUPS_TABLE . "` (
        `id` int(11) NOT NULL,
        `hash` varchar(100) NOT NULL,
        `name` varchar(150) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$q[] = "ALTER TABLE `" . CONTAINER_GROUPS_TABLE . "`
        ADD PRIMARY KEY (`id`),
        ADD UNIQUE KEY `hash` (`hash`);";
$q[] = "ALTER TABLE `" . CONTAINER_GROUPS_TABLE . "`
        MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";

if (SQLITE_MIGRATION) { //-- EXISTING INSTALL
    $sql = $db->query("SELECT * FROM " . CONTAINER_GROUPS_TABLE);
    while ($row = $database->fetchAssoc($sql)) {
        $q[] = "INSERT INTO " . CONTAINER_GROUPS_TABLE . " 
                (`id`, `hash`, `name`) 
                VALUES 
                ('" . $row['id'] . "', '" . $database->prepare($row['hash']) . "', '" . $database->prepare($row['name']) . "')";
    }
}

//-- CONTAINER_GROUPS_LINK_TABLE
$q[] = "DROP TABLE IF EXISTS `" . CONTAINER_GROUPS_LINK_TABLE . "`;";
$q[] = "CREATE TABLE `" . CONTAINER_GROUPS_LINK_TABLE . "` (
        `id` int(11) NOT NULL,
        `group_id` int(11) NOT NULL,
        `container_id` int(11) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$q[] = "ALTER TABLE `" . CONTAINER_GROUPS_LINK_TABLE . "`
        ADD PRIMARY KEY (`id`),
        ADD UNIQUE KEY `group_id` (`group_id`,`container_id`);";
$q[] = "ALTER TABLE `" . CONTAINER_GROUPS_LINK_TABLE . "`
        MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";

if (SQLITE_MIGRATION) { //-- EXISTING INSTALL
    $sql = $db->query("SELECT * FROM " . CONTAINER_GROUPS_LINK_TABLE);
    while ($row = $database->fetchAssoc($sql)) {
        $q[] = "INSERT INTO " . CONTAINER_GROUPS_LINK_TABLE . " 
                (`id`, `group_id`, `container_id`) 
                VALUES 
                ('" . $row['id'] . "', '" . $row['group_id'] . "', '" . $row['container_id'] . "')";
    }
}

//-- NOTIFICATION_PLATFORM_TABLE
$q[] = "DROP TABLE IF EXISTS `" . NOTIFICATION_PLATFORM_TABLE . "`;";
$q[] = "CREATE TABLE `" . NOTIFICATION_PLATFORM_TABLE . "` (
        `id` int(11) NOT NULL,
        `platform` varchar(100) NOT NULL,
        `parameters` text NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$q[] = "ALTER TABLE `" . NOTIFICATION_PLATFORM_TABLE . "`
        ADD PRIMARY KEY (`id`),
        ADD UNIQUE KEY `platform` (`platform`);";
$q[] = "ALTER TABLE `" . NOTIFICATION_PLATFORM_TABLE . "`
        MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";

if (SQLITE_MIGRATION) { //-- EXISTING INSTALL
    $sql = $db->query("SELECT * FROM " . NOTIFICATION_PLATFORM_TABLE);
    while ($row = $database->fetchAssoc($sql)) {
        $q[] = "INSERT INTO " . NOTIFICATION_PLATFORM_TABLE . " 
                (`id`, `platform`, `parameters`) 
                VALUES 
                ('" . $row['id'] . "', '" . $database->prepare($row['platform']) . "', '" . $database->prepare($row['parameters']) . "')";
    }
} else { //--- NEW INSTALL
    $q[] = "INSERT INTO " . NOTIFICATION_PLATFORM_TABLE . "
            (`id`, `platform`, `parameters`)
            VALUES
            ('" . NotificationPlatforms::NOTIFIARR . "', 'Notifiarr', '{\"apikey\":{\"label\":\"API Key\",\"description\":\"The Notifiarr API key from your profile (integration specific or global)\",\"type\":\"text\",\"required\":\"true\"}}'),
            ('" . NotificationPlatforms::TELEGRAM . "', 'Telegram', '{\"botToken\":{\"label\":\"Bot token\",\"description\":\"The token for your bot (google: how to create a telegram bot via godfather)\",\"type\":\"text\",\"required\":\"true\"}, \"chatId\":{\"label\":\"Chat id\",\"description\":\"The chat id for the channel where messages go, should start with -100 (google: gist nafiesl get-chat-id-for-a-channel)\",\"type\":\"text\",\"required\":\"true\"}}'),
            ('" . NotificationPlatforms::MATTERMOST . "', 'Mattermost', '{\"url\":{\"label\":\"Webhook URL\",\"description\":\"The url in Mattermost after adding a webhook\",\"type\":\"text\",\"required\":\"true\"},\"username\":{\"label\":\"Username\",\"description\":\"Optional display name for incoming webhook messages in Mattermost (default: Notifiarr)\",\"type\":\"text\"}}')";
}

//-- NOTIFICATION_TRIGGER_TABLE
$q[] = "DROP TABLE IF EXISTS `" . NOTIFICATION_TRIGGER_TABLE . "`;";
$q[] = "CREATE TABLE `" . NOTIFICATION_TRIGGER_TABLE . "` (
        `id` int(11) NOT NULL,
        `name` varchar(150) NOT NULL,
        `label` varchar(150) NOT NULL,
        `description` text NOT NULL,
        `event` varchar(100) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$q[] = "ALTER TABLE `" . NOTIFICATION_TRIGGER_TABLE . "`
        ADD PRIMARY KEY (`id`),
        ADD UNIQUE KEY `name` (`name`);";
$q[] = "ALTER TABLE `" . NOTIFICATION_TRIGGER_TABLE . "`
        MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";

if (SQLITE_MIGRATION) { //-- EXISTING INSTALL
    $sql = $db->query("SELECT * FROM " . NOTIFICATION_TRIGGER_TABLE);
    while ($row = $database->fetchAssoc($sql)) {
        $q[] = "INSERT INTO " . NOTIFICATION_TRIGGER_TABLE . " 
                (`id`, `name`, `label`, `description`, `event`) 
                VALUES 
                ('" . $row['id'] . "', '" . $database->prepare($row['name']) . "', '" . $database->prepare($row['label']) . "', '" . $database->prepare($row['description']) . "', '" . $database->prepare($row['event']) . "')";
    }
} else { //--- NEW INSTALL
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
            ('health', 'Health change', 'Send a notification when container becomes unhealthy', 'health'),
            ('security', 'Vulnerability found', 'Send a notification when a new vulnerability has been found or updated in a container image (enable Trivy in Settings)', 'security')";
}

//-- NOTIFICATION_LINK_TABLE
$q[] = "DROP TABLE IF EXISTS `" . NOTIFICATION_LINK_TABLE . "`;";
$q[] = "CREATE TABLE `" . NOTIFICATION_LINK_TABLE . "` (
        `id` int(11) NOT NULL,
        `name` varchar(150) NOT NULL,
        `platform` smallint(6) NOT NULL,
        `platform_parameters` text NOT NULL,
        `trigger_ids` text NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$q[] = "ALTER TABLE `" . NOTIFICATION_LINK_TABLE . "`
        ADD PRIMARY KEY (`id`),
        ADD UNIQUE KEY `name` (`name`);";
$q[] = "ALTER TABLE `" . NOTIFICATION_LINK_TABLE . "`
        MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";

if (SQLITE_MIGRATION) { //-- EXISTING INSTALL
    $sql = $db->query("SELECT * FROM " . NOTIFICATION_LINK_TABLE);
    while ($row = $database->fetchAssoc($sql)) {
        $q[] = "INSERT INTO " . NOTIFICATION_LINK_TABLE . " 
                (`id`, `name`, `platform`, `platform_parameters`, `trigger_ids`) 
                VALUES 
                ('" . $row['id'] . "', '" . $database->prepare($row['name']) . "', '" . $row['platform_id'] . "', '" . $database->prepare($row['platform_parameters']) . "', '" . $database->prepare($row['trigger_ids']) . "')";
    }
}

//-- SERVERS_TABLE
$q[] = "DROP TABLE IF EXISTS `" . SERVERS_TABLE . "`;";
$q[] = "CREATE TABLE `" . SERVERS_TABLE . "` (
        `id` int(11) NOT NULL,
        `name` varchar(150) NOT NULL,
        `url` text NOT NULL,
        `apikey` varchar(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$q[] = "ALTER TABLE `" . SERVERS_TABLE . "`
        ADD PRIMARY KEY (`id`),
        ADD UNIQUE KEY `name` (`name`)";
$q[] = "ALTER TABLE `" . SERVERS_TABLE . "`
        MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";

if (SQLITE_MIGRATION) { //-- EXISTING INSTALL
    $sql = $db->query("SELECT * FROM " . SERVERS_TABLE);
    while ($row = $database->fetchAssoc($sql)) {
        $q[] = "INSERT INTO " . SERVERS_TABLE . " 
                (`id`, `name`, `url`, `apikey`) 
                VALUES 
                ('" . $row['id'] . "', '" . $database->prepare($row['name']) . "', '" . $database->prepare($row['url']) . "', '" . $database->prepare($row['apikey']) . "')";
    }
} else { //--- NEW INSTALL
    $q[] = "INSERT INTO " . SERVERS_TABLE . "
            (`id`, `name`, `url`, `apikey`)
            VALUES
            ('1', '" . APP_NAME . "', '" . APP_SERVER_URL . "', '" . (defined('INITIAL_API_KEY') ? INITIAL_API_KEY : generateApikey()) . "')";
}

//-- SETTINGS_TABLE
$q[] = 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"';
$q[] = 'START TRANSACTION;';

$q[] = "DROP TABLE IF EXISTS `settings`;";
$q[] = "CREATE TABLE `" . SETTINGS_TABLE . "` (
        `id` int(11) NOT NULL,
        `name` varchar(150) NOT NULL,
        `value` text NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$q[] = "ALTER TABLE `" . SETTINGS_TABLE . "`
        ADD PRIMARY KEY (`id`),
        ADD UNIQUE KEY `name` (`name`);";
$q[] = "ALTER TABLE `" . SETTINGS_TABLE . "`
        MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";

if (SQLITE_MIGRATION) { //-- EXISTING INSTALL
    $sql = $db->query("SELECT * FROM " . SETTINGS_TABLE);
    while ($row = $database->fetchAssoc($sql)) {
        $q[] = "INSERT INTO " . SETTINGS_TABLE . " 
                (`name`, `value`) 
                VALUES 
                ('" . $database->prepare($row['name']) . "', '" . $database->prepare($row['value']) . "')";
    }
} else { //--- NEW INSTALL
    $settingRows = [];

    $globalSettings = [
        'serverName'               => '',
        'maintenanceIP'            => '',
        'maintenancePort'          => 9998,
        'loginFailures'            => 6,
        'loginTimeout'             => 60,
        'updates'                  => 2,
        'updatesFrequency'         => '0 2 * * *',
        'autoPruneImages'          => false,
        'autoPruneVolumes'         => false,
        'autoPruneNetworks'        => false,
        'autoPruneHour'            => 12,
        'cpuThreshold'             => '',
        'cpuAmount'                => '',
        'memThreshold'             => '',
        'sseEnabled'               => false,
        'cronLogLength'            => 1,
        'notificationLogLength'    => 1,
        'uiLogLength'              => 1,
        'apiLogLength'             => 1,
        'environment'              => 0,
        'overrideBlacklist'        => false,
        'externalLoading'          => 0,
        'taskStatsDisabled'        => 0,
        'taskStateDisabled'        => 0,
        'taskPullsDisabled'        => 0,
        'taskHousekeepingDisabled' => 0,
        'taskHealthDisabled'       => 0,
        'taskPruneDisabled'        => 0,
        'remoteServerTimeout'      => REMOTE_SERVER_TIMEOUT, //-- ADDED IN MIGRATION 002
        'stateCronTime'            => DEFAULT_STATE_CRON_TIME, //-- ADDED IN MIGRATION 003
        'currentPage'              => 'overview', //-- ADDED IN MIGRATION 004
        'telemetry'                => 1, //-- ADDED IN MIGRATION 006
        'overviewLayout'           => 1, //-- ADDED IN MIGRATION 008
        'defaultPage'              => 'overview', //-- ADDED IN MIGRATION 008
        'defaultTheme'             => 'darkly', //-- ADDED IN MIGRATION 011
        'defaultThemeMode'         => 'dark', //-- ADDED IN MIGRATION 011
        'usageMetricsRetention'    => 0, //-- ADDED IN MIGRATION 012
        'websocketPort'            => '9910', //-- ADDED IN MIGRATION 013
        'websocketUrl'             => '', //-- ADDED IN MIGRATION 014
        'containerGui'             => 1, //-- ADDED IN MIGRATION 015
        'taskCommandsDisabled'     => 0, //-- ADDED IN MIGRATION 016
        'securityScanner'          => 0, //-- ADDED IN MIGRATION 020
        'securitySnykAPIKey'       => '', //-- ADDED IN MIGRATION 020
        'securitySkipStopped'      => 1, //-- ADDED IN MIGRATION 021
    ];

    foreach ($globalSettings as $key => $val) {
        $settingRows[] = "('" . $key . "', '" . $val . "')";
    }

    $q[] = "INSERT INTO " . SETTINGS_TABLE . "
            (`name`, `value`)
            VALUES " . implode(', ', $settingRows);
}

$q[]   = 'COMMIT;';
$error = false;
foreach ($q as $sql) {
    logger(MIGRATION_LOG, '<span class="text-success">[Q]</span> ' . preg_replace('!\s+!', ' ', $sql));

    try {
        $database->mysqli_query($sql);
    } catch (Exception $e) {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $e, 'error');

        //-- IGNORE OLD DATABASE UNIQUE CONSTRAINT ISSUS
        if (!str_contains_any($e, ['Duplicate entry', 'UNIQUE constraint failed'])) {
            $error = true;
        }
    }

    if ($database->error() != 'not an error') {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $database->error(), 'error');
        $error = true;
    } else {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> query applied!');
    }
}

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
if (!$error) {
    $sql = "UPDATE " . SETTINGS_TABLE . "
            SET value = '023'
            WHERE name = 'migration'";
    $database->mysqli_query($sql);

    if ($database->error() != 'not an error') {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $database->error(), 'error');
        $error = true;
    } else {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> query applied!');
    }
} else {
    logger(MIGRATION_LOG, 'A migration error occurred, please check the migration log for details');
}
