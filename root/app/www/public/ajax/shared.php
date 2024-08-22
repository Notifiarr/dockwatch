<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

if (file_exists('loader.php')) {
    define('ABSOLUTE_PATH', './');
}
if (file_exists('../loader.php')) {
    define('ABSOLUTE_PATH', '../');
}
if (file_exists('../../loader.php')) {
    define('ABSOLUTE_PATH', '../../');
}
require ABSOLUTE_PATH . 'loader.php';

if (!str_contains_any($_SERVER['PHP_SELF'], ['/api/']) && !str_contains($_SERVER['PWD'], 'oneshot')) {
    if (!$_SESSION['IN_DOCKWATCH']) {
        http_response_code(400);
        exit('Error: You should use the UI, its much prettier.');
    }
}
