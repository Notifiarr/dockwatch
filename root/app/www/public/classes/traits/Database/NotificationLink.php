<?php

/*
----------------------------------
 ------  Created: 090224   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait NotificationLink
{
    public function getNotificationLinks()
    {
        if ($this->notificationLinkTable) {
            return $this->notificationLinkTable;
        }
    
        $notificationLinkTable = [];

        $q = "SELECT *
              FROM " . NOTIFICATION_LINK_TABLE;
        $r = $this->query($q);
        while ($row = $this->fetchAssoc($r)) {
            $notificationLinkTable[$row['id']] = $row;
        }

        $this->notificationLinkTable = $notificationLinkTable;
        return $notificationLinkTable;
    }

    public function updateNotificationLink($linkId, $triggerIds, $platformParameters, $senderName)
    {
        $q = "UPDATE " . NOTIFICATION_LINK_TABLE . "
              SET name = '" . $this->prepare($senderName) . "', platform_parameters = '" . json_encode($platformParameters) . "', trigger_ids = '" . json_encode($triggerIds) . "'
              WHERE id = '" . intval($linkId) . "'";
        $this->query($q);

        $this->notificationLinkTable = '';
        return $this->getNotificationLinks();
    }

    public function addNotificationLink($platformId, $triggerIds, $platformParameters, $senderName)
    {
        $q = "INSERT INTO " . NOTIFICATION_LINK_TABLE . "
              (`name`, `platform_id`, `platform_parameters`, `trigger_ids`) 
              VALUES 
              ('" . $this->prepare($senderName) . "', '" . intval($platformId) . "', '" . json_encode($platformParameters) . "', '" . json_encode($triggerIds) . "')";
        $this->query($q);

        $this->notificationLinkTable = '';
        return $this->getNotificationLinks();
    }

    function deleteNotificationLink($linkId)
    {
        $q = "DELETE FROM " . NOTIFICATION_LINK_TABLE . "
              WHERE id = " . intval($linkId);
        $this->query($q);

        $this->notificationLinkTable = '';
        return $this->getNotificationLinks();
    }

    public function getNotificationLinkPlatformFromName($name)
    {
        $notificationLinks      = $this->getNotificationLinks();
        $notificationTrigger    = $this->getNotificationTriggerFromName($name);

        foreach ($notificationLinks as $notificationLink) {
            if ($notificationLink['name'] == $name) {
                $triggers = makeArray(json_decode($notificationLink['trigger_ids'], true));

                foreach ($triggers as $trigger) {
                    if ($trigger == $notificationTrigger['id']) {
                        return $notificationLink['platform_id'];
                    }
                }
            }
        }
    }
}
