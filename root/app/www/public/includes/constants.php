<?php

/*
----------------------------------
 ------  Created: 111723   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('APP_NAME', 'DockWatch');

//-- PATHS
define('LOGIN_FILE', '/config/logins');
define('SETTINGS_FILE', '/config/settings.json');
define('STATE_FILE', '/config/state.json');
define('PULL_FILE', '/config/pull.json');
define('LOGS_PATH', '/config/logs/');

//-- MEMCACHE
define('MEMCACHE_PREFIX', 'dockwatch-');
define('MEMCACHE_DOCKER_STATS', 10);
define('MEMCACHE_DOCKER_PROCESS', 10);
define('MEMCACHE_DOCKER_INSPECT', 10);

//-- WHAT DATA TO GET WHEN VIEWING A PAGE
$getStats   = ['overview', 'containers'];
$getProc    = ['overview', 'containers', 'notifications'];
$getInspect = ['overview', 'containers'];