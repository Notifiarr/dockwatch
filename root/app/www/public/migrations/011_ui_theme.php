<?php

/*
----------------------------------
 ------  Created: 020825   ------
 ------  Austin Best	   ------
----------------------------------
*/

$q = [];
$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`) 
        VALUES 
        ('defaultTheme', 'darkly')
        ON CONFLICT(`name`) DO UPDATE SET value = 'darkly' WHERE name = 'defaultTheme'";

$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`) 
        VALUES 
        ('defaultThemeMode', 'dark')
        ON CONFLICT(`name`) DO UPDATE SET value = 'overview' WHERE name = 'defaultPage'";

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '011'
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
