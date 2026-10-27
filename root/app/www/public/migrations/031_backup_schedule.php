<?php

/*
----------------------------------
 ------  Created: 102626   ------
 ------  Austin Best	   ------
----------------------------------
*/

$newSettings = [
    'backupTime' => '03:00',
    'backupKeep' => '7',
];

$error = false;

foreach ($newSettings as $name => $value) {
    //-- INSERT ONLY WHEN THE NAME IS MISSING SO EXISTING INSTALLS KEEP THEIR VALUES
    $query = "INSERT INTO " . SETTINGS_TABLE . "
            (`name`, `value`)
            SELECT '" . $name . "', '" . $value . "'
            WHERE NOT EXISTS (
                SELECT 1
                FROM " . SETTINGS_TABLE . "
                WHERE `name` = '" . $name . "'
            )";

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

$query = "INSERT INTO " . NOTIFICATION_TRIGGER_TABLE . "
        (`name`, `label`, `description`, `event`)
        VALUES
        ('backup', 'Backup', 'Sent when a database backup finishes', 'backup')";

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

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
if (!$error) {
    $sql = "UPDATE " . SETTINGS_TABLE . "
            SET value = '031'
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
