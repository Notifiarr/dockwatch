<?php

/*
----------------------------------
 ------  Created: 061626   ------
 ------  Austin Best	   ------
----------------------------------
*/

$q   = [];
$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`)
        VALUES
        ('logLevel', " . LOG_LEVEL_INFO . ")";

$error = false;

foreach ($q as $query) {
    logger(MIGRATION_LOG, '<span class="text-success">[Q]</span> ' . preg_replace('!\s+!', ' ', $query));

    $database->mysqli_query($query);

    if ($migrationError = $database->mysqli_error()) {
        if (str_contains_any($migrationError, ['Duplicate entry', 'UNIQUE constraint failed'])) {
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
            SET value = '024'
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
