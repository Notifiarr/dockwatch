<?php

/*
----------------------------------
 ------  Created: 111723   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('SETTINGS_FILE', '/config/settings.json');
define('STATE_FILE', '/config/state.json');
define('PULL_FILE', '/config/pull.json');
define('LOGS_PATH', 'logs/');

//-- WHAT DATA TO GET WHEN VIEWING A PAGE
$getStats   = ['overview', 'containers'];
$getProc    = ['overview', 'containers', 'notifications'];
$getInspect = ['overview', 'containers'];