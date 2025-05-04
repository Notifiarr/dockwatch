<?php

/*
----------------------------------
 ------  Created: 050325   ------
 ------  Austin Best	   ------
----------------------------------
*/

switch ($path) {
    case 'container': //-- .../api/dockerAPI/container/$action
        switch (true) {
            case $IS_GET && $action == 'create':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $inspect = json_decode($docker->inspect($parameters['name'], false, true), true);
                if (!$inspect) {
                    apiResponse(400, ['error' => 'Failed to get inspect for container: ' . $parameters['name']]);
                }

                $apiRequestResponse = json_encode($docker->apiCreateContainer($inspect));
                break;
        }
        break;
}
