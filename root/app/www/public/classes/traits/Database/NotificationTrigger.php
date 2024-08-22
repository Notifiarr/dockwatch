<?php

/*
----------------------------------
 ------  Created: 090224   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait NotificationTrigger
{
    public function getNotificationTriggers()
    {
        if ($this->notificationTriggersTable) {
            return $this->notificationTriggersTable;
        }

        $notificationTriggersTable = [];

        $q = "SELECT *
              FROM " . NOTIFICATION_TRIGGER_TABLE;
        $r = $this->query($q);
        while ($row = $this->fetchAssoc($r)) {
            $notificationTriggersTable[$row['id']] = $row;
        }

        $this->notificationTriggersTable = $notificationTriggersTable;
        return $notificationTriggersTable;
    }

    public function getNotificationTriggerFromName($name)
    {
        $triggers = $this->getNotificationTriggers();

        foreach ($triggers as $trigger) {
            if (str_compare($name, $trigger['name'])) {
                return $trigger;
            }
        }

        return [];
    }

    public function isNotificationTriggerEnabled($name)
    {
        $notificationLinks      = $this->getNotificationLinks();
        $notificationTrigger    = $this->getNotificationTriggerFromName($name);

        foreach ($notificationLinks as $notificationLink) {
            $triggers = makeArray(json_decode($notificationLink['trigger_ids'], true));

            foreach ($triggers as $trigger) {
                if ($trigger == $notificationTrigger['id']) {
                    return true;
                }
            }
        }

        return false;
    }
}
