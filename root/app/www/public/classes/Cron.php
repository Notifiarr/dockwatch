<?php

/*
----------------------------------
 ------  Created: 102626   ------
 ------  Austin Best	   ------
----------------------------------
*/

//-- BRING IN THE EXTRAS
loadClassExtras('Cron');

class Cron
{
    use Dispatcher;

    protected $database;

    public function __construct()
    {
        global $database;

        $this->database = $database;
    }

    public function __toString()
    {
        return 'Cron initialized';
    }
}
