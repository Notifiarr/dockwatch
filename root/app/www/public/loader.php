<?php

/*
----------------------------------
 ------  Created: 111623   ------
 ------  Austin Best	   ------
----------------------------------
*/

// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_PARSE);

if (!defined('ABSOLUTE_PATH')) {
    define('ABSOLUTE_PATH', './');
}

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

if (!$_SESSION) {
    session_start();
}

if (file_exists(LOGIN_FILE)) {
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
    if (strpos($_SERVER['PHP_SELF'], 'login.php') === false) {
        header('Location: login.php');
    }
} else {
    //-- CREATE DIRECTORIES
    createDirectoryTree(LOGS_PATH . 'crons');
    createDirectoryTree(LOGS_PATH . 'notifications');

    //-- INITIALIZE THE NOTIFY CLASS
    $notifications = new Notifications();

    //-- SETTINGS
    $settings = getFile(SETTINGS_FILE);

    //-- STATE
    $state = getFile(STATE_FILE);

    //-- PULLS
    $pulls = getFile(PULL_FILE);

    //-- INITIALIZE MEMCACHE
    if ($settings['global']['memcachedServer'] && $settings['global']['memcachedPort']) {
        $memcache = new Memcached();
        $memcache->addServer($settings['global']['memcachedServer'], $settings['global']['memcachedPort']);
    }
}
