<?php

/*
----------------------------------
 ------  Created: 090224   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait NotificationPlatform
{
    public function getNotificationPlatforms()
    {
        if ($this->notificationPlatformTable) {
            return $this->notificationPlatformTable;
        }

        $notificationPlatformTable = [];

        $q = "SELECT *
              FROM " . NOTIFICATION_PLATFORM_TABLE;
        $r = $this->mysqli_query($q);
        while ($row = $this->mysqli_fetchAssoc($r)) {
            $notificationPlatformTable[$row['id']] = $row;
        }

        $this->notificationPlatformTable = $notificationPlatformTable;
        return $notificationPlatformTable;
    }
}
