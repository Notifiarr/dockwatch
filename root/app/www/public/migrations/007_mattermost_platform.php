<?php

/*
----------------------------------
 ------  Created: 111224   ------
 ------  Austin Best	   ------
----------------------------------
*/

$q = [];
$q[] = "INSERT INTO " . NOTIFICATION_PLATFORM_TABLE . "
        (`id`, `platform`, `parameters`) 
        VALUES 
        ('" . NotificationPlatforms::MATTERMOST . "', 'Mattermost', '{\"url\":{\"label\":\"Webhook URL\",\"description\":\"The url in Mattermost after adding a webhook\",\"type\":\"text\",\"required\":\"true\"}}')";

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '007'
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
