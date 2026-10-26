<?php

/*
----------------------------------
 ------  Created: 102526   ------
 ------  Austin Best	   ------
----------------------------------
*/

$missingSettings = [
    'securityEnabled'    => '0',
    'securityScanHour'   => '12',
    'securityScanLength' => '2',
    'migration'          => '030',
];

$error = false;

foreach ($missingSettings as $name => $value) {
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

    try {
        $database->mysqli_query($query);
    } catch (Exception $e) {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $e->getMessage(), 'error');
        $error = true;
        continue;
    }

    if ($migrationError = $database->mysqli_error()) {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $migrationError, 'error');
        $error = true;
    } elseif ($database->mysqli_affectedRows() === 0) {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> already exists', 'warn');
    } else {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> query applied!');
    }
}

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
if (!$error) {
    $sql = "UPDATE " . SETTINGS_TABLE . "
            SET value = '030'
            WHERE name = 'migration'";

    try {
        $database->mysqli_query($sql);
    } catch (Exception $e) {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $e->getMessage(), 'error');
        $error = true;
    }

    if (!$error && ($migrationError = $database->mysqli_error())) {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $migrationError, 'error');
        $error = true;
    } elseif (!$error) {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> query applied!');
    }
}

if ($error) {
    logger(MIGRATION_LOG, 'A migration error occurred, please check the migration log for details');
}
