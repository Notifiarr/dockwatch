<?php

/*
----------------------------------
 ------  Created: 111623   ------
 ------  Austin Best	   ------
----------------------------------
*/

// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_PARSE);

//-- ADJUST THIS HERE
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

$loadTimes = [];
$start = microtime(true);

//-- DIRECTORIES TO LOAD FILES FROM, ORDER IS IMPORTANT
$autoloads          = ['includes', 'functions', 'classes'];
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

//-- INIT THE CLASS
$docker = new Docker();

if (!$_SESSION) {
    session_start();
}

automation();

$dockerCommunicateAPI = $docker->apiIsAvailable();

//-- SERVERS
$serversFile = getFile(SERVERS_FILE);
$_SESSION['serverIndex'] = is_numeric($_SESSION['serverIndex']) ? $_SESSION['serverIndex'] : 0;

define('ACTIVE_SERVER_NAME', $serversFile[$_SESSION['serverIndex']]['name']);
define('ACTIVE_SERVER_URL', rtrim($serversFile[$_SESSION['serverIndex']]['url'], '/'));
define('ACTIVE_SERVER_APIKEY', $serversFile[$_SESSION['serverIndex']]['apikey']);

if (!str_contains_any($_SERVER['PHP_SELF'], ['/api/', 'socket']) && !str_contains($_SERVER['PWD'], 'oneshot')) {
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

    //-- SETTINGS
    $settingsFile = getServerFile('settings');
    if ($settingsFile['code'] != 200) {
        $apiError = $settingsFile['file'];
    }
    $settingsFile = $settingsFile['file'];

    //-- LOGIN, DEFINE AFTER LOADING SETTINGS
    define('LOGIN_FAILURE_LIMIT', ($settingsFile['global']['loginFailures'] ? $settingsFile['global']['loginFailures']: 10));
    define('LOGIN_FAILURE_TIMEOUT', ($settingsFile['global']['loginFailures'] ? $settingsFile['global']['loginTimeout']: 10)); //-- MINUTES TO DISABLE LOGINS

    //-- STATE
    $stateFile = getServerFile('state');
    if ($stateFile['code'] != 200) {
        $apiError = $stateFile['file'];
    }
    $stateFile = $stateFile['file'];

    //-- PULLS
    $pullsFile = getServerFile('pull');
    if ($pullsFile['code'] != 200) {
        $apiError = $pullsFile['file'];
    }
    $pullsFile = $pullsFile['file'];

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

//-- SOCKET
$socketHost = $settingsFile['global']['socketHost'] ? $settingsFile['global']['socketHost'] : SOCKET_HOST;
$socketPort = $settingsFile['global']['socketPort'] ? $settingsFile['global']['socketPort'] : SOCKET_PORT;

$fetchProc      = in_array($_POST['page'], $getProc) || $_POST['hash'];
$fetchStats     = in_array($_POST['page'], $getStats) || $_POST['hash'];
$fetchInspect   = in_array($_POST['page'], $getInspect) || $_POST['hash'];

$loadTimes[] = trackTime('getExpandedProcessList ->');
$getExpandedProcessList = getExpandedProcessList($fetchProc, $fetchStats, $fetchInspect);
$processList            = $getExpandedProcessList['processList'];
foreach ($getExpandedProcessList['loadTimes'] as $loadTime) {
    $loadTimes[] = $loadTime;
}
$loadTimes[] = trackTime('getExpandedProcessList <-');

//-- UPDATE THE STATE FILE WHEN EVERYTHING IS FETCHED
if ($_POST['page'] == 'overview' || $_POST['page'] == 'containers') {
    setServerFile('state', json_encode($processList));
}

//-- STATE
$stateFile = $processList;