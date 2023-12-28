<?php

/*
----------------------------------
 ------  Created: 112823   ------
 ------  Austin Best	   ------
----------------------------------
*/

function executeTask($task)
{
    $return = '[]';

    switch ($task) {
        case 'aliasFile':
            $external   = getFile(EXTERNAL_ICON_ALIAS_FILE);
            $internal   = getFile(ABSOLUTE_PATH . INTERNAL_ICON_ALIAS_FILE);
            $return     = json_encode(['external_file' => EXTERNAL_ICON_ALIAS_FILE, 'external_alias' => $external, 'internal_file' => ABSOLUTE_PATH . INTERNAL_ICON_ALIAS_FILE, 'internal_alias' => $internal], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            break;
        case 'state':
        case 'health':
        case 'housekeeper':
        case 'pulls':
        case 'prune':
            $cmd = '/usr/bin/php ' . ABSOLUTE_PATH . 'crons/' . $task . '.php';
            $return = shell_exec($cmd . ' 2>&1');
            break;
        case 'server':
            $return = 'cli:<br>';
            $return .= '/usr/bin/php -r \'print_r($_SERVER);\'<br><br>';
            $return .= 'browser:<br>';
            foreach ($_SERVER as $key => $val) {
                $return .= '[' . $key . '] => ' . $val . '<br>';
            }
            break;
        case 'session':
            foreach ($_SESSION as $key => $val) {
                $return .= '[' . $key . '] => ' . $val . '<br>';
            }
            break;
        case 'icons':
            getIcons(true);
            $return = 'The icon list has been refreshed';
            break;
        case 'pullFile':
            $pull   = getFile(PULL_FILE);
            $return = json_encode($pull, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            break;
        case 'stateFile':
            $state  = getFile(STATE_FILE);
            $return = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            break;
    }

    return $return;
}