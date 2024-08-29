<?php

/*
----------------------------------
 ------  Created: 082624   ------
 ------  Austin Best	   ------
----------------------------------
*/

class Shell
{
    public function __construct()
    {

    }

    public function __tostring()
    {
        return 'Class loaded: Shell';
    }

    public function exec($cmd)
    {
        return shell_exec($cmd);
    }

    public function prepare($arg)
    {
        $prepared = preg_replace("/[^A-Za-z0-9 -_]/", '', $arg);

        return $prepared;
    }
}
