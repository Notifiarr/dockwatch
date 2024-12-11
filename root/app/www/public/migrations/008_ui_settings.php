<?php

/*
----------------------------------
 ------  Created: 121014   ------
 ------  Austin Best	   ------
----------------------------------
*/

$q = [];
$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`) 
        VALUES 
        ('overviewLayout', '1')
        ON CONFLICT(`name`) DO UPDATE SET value = '1' WHERE name = 'overviewLayout'";

$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`) 
        VALUES 
        ('defaultPage', 'overview')
        ON CONFLICT(`name`) DO UPDATE SET value = 'overview' WHERE name = 'defaultPage'";

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '008'
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
