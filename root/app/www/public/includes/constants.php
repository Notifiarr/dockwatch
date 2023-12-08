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

//-- FOLDERS
define('APP_DATA_PATH', '/config/');
define('BACKUP_PATH', APP_DATA_PATH . 'backups/');
define('LOGS_PATH', APP_DATA_PATH . 'logs/');

//-- DATA FILES
define('SERVERS_FILE', APP_DATA_PATH . 'servers.json');
define('LOGIN_FILE', APP_DATA_PATH . 'logins');
define('SETTINGS_FILE', APP_DATA_PATH . 'settings.json');
define('STATE_FILE', APP_DATA_PATH . 'state.json');
define('PULL_FILE', APP_DATA_PATH . 'pull.json');
define('LOGO_FILE', APP_DATA_PATH . 'logos.json');
define('HEALTH_FILE', APP_DATA_PATH . 'health.json');
define('INTERNAL_ICON_ALIAS_FILE', 'container-alias.json');
define('EXTERNAL_ICON_ALIAS_FILE', APP_DATA_PATH . 'container-alias.json');

//-- LOG FILES
define('SYSTEM_LOG', LOGS_PATH . 'system/' . date('Ymd') . '.log');
define('UI_LOG', LOGS_PATH . 'ui/' . date('Ymd') . '.log');
define('API_LOG', LOGS_PATH . 'api/' . date('Ymd') . '.log');
define('CRON_HOUSEKEEPER_LOG', LOGS_PATH . 'crons/housekeeper-' . date('Ymd_Hi') . '.log');
define('CRON_PRUNE_LOG', LOGS_PATH . 'crons/prune-' . date('Ymd_Hi') . '.log');
define('CRON_PULLS_LOG', LOGS_PATH . 'crons/pulls-' . date('Ymd_Hi') . '.log');
define('CRON_STATE_LOG', LOGS_PATH . 'crons/state-' . date('Ymd_Hi') . '.log');
define('CRON_HEALTH_LOG', LOGS_PATH . 'crons/health-' . date('Ymd_Hi') . '.log');

//-- MEMCACHE
define('MEMCACHE_PREFIX', 'dockwatch-' . substr(md5($_SERVER['SERVER_NAME']), 0, 10) . '-');
define('MEMCACHE_DOCKER_STATS', 10);
define('MEMCACHE_DOCKER_PROCESS', 10);
define('MEMCACHE_DOCKER_INSPECT', 10);

//-- WHAT DATA TO GET WHEN VIEWING A PAGE
$getStats   = ['overview', 'containers'];
$getProc    = ['overview', 'containers', 'notifications'];
$getInspect = ['overview', 'containers'];