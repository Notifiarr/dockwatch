<?php

/*
----------------------------------
 ------  Created: 042726   ------
 ------  Austin Best	   ------
----------------------------------
*/

function maintenanceGate($code, $message)
{
    if (!IS_MAINTENANCE) {
        return;
    }

    http_response_code($code);
    exit($message);
}
