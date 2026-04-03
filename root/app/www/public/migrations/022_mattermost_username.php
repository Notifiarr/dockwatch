<?php

/*
----------------------------------
 ------  Created: 040326   ------
 ------  Austin Best	   ------
----------------------------------
*/

$q   = [];
$q[] = "UPDATE " . NOTIFICATION_PLATFORM_TABLE . "
        SET parameters = '{\"url\":{\"label\":\"Webhook URL\",\"description\":\"The url in Mattermost after adding a webhook\",\"type\":\"text\",\"required\":\"true\"},\"username\":{\"label\":\"Username\",\"description\":\"Optional display name for incoming webhook messages in Mattermost (default: Notifiarr)\",\"type\":\"text\"}}'
        WHERE id = " . NotificationPlatforms::MATTERMOST;

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '022'
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
