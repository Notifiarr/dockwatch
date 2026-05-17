<?php

/*
----------------------------------
 ------  Created: 111623   ------
 ------  Austin Best	   ------
----------------------------------
*/

// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_PARSE);

/*
    To adjust the UI based on ACCESS_MODE:
        class=access-rw     : removed when ACCESS_MODE is set to 2
        class=access-rwx    : removed when ACCESS_MODE is set to 1 or 2
        class=access-ro     : only shows element when ACCESS_MODE is set to 2
*/
define('ACCESS_MODE', 0);

//-- COMPOSER AUTOLOADER
require __DIR__ . '/../vendor/autoload.php';

//-- COMPOSER: CODE HIGHLIGHTER
use Phiki\Phiki;

//-- NOT STARTUP
if (!defined('IS_STARTUP')) {
    define('IS_STARTUP', false);
}

if ($_SERVER['TZ']) {
    date_default_timezone_set($_SERVER['TZ']);
}

//-- USE THIS VARIABLE TO BYPASS THINGS NOT NEEDED FOR THE SSE EVENT, ~250ms to ~80MS
define('IS_SSE', (str_contains($_SERVER['PHP_SELF'], 'sse') && !str_contains($_SERVER['PHP_SELF'], 'cron') ? true : false));

//-- ADJUST THIS HERE, SOCKET PROXY USER
if ($_SERVER['DOCKER_HOST']) {
    $_SERVER['DOCKER_HOST'] = str_contains($_SERVER['DOCKER_HOST'], '://') ? $_SERVER['DOCKER_HOST'] : 'http://' . $_SERVER['DOCKER_HOST'];
    $_SERVER['DOCKER_HOST'] = str_replace('tcp://', 'http://', $_SERVER['DOCKER_HOST']); //-- libcurl
}

if (!defined('ABSOLUTE_PATH')) {
    if (file_exists('loader.php')) {
        define('ABSOLUTE_PATH', './');
    }
    if (file_exists('../loader.php')) {
        define('ABSOLUTE_PATH', '../');
    }
    if (file_exists('../../loader.php')) {
        define('ABSOLUTE_PATH', '../../');
    }
}

if (!defined('IS_MAINTENANCE')) {
    define('IS_MAINTENANCE', getenv('DOCKWATCH_MAINTENANCE') == '1');
}

$dockwatchScriptPath = $_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['PHP_SELF'] ?? '';
if ($dockwatchScriptPath == '' && !empty($_SERVER['argv'][0])) {
    $arg0 = $_SERVER['argv'][0];
    if (str_starts_with($arg0, '/')) {
        $dockwatchScriptPath = $arg0;
    } else {
        $base                = isset($_SERVER['PWD']) ? rtrim($_SERVER['PWD'], '/') . '/' : getcwd() . '/';
        $joined              = $base . $arg0;
        $resolved            = realpath($joined);
        $dockwatchScriptPath = $resolved != false ? $resolved : $joined;
    }
}

$dockwatchCronParent = $dockwatchScriptPath != '' ? basename(dirname($dockwatchScriptPath)) : '';
if (IS_MAINTENANCE && (str_contains($dockwatchScriptPath, '/crons/') || str_contains($dockwatchScriptPath, '\\crons\\') || strcasecmp($dockwatchCronParent, 'crons') == 0)) {
    exit(0);
}

$loadTimes = [];
$start     = microtime(true);

//-- DIRECTORIES TO LOAD FILES FROM, ORDER IS IMPORTANT
$autoloadDirs    = ['includes', 'functions', 'functions/helpers', 'classes'];
$autoloadFiles   = ['classes/interfaces/Core.php', 'classes/interfaces/UI.php'];
$ignoreAutoloads = ['header.php', 'footer.php'];

foreach ($autoloadDirs as $autoloadDir) {
    $dir = ABSOLUTE_PATH . $autoloadDir;

    if (is_dir($dir)) {
        $handle = opendir($dir);
        while ($file = readdir($handle)) {
            if ($file[0] != '.' && !is_dir($dir . '/' . $file) && !in_array($file, $ignoreAutoloads)) {
                require $dir . '/' . $file;
            }
        }
        closedir($handle);
    }
}

foreach ($autoloadFiles as $autoloadFile) {
    require $autoloadFile;
}

//-- SET URL BASE FROM FILE IF IT EXISTS AND IS NOT EMPTY
if (file_exists(BASE_URL_FILE)) {
    $baseUrl = trim(file_get_contents(BASE_URL_FILE));
    if (!empty($baseUrl)) {
        $_SERVER['BASE_URL'] = $baseUrl;
    }
}

switch (ACCESS_MODE) {
    case AccessMode::R:
        $accessModeClass = 'text-danger';
        $accessModeHover = 'Read only';
        break;
    case AccessMode::RW:
        $accessModeClass = 'text-warning';
        $accessModeHover = 'Limited access';
        break;
    case AccessMode::RWX:
        $accessModeClass = 'text-success';
        $accessModeHover = 'Full access';
        break;
}

$loadTimes[] = trackTime('page ->', $start);

if (!$_SESSION) {
    session_start();
}

if (!IS_SSE) {
    automation();

    if (IS_MAINTENANCE) {
        logger(SYSTEM_LOG, 'Init: maintenance container, database skipped', 'shell');

        define('REMOTE_SERVER_TIMEOUT', DEFAULT_REMOTE_SERVER_TIMEOUT);

        $settingsTable = [
            'maintenancePort'     => (string) APP_MAINTENANCE_PORT,
            'maintenanceIP'       => '',
            'loginFailures'       => 10,
            'remoteServerTimeout' => (string) DEFAULT_REMOTE_SERVER_TIMEOUT,
        ];
        $serversTable  = [
            APP_SERVER_ID => [
                'id'     => APP_SERVER_ID,
                'name'   => 'local',
                'url'    => APP_SERVER_URL,
                'apikey' => getenv('DOCKWATCH_APIKEY') ?: '',
            ],
        ];

        define('USER_THEME', 'nzblack');
        define('USER_THEME_MODE', 'dark');
        $themes = [];

        $_SESSION['activeServerId']     = APP_SERVER_ID;
        $_SESSION['activeServerName']   = $serversTable[APP_SERVER_ID]['name'];
        $_SESSION['activeServerUrl']    = rtrim($serversTable[APP_SERVER_ID]['url'], '/');
        $_SESSION['activeServerApikey'] = $serversTable[APP_SERVER_ID]['apikey'];
        define('ACTIVE_SERVER_NAME', $serversTable[APP_SERVER_ID]['name']);

        $database = new Database();
    } else {
        logger(SYSTEM_LOG, 'Init class: Memcache()');
        /** @disregard */
        $memcache = new Memcached();
        $memcache->addServer(MEMCACHE_HOST, MEMCACHE_PORT);

        logger(SYSTEM_LOG, 'Init class: Database()');
        $database = new Database();

        $settingsTable = apiRequestLocal('database/settings');
        $serversTable  = apiRequestLocal('database/servers');

        define('REMOTE_SERVER_TIMEOUT', $settingsTable['remoteServerTimeout'] ?: DEFAULT_REMOTE_SERVER_TIMEOUT);

        define('USER_THEME', $settingsTable['defaultTheme'] && file_exists('themes/' . $settingsTable['defaultTheme'] . '.min.css') ? $settingsTable['defaultTheme'] : 'nzblack');
        define('USER_THEME_MODE', $settingsTable['defaultThemeMode'] ?: 'dark');
        $themes = getThemes();

        if (!$_SESSION['activeServerId'] || str_contains($_SERVER['PHP_SELF'], '/api/')) {
            apiSetActiveServer(APP_SERVER_ID, $serversTable);
            define('ACTIVE_SERVER_NAME', $serversTable[APP_SERVER_ID]['name']);
        } else {
            $activeServer = apiGetActiveServer();
            define('ACTIVE_SERVER_NAME', $serversTable[$activeServer['id']]['name']);
        }
    }

    logger(SYSTEM_LOG, 'Init class: Shell()');
    $shell = new Shell();

    logger(SYSTEM_LOG, 'Init class: Docker()');
    $docker = new Docker();

    $isDockerApiAvailable = $docker->apiIsAvailable();

    if (!IS_MAINTENANCE) {
        $notifications = new Notifications();
        logger(SYSTEM_LOG, 'Init class: Notifications()');

        /** @disregard */
        $phiki = new Phiki();
        logger(SYSTEM_LOG, 'Init class: Phiki()');

        $security = new Security();
        logger(SYSTEM_LOG, 'Init class: Security()');

        if (!str_contains_any($_SERVER['PHP_SELF'], ['/api/']) && !str_contains($_SERVER['PWD'], 'oneshot')) {
            $stateFile = apiRequestLocal('file/state');
            $pullsFile = apiRequestLocal('file/pull');
        }
    }
}

if (!str_contains_any($_SERVER['PHP_SELF'], ['/api/']) && !str_contains($_SERVER['PWD'], 'oneshot')) {
    //-- LOGIN, DEFINE AFTER LOADING SETTINGS
    define('LOGIN_FAILURE_LIMIT', $settingsTable['loginFailures'] ?: 10);
    define('LOGIN_FAILURE_TIMEOUT', $settingsTable['loginFailures'] ?: 10); //-- MINUTES TO DISABLE LOGINS

    if (file_exists(LOGIN_FILE) && !str_contains($_SERVER['PHP_SELF'], '/crons/')) {
        define('USE_AUTH', true);
        $loginsFile = file(LOGIN_FILE);

        foreach ($loginsFile as $login) {
            if ($login == 'admin:password') {
                exit('Default admin:password is in the login file, change it & refresh');
            }
        }
    } else {
        define('USE_AUTH', false);
        $_SESSION['authenticated'] = true;
    }

    if (!$_SESSION['authenticated']) {
        if (!str_contains($_SERVER['PHP_SELF'], 'login.php')) {
            header('Location: login.php');
        }
    } else {
        logger(SYSTEM_LOG, 'Starting');
    }
}

if (!IS_SSE && !IS_MAINTENANCE) {
    $baseFile = basename($_SERVER['PHP_SELF']);

    //-- FLIP TO REMOTE MANAGEMENT IF NEEDED
    $activeServer = $activeServer ?: apiGetActiveServer();

    if ($activeServer['id'] != APP_SERVER_ID) {
        $settingsTable = apiRequest('database/settings')['result'];
        $serversTable  = apiRequest('database/servers')['result'];
        $stateFile     = apiRequest('file/state')['result'];
        $pullsFile     = apiRequest('file/pull')['result'];
    }

    //-- SPECIFIC PATHS THAT NEED TO HAVE AN UPDATED PROCESS LIST
    $apiPaths      = ['stats/overview', 'stats/containers', 'stats/metrics'];
    $internalPaths = ['housekeeper.php'];

    $fetchProc    = in_array($_POST['page'], $getProc) || $_POST['hash'] || in_array($_GET['endpoint'], $apiPaths) || in_array($baseFile, $internalPaths);
    $fetchStats   = in_array($_POST['page'], $getStats) || $_POST['hash'] || in_array($_GET['endpoint'], $apiPaths) || in_array($baseFile, $internalPaths);
    $fetchInspect = in_array($_POST['page'], $getInspect) || $_POST['hash'] || in_array($_GET['endpoint'], $apiPaths) || in_array($baseFile, $internalPaths);

    $loadTimes[]            = trackTime('getExpandedProcessList ->');
    $getExpandedProcessList = getExpandedProcessList($fetchProc, $fetchStats, $fetchInspect);
    $processList            = $getExpandedProcessList['processList'] ?? [];

    foreach ($getExpandedProcessList['loadTimes'] as $loadTime) {
        $loadTimes[] = $loadTime;
    }
    $loadTimes[] = trackTime('getExpandedProcessList <-');

    //-- UPDATE THE STATE FILE WHEN EVERYTHING IS FETCHED
    if (str_equals_any($_POST['page'], ['overview', 'containers']) || in_array($_GET['endpoint'], $apiPaths) || in_array($baseFile, $internalPaths)) {
        if ($processList) {
            apiRequest('file/state', [], ['contents' => $processList]);
        }
    }

    //-- STATE
    $stateFile = $processList;
}
