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
    if (strpos($traitFile, '.php') !== false) {
        require $traits . $traitFile;
    }
}
closedir($traitsDir);

class Notifications
{
    use Notifiarr;

    protected $platforms;
    protected $platformSettings;
    private $headers;
    private $logfile;
    public function __construct()
    {
        global $platforms;

        $settings = getFile(SETTINGS_FILE);

        $this->platforms        = $platforms; //-- includes/platforms.php
        $this->platformSettings = $settings['notifications']['platforms'];
        $this->logfile          = LOGS_PATH . 'notifications/';
    }

    public function __toString()
    {
        return 'Notifications initialized';
    }

    public function notify($platform, $payload)
    {
        $platformData = $this->getNotificationPlatformFromId($platform);
        $this->logfile = $this->logfile . $platformData['name'] . '-'. date('Ymd') .'.log';

        logger($this->logfile, 'notification request to ' . $platformData['name'], 'info');
        logger($this->logfile, 'notification payload: ' . json_encode($payload), 'info');

        /*
            Everything should return an array with code => ..., error => ... (if no error, just code is fine)
        */
        switch ($platform) {
            case 1: //-- Notifiarr
                return $this->notifiarr($payload);
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
