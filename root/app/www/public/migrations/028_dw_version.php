<?php

/*
----------------------------------
 ------  Created: 071026   ------
 ------  nzxl       	   ------
----------------------------------
*/

$q   = [];
$q[] = "CREATE TABLE IF NOT EXISTS `" . VERSION_TABLE . "` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `version`    varchar(50) NOT NULL,
        `branch`     varchar(50) NOT NULL,
        `image_hash` varchar(100) NOT NULL,
        `updated_at` int(11)     NOT NULL,
        PRIMARY KEY (`id`)
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
            SET value = '028'
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
