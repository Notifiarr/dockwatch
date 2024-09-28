<?php

/*
----------------------------------
 ------  Created: 092824   ------
 ------  Austin Best	   ------
----------------------------------
*/

$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`) 
        VALUES 
        ('telemetry', 1)
        ON CONFLICT(`name`) DO UPDATE SET value = 1 WHERE name = 'telemetry'";

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '006'
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
