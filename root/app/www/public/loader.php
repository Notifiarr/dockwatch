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
$autoloads = ['includes', 'functions', 'classes'];

foreach ($autoloads as $autoload) {
    $dir = ABSOLUTE_PATH . $autoload;
    if (is_dir($dir)) {
        $handle = opendir($dir);
        while ($file = readdir($handle)) {
            if ($file[0] != '.' && !is_dir($dir . '/' . $file)) {
                require $dir . '/' . $file;
            }
        }
        closedir($handle);
    }
}

//-- INITIALIZE THE NOTIFY CLASS
$notifications = new Notifications();

//-- SETTINGS
$settings = getFile(SETTINGS_FILE);

//-- STATE
$state = getFile(STATE_FILE);

//-- PULLS
$pulls = getFile(PULL_FILE);
