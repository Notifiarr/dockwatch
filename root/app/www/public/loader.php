<?php

/*
----------------------------------
 ------  Created: 111623   ------
 ------  Austin Best	   ------
----------------------------------
*/

// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_PARSE);

define('ACCESS_MODE', 0);

//-- COMPOSER AUTOLOADER
require __DIR__ . '/../vendor/autoload.php';

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

$loadTimes  = [];
$start      = microtime(true);

//-- DIRECTORIES TO LOAD FILES FROM, ORDER IS IMPORTANT
$autoloadDirs       = ['includes', 'functions', 'functions/helpers', 'classes'];
$autoloadFiles      = ['classes/interfaces/Core.php', 'classes/interfaces/UI.php'];
$ignoreAutoloads    = ['header.php', 'footer.php'];

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

define('REMOTE_SERVER_TIMEOUT', $settingsTable['remoteServerTimeout'] ?: DEFAULT_REMOTE_SERVER_TIMEOUT);

if (!IS_SSE) {
    //-- RUN REQUIRED CHECKS
    automation();

    //-- INITIALIZE MEMCACHE
    logger(SYSTEM_LOG, 'Init class: Memcache()');
    $memcache = new Memcached();
    $memcache->addServer(MEMCACHE_HOST, MEMCACHE_PORT);

    //-- INITIALIZE THE DATABASE CLASS
    logger(SYSTEM_LOG, 'Init class: Database()');
    $database = new Database();

    if (!$skipMmigrations) {
        apiRequestLocal('database/migrations');
    }

    $settingsTable  = apiRequestLocal('database/settings');
    $serversTable   = apiRequestLocal('database/servers');

    define('USER_THEME', $settingsTable['defaultTheme'] && file_exists('themes/' . $settingsTable['defaultTheme'] . '.min.css') ? $settingsTable['defaultTheme'] : 'nzblack');
    define('USER_THEME_MODE', $settingsTable['defaultThemeMode'] ?: 'dark');
    $themes = getThemes();

    //-- SET ACTIVE INSTANCE
    if (!$_SESSION['activeServerId'] || str_contains($_SERVER['PHP_SELF'], '/api/')) {
        apiSetActiveServer(APP_SERVER_ID, $serversTable);
        define('ACTIVE_SERVER_NAME', $serversTable[APP_SERVER_ID]['name']);
    } else {
        $activeServer = apiGetActiveServer();
        define('ACTIVE_SERVER_NAME', $serversTable[$activeServer['id']]['name']);
    }

    //-- INITIALIZE THE SHELL CLASS
    logger(SYSTEM_LOG, 'Init class: Shell()');
    $shell = new Shell();

    //-- INITIALIZE THE DOCKER CLASS
    logger(SYSTEM_LOG, 'Init class: Docker()');
    $docker = new Docker();

    //-- CHECK IF DOCKER IS AVAILABLE
    $isDockerApiAvailable = $docker->apiIsAvailable();

    //-- INITIALIZE THE NOTIFY CLASS
    $notifications = new Notifications();
    logger(SYSTEM_LOG, 'Init class: Notifications()');

    if (!str_contains_any($_SERVER['PHP_SELF'], ['/api/']) && !str_contains($_SERVER['PWD'], 'oneshot')) {
        $stateFile  = apiRequestLocal('file/state');
        $pullsFile  = apiRequestLocal('file/pull');
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

if (!IS_SSE) {
    $baseFile = basename($_SERVER['PHP_SELF']);

    //-- FLIP TO REMOTE MANAGEMENT IF NEEDED
    $activeServer = $activeServer ?: apiGetActiveServer();

    if ($activeServer['id'] != APP_SERVER_ID) {
        $settingsTable  = apiRequest('database/settings')['result'];
        $serversTable   = apiRequest('database/servers')['result'];
        $stateFile      = apiRequest('file/state')['result'];
        $pullsFile      = apiRequest('file/pull')['result'];
    }

    //-- SPECIFIC PATHS THAT NEED TO HAVE AN UPDATED PROCESS LIST
    $apiPaths           = ['stats/overview', 'stats/containers', 'stats/metrics'];
    $internalPaths      = ['housekeeper.php'];

    $fetchProc      = in_array($_POST['page'], $getProc) || $_POST['hash'] || in_array($_GET['endpoint'], $apiPaths) || in_array($baseFile, $internalPaths);
    $fetchStats     = in_array($_POST['page'], $getStats) || $_POST['hash'] || in_array($_GET['endpoint'], $apiPaths) || in_array($baseFile, $internalPaths);
    $fetchInspect   = in_array($_POST['page'], $getInspect) || $_POST['hash'] || in_array($_GET['endpoint'], $apiPaths) || in_array($baseFile, $internalPaths);

    $loadTimes[] = trackTime('getExpandedProcessList ->');
    $getExpandedProcessList = getExpandedProcessList($fetchProc, $fetchStats, $fetchInspect);
    $processList            = $getExpandedProcessList['processList'];

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
