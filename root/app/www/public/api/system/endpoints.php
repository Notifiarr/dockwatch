<?php

/*
----------------------------------
 ------  Created: 050325   ------
 ------  Austin Best	   ------
----------------------------------
*/

switch ($path) {
    case 'memcache': //-- .../api/system/memcache/$action
        switch (true) {
            case $IS_GET && $action == 'get':
                if (!$parameters['key']) {
                    apiResponse(400, ['error' => 'Missing key parameter']);
                }

                $apiRequestResponse = memcacheGet($parameters['key']);
                break;
            case $IS_POST && $action == 'set':
                if (!$payload['key']) {
                    apiResponse(400, ['error' => 'Missing key parameter']);
                }
                if (!$payload['value']) {
                    apiResponse(400, ['error' => 'Missing value parameter']);
                }
                if (!$payload['seconds']) {
                    apiResponse(400, ['error' => 'Missing seconds parameter']);
                }

                memcacheSet($payload['key'], $payload['value'], $payload['seconds']);
                break;
        }
}
