<?php

/*
----------------------------------
 ------  Created: 071026   ------
 ------  nzxl       	   ------
----------------------------------
*/

$q   = [];
$q[] = "CREATE TABLE IF NOT EXISTS `" . SECURITY_SCANS_TABLE . "` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `image_name` varchar(500) NOT NULL,
        `image_hash` varchar(100) NOT NULL,
        `scan_file`  varchar(500) NOT NULL,
        `created_at` int(11)      NOT NULL,
        PRIMARY KEY (`id`),
        KEY `image_name` (`image_name`),
        KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

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
            SET value = '027'
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
