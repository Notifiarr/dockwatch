<?php

/*
----------------------------------
 ------  Created: 050325   ------
 ------  Austin Best	   ------
----------------------------------
*/

switch ($path) {
    case 'log': //-- .../api/server/log/$action
        switch (true) {
            case $IS_GET:
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $apiRequestResponse = viewLog($parameters['name']);
                break;
            case $IS_POST && $action == 'delete':
                if (!$payload['log']) {
                    apiResponse(400, ['error' => 'Missing log parameter']);
                }

                $apiRequestResponse = deleteLog($payload['log']);
                break;
            case $IS_POST && $action == 'purge':
                if (!$payload['group']) {
                    apiResponse(400, ['error' => 'Missing group parameter']);
                }

                $apiRequestResponse = purgeLogs($payload['group']);
                break;
        }
        break;
    case 'ping': //-- .../api/server/ping
        switch (true) {
            case $IS_GET:
                if (getFile(MIGRATION_FILE)) {
                    apiResponse(423, ['error' => 'Migration in progress']);
                }

                $apiRequestResponse = gitVersion(true);
                break;
        }
        break;
    case 'task': //-- .../api/server/task/$action
        switch (true) {
            case $IS_POST && $action == 'run':
                if (!$payload['task']) {
                    apiResponse(400, ['error' => 'Missing task parameter']);
                }

                $apiRequestResponse = executeTask($payload['task']);
                break;
        }
        break;
    case 'time': //-- .../api/server/time
        switch (true) {
            case $IS_GET:
                $apiRequestResponse = apiResponse(200, ['timezone' => date_default_timezone_get(), 'time' => date('c')]);
                break;
        }
        break;
}
