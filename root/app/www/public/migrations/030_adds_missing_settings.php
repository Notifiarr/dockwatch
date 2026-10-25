<?php

/*
----------------------------------
 ------  Created: 102526   ------
 ------  Austin Best	   ------
----------------------------------
*/

//-- INSERT ONLY WHEN THE NAME IS MISSING SO EXISTING INSTALLS KEEP THEIR VALUES
$missingSettings = [
    'securityEnabled'    => '0',
    'securityScanHour'   => '12',
    'securityScanLength' => '2',
    'migration'          => '030',
];

$q = [];

foreach ($missingSettings as $name => $value) {
    $q[] = "INSERT INTO " . SETTINGS_TABLE . "
            (`name`, `value`)
            VALUES
            ('" . $name . "', '" . $value . "')";
}

$error = false;

foreach ($q as $query) {
    logger(MIGRATION_LOG, '<span class="text-success">[Q]</span> ' . preg_replace('!\s+!', ' ', $query));

    $database->mysqli_query($query);

    if ($migrationError = $database->mysqli_error()) {
        if (str_contains($migrationError, 'Duplicate entry')) {
            logger(MIGRATION_LOG, '<span class="text-info">[R]</span> already exists', 'warn');
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
            SET value = '030'
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
