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

function gitVersion()
{
    if (!defined('DOCKWATCH_COMMITS') && !defined('DOCKWATCH_BRANCH')) {
        return '0.0.0';
    }

    return APP_X . '.' . APP_Y . '.' . DOCKWATCH_COMMITS . ' - ' . DOCKWATCH_BRANCH;
}
