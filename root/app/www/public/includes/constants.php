<?php

/*
----------------------------------
 ------  Created: 111723   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('APP_NAME', 'DockWatch');

define('ICON_REPO', 'Notifiarr/images');
define('ICON_URL', 'https://gh.notifiarr.com/images/icons/');

//-- PATHS
define('LOGIN_FILE', '/config/logins');
define('SETTINGS_FILE', '/config/settings.json');
define('STATE_FILE', '/config/state.json');
define('PULL_FILE', '/config/pull.json');
define('LOGS_PATH', '/config/logs/');
define('LOGO_FILE', '/config/logos.json');
define('INTERNAL_ICON_ALIAS_FILE', 'container-alias.json');
define('EXTERNAL_ICON_ALIAS_FILE', '/config/container-alias.json');

//-- MEMCACHE
define('MEMCACHE_PREFIX', 'dockwatch-' . substr(md5($_SERVER['SERVER_NAME']), 0, 10) . '-');
define('MEMCACHE_DOCKER_STATS', 10);
define('MEMCACHE_DOCKER_PROCESS', 10);
define('MEMCACHE_DOCKER_INSPECT', 10);

//-- WHAT DATA TO GET WHEN VIEWING A PAGE
$getStats   = ['overview', 'containers'];
$getProc    = ['overview', 'containers', 'notifications'];
$getInspect = ['overview', 'containers'];