<?php
/*
----------------------------------
 ------  Created: 022426   ------
 ------  nzxl	           ------
----------------------------------
*/

$q = [];

//-- TRIVY NOTIFICATION TRIGGER
$q[] = "INSERT INTO " . NOTIFICATION_TRIGGER_TABLE . "
        (`name`, `label`, `description`, `event`)
        VALUES
        ('security', 'Vulnerability found', 'Send a notification when a new vulnerability has been found or updated in a container image (enable Trivy in Settings)', 'security')";

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '019'
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
