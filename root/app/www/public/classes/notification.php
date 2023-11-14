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

    public $platforms = ['Notifiarr'];

    public function __construct()
    {

    }

    public function __toString()
    {
        return 'Notifications initialized';
    }

    public function notify($platform, $payload)
    {
        switch ($platform) {
            case 'notifiarr':
                return $this->notifiarr($payload);
        }
    }

    public function getPlatforms()
    {
        return $this->platforms;
    }
}
