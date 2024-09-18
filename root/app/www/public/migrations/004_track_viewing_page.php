<?php

/*
----------------------------------
 ------  Created: 091724   ------
 ------  Austin Best	   ------
----------------------------------
*/

$q = [];
$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`) 
        VALUES 
        ('currentPage', 'overview')
        ON CONFLICT(`name`) DO UPDATE SET value = 'overview' WHERE name = 'currentPage'";

$q[] = "DELETE FROM " . SETTINGS_TABLE . "
        WHERE name = 'externalLoading'";

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '004'
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
