<?php

/*
----------------------------------
 ------  Created: 021124   ------
 ------  Austin Best	   ------
----------------------------------
*/

function gitBranch()
{
    if (!defined('DOCKWATCH_BRANCH')) {
        return 'Source';
    }
       
    return DOCKWATCH_BRANCH;
}

function gitHash()
{
    if (!defined('DOCKWATCH_COMMIT')) {
        return 'Unknown';
    }

    return DOCKWATCH_COMMIT;
}

function gitMessage()
{
    if (!defined('DOCKWATCH_COMMIT_MSG')) {
        return 'Unknown';
    }

    return DOCKWATCH_COMMIT_MSG;
}

function gitVersion($full = false)
{
    if (!defined('DOCKWATCH_COMMITS') && !defined('DOCKWATCH_BRANCH')) {
        return ($full ? 'v' : '') . '0.0.0 - ' . gitBranch();
    }

    if ($full) {
        return 'v' . APP_X . '.' . APP_Y . '.' . DOCKWATCH_COMMITS . ' - ' . gitBranch();
    }

    return APP_X . '.' . APP_Y . '.' . DOCKWATCH_COMMITS;
}
