<?php
/*
----------------------------------
 ------  Created: 032926   ------
 ------  nzxl	           ------
----------------------------------
*/

$q = [];

//-- ADD SKIP CONTAINER OPTION AND DELETE OLD TRIVY STUFF
$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`)
        VALUES
        ('securitySkipStopped', '1')";
$q[] = "DELETE FROM " . SETTINGS_TABLE . "
        WHERE name = 'trivyEnabled'";
$q[] = "DELETE FROM " . SETTINGS_TABLE . "
        WHERE name = 'trivyScanHour'";
$q[] = "DELETE FROM " . SETTINGS_TABLE . "
        WHERE name = 'trivyScanLength'";

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '021'
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
