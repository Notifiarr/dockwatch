<?php

/*
----------------------------------
 ------  Created: 111723   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('APP_NAME', 'Dockwatch');
define('APP_IMAGE', 'ghcr.io/notifiarr/dockwatch:main');
define('APP_PORT', 9999);
define('APP_SERVER_ID', 1);
define('APP_SERVER_URL', 'http://localhost');
define('APP_MAINTENANCE_IMAGE', 'ghcr.io/notifiarr/dockwatch:develop');
define('APP_MAINTENANCE_PORT', 9998);
define('APP_WEBSOCKET_PORT', 9910);
define('APP_BACKUPS', 7); //-- DAYS
define('APP_X', 0);
define('APP_Y', 6);
define('ICON_REPO', 'Notifiarr/images');
define('ICON_URL', 'https://gh.notifiarr.com/images/icons/');

//-- REMOTES
define('DEFAULT_REMOTE_SERVER_TIMEOUT', 20);

//-- TELEMETRY
define('TELEMETRY_URL', 'https://notifiarr.com/api/v1/system/dockwatch-telemetry/dockwatc-htel-emet-ryen-dpoint-2024-'); //-- THIS APIKEY HAS BEEN HARDCODED TO ONLY WORK ON THIS ENDPOINT

//-- DATABASE
define('DATABASE_NAME', 'dockwatch.db');
define('SETTINGS_TABLE', 'settings');
define('SERVERS_TABLE', 'servers');
define('CONTAINER_SETTINGS_TABLE', 'container_settings');
define('CONTAINER_GROUPS_TABLE', 'container_groups');
define('CONTAINER_GROUPS_LINK_TABLE', 'container_group_link');
define('NOTIFICATION_PLATFORM_TABLE', 'notification_platform');
define('NOTIFICATION_TRIGGER_TABLE', 'notification_trigger');
define('NOTIFICATION_LINK_TABLE', 'notification_link');

//-- CRON
define('DEFAULT_CRON', '0 0 * * *');
define('DEFAULT_STATE_CRON_TIME', 5);

//-- FOLDERS
define('APP_DATA_PATH', '/config/');
define('BACKUP_PATH', APP_DATA_PATH . 'backups/');
define('LOGS_PATH', APP_DATA_PATH . 'logs/');
define('TMP_PATH', APP_DATA_PATH . 'tmp/');
define('COMPOSE_PATH', APP_DATA_PATH . 'compose/');
define('DATABASE_PATH', APP_DATA_PATH . 'database/');
define('MIGRATIONS_PATH', ABSOLUTE_PATH . 'migrations/');

//-- DATA FILES
define('SERVERS_FILE', APP_DATA_PATH . 'servers.json');
define('LOGIN_FILE', APP_DATA_PATH . 'logins');
define('LOGIN_FAILURE_FILE', APP_DATA_PATH . 'login_failures');
define('SETTINGS_FILE', APP_DATA_PATH . 'settings.json');
define('STATE_FILE', APP_DATA_PATH . 'state.json');
define('PULL_FILE', APP_DATA_PATH . 'pull.json');
define('LOGO_FILE', APP_DATA_PATH . 'logos.json');
define('HEALTH_FILE', APP_DATA_PATH . 'health.json');
define('INTERNAL_ICON_ALIAS_FILE', 'container-alias.json');
define('EXTERNAL_ICON_ALIAS_FILE', APP_DATA_PATH . 'container-alias.json');
define('STATS_FILE', APP_DATA_PATH . 'stats.json');
define('DEPENDENCY_FILE', APP_DATA_PATH . 'dependencies.json');
define('SSE_FILE', APP_DATA_PATH . 'sse.json');
define('METRICS_FILE', APP_DATA_PATH . 'metrics.json');
define('MIGRATION_FILE', APP_DATA_PATH . 'migration-in-progress.txt');
define('IS_MIGRATION_RUNNING', (file_exists(MIGRATION_FILE) ? true : false));

//-- LOG FILES
define('SYSTEM_LOG', LOGS_PATH . 'system/app.log');
define('UI_LOG', LOGS_PATH . 'system/ui.log');
define('API_LOG', LOGS_PATH . 'system/api.log');
define('MAINTENANCE_LOG', LOGS_PATH . 'system/maintenance.log');
define('STARTUP_LOG', LOGS_PATH . 'system/startup.log');
define('MIGRATION_LOG', LOGS_PATH . 'system/migrations.log');
define('WEBSOCKET_LOG', LOGS_PATH . 'system/websocket.log');
define('CRON_HOUSEKEEPER_LOG', LOGS_PATH . 'crons/housekeeper.log');
define('CRON_PRUNE_LOG', LOGS_PATH . 'crons/prune.log');
define('CRON_PULLS_LOG', LOGS_PATH . 'crons/pulls.log');
define('CRON_STATE_LOG', LOGS_PATH . 'crons/state.log');
define('CRON_STATS_LOG', LOGS_PATH . 'crons/stats.log');
define('CRON_HEALTH_LOG', LOGS_PATH . 'crons/health.log');
define('CRON_SSE_LOG', LOGS_PATH . 'crons/sse.log');
define('LOG_ROTATE_SIZE', 2); //-- MB UNTIL ROTATE

//-- MEMCACHE
define('MEMCACHE_PREFIX', 'dockwatch-' . substr(md5($_SERVER['SERVER_NAME']), 0, 10) . '-');
define('MEMCACHE_DOCKER_STATS', 10);
define('MEMCACHE_DOCKER_PROCESS', 10);
define('MEMCACHE_DOCKER_INSPECT', 10);

//-- REGCTL
define('REGCTL_PATH', '/usr/local/bin/');
define('REGCTL_BINARY', 'regctl');

//-- AVAILABLE PAGES
$pages      = ['overview', 'containers', 'compose', 'orphans', 'notification', 'settings', 'tasks', 'commands', 'logs'];

//-- WHAT DATA TO GET WHEN VIEWING A PAGE
$getStats   = ['overview', 'containers'];
$getProc    = ['overview', 'containers', 'notifications'];
$getInspect = ['overview', 'containers'];

//-- SKIP UPDATING CONTAINERS THAT CAN BREAK THINGS
define('SKIP_OFF', 0);
define('SKIP_FORCE', 1);
define('SKIP_OPTIONAL', 2);

$skipContainerActions   = [
                            'dockwatch',    //-- IF THIS GOES DOWN, IT WILL STOP THE CONTAINER WHICH MEANS IT CAN NEVER FINISH
                            'cloudflared',  //-- IF THIS GOES DOWN, IT WILL KILL THE NETWORK TRAFFIC TO DOCKWATCH
                            'swag'          //-- IF THIS GOES DOWN, IT WILL KILL THE WEB SERVICE TO DOCKWATCH
                        ];
