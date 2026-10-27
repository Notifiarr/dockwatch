<?php

/*
----------------------------------
 ------  Created: 102626   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

$cron->dispatch();
