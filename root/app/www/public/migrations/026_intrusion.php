<?php

/*
----------------------------------
 ------  Created: 070626   ------
 ------  Austin Best	   ------
----------------------------------
*/

$q   = [];
$q[] = "CREATE TABLE IF NOT EXISTS `" . INTRUSION_HISTORY_TABLE . "` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `type` varchar(50) NOT NULL,
        `created_at` int(11) NOT NULL,
        `ip` varchar(45) NOT NULL DEFAULT '',
        `method` varchar(10) NOT NULL DEFAULT '',
        `uri` text NOT NULL,
        `referrer` text NOT NULL,
        `user_agent` text NOT NULL,
        `username` varchar(150) NOT NULL DEFAULT '',
        `apikey` text NOT NULL,
        `container` varchar(255) NOT NULL DEFAULT '',
        `token` text NOT NULL,
        `details` text NOT NULL,
        PRIMARY KEY (`id`),
        KEY `type` (`type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$q[] = "INSERT INTO " . NOTIFICATION_TRIGGER_TABLE . "
        (`name`, `label`, `description`, `event`)
        VALUES
        ('intrusion', 'Intrusion attempts', 'Send a notification when a suspicious access attempt is detected (failed login, invalid API key, CSRF failure, etc.)', 'intrusion')";

$error = false;

foreach ($q as $query) {
    logger(MIGRATION_LOG, '<span class="text-success">[Q]</span> ' . preg_replace('!\s+!', ' ', $query));

    $database->mysqli_query($query);

    if ($migrationError = $database->mysqli_error()) {
        if (str_contains($migrationError, 'Duplicate entry')) {
            logger(MIGRATION_LOG, '<span class="text-warning">[W]</span> ' . $migrationError, 'warn');
        } else {
            logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $migrationError, 'error');
            $error = true;
        }
    } else {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> query applied!');
    }
}

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
if (!$error) {
    $sql = "UPDATE " . SETTINGS_TABLE . "
            SET value = '026'
            WHERE name = 'migration'";
    $database->mysqli_query($sql);

    if ($migrationError = $database->mysqli_error()) {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $migrationError, 'error');
        $error = true;
    } else {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> query applied!');
    }
} else {
    logger(MIGRATION_LOG, 'A migration error occurred, please check the migration log for details');
}
