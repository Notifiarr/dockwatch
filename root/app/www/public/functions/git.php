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
        return 'Unknown';
    } else {
        return DOCKWATCH_BRANCH;
    }
}

function gitHash()
{
    if (!defined('DOCKWATCH_COMMIT')) {
        return 'Unknown';
    } else {
        return DOCKWATCH_COMMIT;
    }
}

function gitMessage()
{
    if (!defined('DOCKWATCH_COMMIT_MSG')) {
        return 'Unknown';
    } else {
        return DOCKWATCH_COMMIT_MSG;
    }
}
