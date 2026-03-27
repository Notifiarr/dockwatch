<?php
/*
----------------------------------
 ------  Created: 032726   ------
 ------  nzxl	           ------
----------------------------------
*/

$q = [];

//-- RENAME TRIVY SETTINGS AND ADD SCANNER
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET name = 'securityEnabled'
        WHERE name = 'trivyEnabled'";
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET name = 'securityScanHour'
        WHERE name = 'trivyScanHour'";
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET name = 'securityScanLength'
        WHERE name = 'trivyScanLength'";
$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`)
        VALUES
        ('securityScanner', '0')";
$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`)
        VALUES
        ('securitySnykAPIKey', '')";

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '020'
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
