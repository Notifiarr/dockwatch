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
    use NotificationTemplates;
    use NotificationTests;
    use Notifiarr;
    use Telegram;

    protected $headers;
    protected $logpath;
    protected $serverName;
    protected $database;

    public function __construct()
    {
        global $platforms, $settingsTable, $database;

        $this->database     = $database ?? new Database();
        $this->logpath      = LOGS_PATH . 'notifications/';

        $settingsTable      = $settingsTable ?? apiRequest('database-getSettings');
        $this->serverName   = is_array($settingsTable) ? $settingsTable['serverName'] : '';
    }

    public function __toString()
    {
        return 'Notifications initialized';
    }

    public function sendTestNotification($linkId, $name)
    {
        $linkIds                    = [];
        $return                     = $notificationLinkData = '';
        $tests                      = $this->getTestPayloads();
        $notificationPlatformTable  = $this->database->getNotificationPlatforms();
        $notificationLinkTable      = $this->database->getNotificationLinks();

        foreach ($notificationLinkTable as $notificationLink) {
            if ($notificationLink['id'] == $linkId) {
                $notificationLinkData = $notificationLink;
                break;
            }
        }
        $notificationPlatform = $notificationPlatformTable[$notificationLinkData['platform_id']];

        $logfile = $this->logpath . $notificationPlatform['platform'] . '.log';
        logger($logfile, 'test notification request to ' . $notificationPlatform['platform']);
        logger($logfile, 'test=' . $name);
        logger($logfile, 'tests=' . json_encode($tests));
        logger($logfile, 'test payload=' . json_encode($tests[$name]));

        $result = $this->notify($linkId, $name, $tests[$name], true);
    
        if ($result['code'] != 200) {
            $return = 'Code ' . $result['code'] . ', ' . $result['error'];
        }
    
        return ['code' => $result['code'], 'result' => $return];
    }

    public function notify($linkId, $trigger, $payload, $test = false)
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
                    return $this->notifiarr($logfile, $platformParameters['apikey'], $payload, $test);
                case NotificationPlatforms::TELEGRAM:
                    return $this->telegram($logfile, $platformParameters['botToken'], $platformParameters['chatId'], $payload, $test);
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
