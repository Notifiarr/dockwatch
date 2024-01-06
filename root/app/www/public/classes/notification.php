<?php

/*
----------------------------------
 ------  Created: 111723   ------
 ------  Austin Best	   ------
----------------------------------
*/


//-- BRING IN THE TRAITS
$traits     = ABSOLUTE_PATH . 'classes/traits/notification/';
$traitsDir  = opendir($traits);
while ($traitFile = readdir($traitsDir)) {
    if (str_contains($traitFile, '.php')) {
        require $traits . $traitFile;
    }
}
closedir($traitsDir);

class Notifications
{
    use Notifiarr;

    protected $platforms;
    protected $platformSettings;
    protected $headers;
    protected $logpath;
    protected $serverName;
    public function __construct()
    {
        global $platforms, $settingsFile;

        if (!$settingsFile) {
            $settingsFile = getServerFile('settings');
            $settingsFile = $settingsFile['file'];
        }

        $this->platforms        = $platforms; //-- includes/platforms.php
        $this->platformSettings = $settingsFile['notifications']['platforms'];
        $this->logpath          = LOGS_PATH . 'notifications/';
        $this->serverName       = $settingsFile['global']['serverName'];
    }

    public function __toString()
    {
        return 'Notifications initialized';
    }

    public function notify($platform, $payload)
    {
        if ($this->serverName) {
            $payload['server']['name'] = $this->serverName;
        }

        $platformData   = $this->getNotificationPlatformFromId($platform);
        $logfile        = $this->logpath . $platformData['name'] . '.log';

        logger($logfile, 'notification request to ' . $platformData['name']);
        logger($logfile, 'notification payload: ' . json_encode($payload));

        /*
            Everything should return an array with code => ..., error => ... (if no error, just code is fine)
        */
        switch ($platform) {
            case 1: //-- Notifiarr
                return $this->notifiarr($logfile, $payload);
        }
    }

    public function getPlatforms()
    {
        return $this->platforms;
    }

    public function getNotificationPlatformFromId($platform)
    {
        return $this->platforms[$platform];
    }
}
