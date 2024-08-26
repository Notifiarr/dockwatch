<?php

/*
----------------------------------
 ------  Created: 010524   ------
 ------  Austin Best	   ------
----------------------------------
*/

function regctlCheck($image)
{
    global $shell;

    if (!file_exists(REGCTL_PATH . REGCTL_BINARY)) {
        return 'Error: regctl binary (\'' . REGCTL_PATH . REGCTL_BINARY . '\') is not avaialable or there is a permissions error';
    }

    $regctl = $shell->exec(REGCTL_PATH . REGCTL_BINARY . ' image digest --list ' . $image);

    //-- RETRY
    if (!$regctl) {
        sleep(3);
        $regctl = $shell->exec(REGCTL_PATH . REGCTL_BINARY . ' image digest --list ' . $image);
    }

    return $regctl;
}
