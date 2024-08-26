<?php

/*
----------------------------------
 ------  Created: 111623   ------
 ------  Austin Best	   ------
----------------------------------
*/

// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_PARSE);

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

$apiError   = '';
$loadTimes  = [];
$start      = microtime(true);

//-- DIRECTORIES TO LOAD FILES FROM, ORDER IS IMPORTANT
$autoloads          = ['includes', 'functions', 'functions/helpers', 'classes'];
$ignoreAutoloads    = ['header.php', 'footer.php'];

foreach ($autoloads as $autoload) {
    $dir = ABSOLUTE_PATH . $autoload;

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

$loadTimes[] = trackTime('page ->', $start);

if (!$_SESSION) {
    session_start();
}

if (!IS_SSE) {
    //-- INITIALIZE THE DOCKER CLASS
    $docker = new Docker();

    //-- RUN REQUIRED CHECKS
    automation();

    //-- CHECK IF DOCKER IS AVAILABLE
    $dockerCommunicateAPI = $docker->apiIsAvailable();

    //-- INITIALIZE THE SHELL CLASS
    $shell = new Shell();
}

//-- GET THE SERVERS LIST
$serversFile = getFile(SERVERS_FILE);
$_SESSION['serverIndex'] = is_numeric($_SESSION['serverIndex']) ? $_SESSION['serverIndex'] : 0;

define('ACTIVE_SERVER_NAME', $serversFile[$_SESSION['serverIndex']]['name']);
define('ACTIVE_SERVER_URL', rtrim($serversFile[$_SESSION['serverIndex']]['url'], '/'));
define('ACTIVE_SERVER_APIKEY', $serversFile[$_SESSION['serverIndex']]['apikey']);

if (!str_contains_any($_SERVER['PHP_SELF'], ['/api/']) && !str_contains($_SERVER['PWD'], 'oneshot')) {
    if (!IS_SSE) {
        //-- CHECK IF SELECTED SERVER CAN BE TALKED TO
        $ping = apiRequest('ping');
        if (!is_array($ping) || $ping['code'] != 200) {
            if ($_SESSION['serverIndex'] == 0) {
                exit('The connection to this container in the servers file is broken');
            } else {
                $_SESSION['serverIndex'] = 0;
                header('Location: /');
                exit();
            }
        }
    }

    //-- SETTINGS
    $settingsFile   = getServerFile('settings');
    $apiError       = $settingsFile['code'] != 200 ? $settingsFile['file'] : $apiError;
    $settingsFile   = $settingsFile['file'];

    //-- LOGIN, DEFINE AFTER LOADING SETTINGS
    define('LOGIN_FAILURE_LIMIT', ($settingsFile['global']['loginFailures'] ? $settingsFile['global']['loginFailures']: 10));
    define('LOGIN_FAILURE_TIMEOUT', ($settingsFile['global']['loginFailures'] ? $settingsFile['global']['loginTimeout']: 10)); //-- MINUTES TO DISABLE LOGINS

    if (!IS_SSE) {
        //-- STATE
        $stateFile      = getServerFile('state');
        $apiError       = $stateFile['code'] != 200 ? $stateFile['file'] : $apiError;
        $stateFile      = $stateFile['file'];

        //-- PULLS
        $pullsFile      = getServerFile('pull');
        $apiError       = $pullsFile['code'] != 200 ? $pullsFile['file'] : $apiError;
        $pullsFile      = $pullsFile['file'];
    }

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

        //-- INITIALIZE THE NOTIFY CLASS
        $notifications = new Notifications();
        logger(SYSTEM_LOG, 'Init class: Notifications()');
    }
}

if (!IS_SSE) {
    $fetchProc      = in_array($_POST['page'], $getProc) || $_POST['hash'] || $_GET['request'] == 'dwStats';
    $fetchStats     = in_array($_POST['page'], $getStats) || $_POST['hash'] || $_GET['request'] == 'dwStats';
    $fetchInspect   = in_array($_POST['page'], $getInspect) || $_POST['hash'] || $_GET['request'] == 'dwStats';

    $loadTimes[] = trackTime('getExpandedProcessList ->');
    $getExpandedProcessList = getExpandedProcessList($fetchProc, $fetchStats, $fetchInspect);
    $processList            = $getExpandedProcessList['processList'];
    foreach ($getExpandedProcessList['loadTimes'] as $loadTime) {
        $loadTimes[] = $loadTime;
    }
    $loadTimes[] = trackTime('getExpandedProcessList <-');

    //-- UPDATE THE STATE FILE WHEN EVERYTHING IS FETCHED
    if ($_POST['page'] == 'overview' || $_POST['page'] == 'containers' || $_GET['request'] == 'dwStats') {
        setServerFile('state', json_encode($processList));
    }

    //-- STATE
    $stateFile = $processList;
}
