<?php
/*
----------------------------------
 ------  Created: 021626   ------
 ------  nzxl	             ------
----------------------------------
*/

$q = [];

//-- TRIVY SETTINGS
$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`)
        VALUES
        ('trivyEnabled', '0')";
$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`)
        VALUES
        ('trivyScanHour', '12')";
$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`)
        VALUES
        ('trivyScanLength', '2')";

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '018'
        WHERE name = 'migration'";

foreach ($q as $query) {
    logger(MIGRATION_LOG, '<span class="text-success">[Q]</span> ' . preg_replace('!\s+!', ' ', $query));

    $database->query($query);

    if ($database->error() != 'not an error') {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $database->error(), 'error');
    } else {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> query applied!');
    }
}
