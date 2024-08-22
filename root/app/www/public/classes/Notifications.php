<?php

/*
----------------------------------
 ------  Created: 111723   ------
 ------  Austin Best	   ------
----------------------------------
*/

//-- BRING IN THE EXTRAS
loadClassExtras('Notifications');

class Notifications
{
    use Notifiarr;
    use NotificationTemplates;

    protected $platforms;
    protected $headers;
    protected $logpath;
    protected $serverName;
    protected $database;

    public function __construct()
    {
        global $platforms, $settingsTable, $database;

        $this->database     = $database ?? new Database();
        $this->platforms    = $platforms; //-- includes/platforms.php
        $this->logpath      = LOGS_PATH . 'notifications/';

        $settingsTable      = $settingsTable ?? apiRequest('database-getSettings');
        $this->serverName   = is_array($settingsTable) ? $settingsTable['serverName'] : '';
    }

    public function __toString()
    {
        return 'Notifications initialized';
    }

    public function sendTestNotification($linkId)
    {    
        $payload    = ['event' => 'test', 'title' => APP_NAME . ' test', 'message' => 'This is a test message sent from ' . APP_NAME];
        $return     = '';
        $result     = $this->notify($linkId, 'test', $payload);
    
        if ($result['code'] != 200) {
            $return = 'Code ' . $result['code'] . ', ' . $result['error'];
        }
    
        return $return;
    }

    public function notify($linkId, $trigger, $payload)
    {
        $linkIds                    = [];
        $notificationPlatformTable  = $this->database->getNotificationPlatforms();
        $notificationTriggersTable  = $this->database->getNotificationTriggers();
        $notificationLinkTable      = $this->database->getNotificationLinks();
        $triggerFields              = $this->getTemplate($trigger);

        //-- MAKE IT MATCH THE TEMPLATE
        foreach ($payload as $payloadField => $payloadVal) {
            if (!array_key_exists($payloadField, $triggerFields) || !$payloadVal) {
                unset($payload[$payloadField]);
            }
        }

        if ($this->serverName) {
            $payload['server']['name'] = $this->serverName;
        }

        if ($linkId) {
            foreach ($notificationLinkTable as $notificationLink) {
                if ($notificationLink['id'] == $linkId) {
                    $linkIds[] = $notificationLink;
                }
            }

            $notificationLink       = $notificationLinkTable[$linkId];
            $notificationPlatform   = $notificationPlatformTable[$notificationLink['platform_id']];
        } else {
            foreach ($notificationTriggersTable as $notificationTrigger) {
                if ($notificationTrigger['name'] == $trigger) {
                    foreach ($notificationLinkTable as $notificationLink) {
                        $triggers = makeArray(json_decode($notificationLink['trigger_ids'], true));

                        foreach ($triggers as $trigger) {
                            if ($trigger == $notificationTrigger['id']) {
                                $linkIds[] = $notificationLink;
                            }
                        }
                    }
                    break;
                }
            }
        }

        foreach ($linkIds as $linkId) {
            $platformId         = $linkId['platform_id'];
            $platformParameters = json_decode($linkId['platform_parameters'], true);
            $platformName       = '';

            foreach ($notificationPlatformTable as $notificationPlatform) {
                if ($notificationPlatform['id'] == $platformId) {
                    $platformName = $notificationPlatform['platform'];
                    break;
                }
            }

            $logfile = $this->logpath . $platformName . '.log';
            logger($logfile, 'notification request to ' . $platformName);
            logger($logfile, 'notification payload: ' . json_encode($payload));

            switch ($platformId) {
                case NotificationPlatforms::NOTIFIARR:
                    return $this->notifiarr($logfile, $platformParameters['apikey'], $payload);
            }
        }
    }

    public function getNotificationPlatformNameFromId($id, $platforms)
    {
        foreach ($platforms as $platform) {
            if ($id == $platform['id']) {
                return $platform['platform'];
            }
        }
    }

    public function getNotificationTriggerNameFromId($id, $triggers)
    {
        foreach ($triggers as $trigger) {
            if ($id == $trigger['id']) {
                return $trigger['label'];
            }
        }
    }
}
