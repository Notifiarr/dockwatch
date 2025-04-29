<?php

/*
----------------------------------
 ------  Created: 112823   ------
 ------  Austin Best	   ------
----------------------------------
*/

function executeTask($task)
{
    global $database, $shell;

    $return = '[]';

    switch ($task) {
        case 'telemetry':
            return json_encode(telemetry(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        case 'processList';
            $getExpandedProcessList = getExpandedProcessList(true, true, true);
            $processList            = $getExpandedProcessList['processList'];

            return json_encode($processList, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        case 'aliasFile':
            $external   = getFile(EXTERNAL_ICON_ALIAS_FILE);
            $internal   = getFile(ABSOLUTE_PATH . INTERNAL_ICON_ALIAS_FILE);

            return json_encode(['external_file' => EXTERNAL_ICON_ALIAS_FILE, 'external_alias' => $external, 'internal_file' => ABSOLUTE_PATH . INTERNAL_ICON_ALIAS_FILE, 'internal_alias' => $internal], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        case 'state':
        case 'health':
        case 'housekeeper':
        case 'pulls':
        case 'prune':
        case 'stats':
        case 'commands':
            $cmd = '/usr/bin/php ' . ABSOLUTE_PATH . 'crons/' . $task . '.php';

            return $shell->exec($cmd . ' 2>&1');
        case 'server':
            $return = 'cli:<br>';
            $return .= '/usr/bin/php -r \'print_r($_SERVER);\'<br><br>';
            $return .= 'browser:<br>';

            foreach ($_SERVER as $key => $val) {
                $return .= '[' . $key . '] => ' . $val . '<br>';
            }

            return $return;
        case 'session':
            foreach ($_SESSION as $key => $val) {
                $return .= '[' . $key . '] => ' . $val . '<br>';
            }

            return $return;
        case 'icons':
            getIcons(true);
            return 'The icon list has been refreshed';
        case 'pullFile':
            return json_encode(getFile(PULL_FILE), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        case 'stateFile':
            return json_encode(getFile(STATE_FILE), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        case 'dependencyFile':
            return json_encode(getFile(DEPENDENCY_FILE), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        case 'containersList':
            return json_encode(getContainerStats(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        case 'overviewStats':
            return json_encode(getOverviewStats(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        case 'metrics':
            return json_encode(getUsageMetrics(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        default:
            return 'Invalid task requested (task=' . $task . ')';
    }
}
