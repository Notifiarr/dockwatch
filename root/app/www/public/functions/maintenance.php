<?php

/*
----------------------------------
 ------  Created: 042726   ------
 ------  Austin Best	   ------
----------------------------------
*/

function maintenanceGate($code, $message, $plainUtf8 = true)
{
    if (!IS_MAINTENANCE) {
        return;
    }

    if ($plainUtf8) {
        header('Content-Type: text/plain; charset=UTF-8');
    }

    http_response_code($code);
    exit($message);
}
