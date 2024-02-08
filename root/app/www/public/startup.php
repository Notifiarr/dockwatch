<?php

if (!defined('ABSOLUTE_PATH')) {
	define('ABSOLUTE_PATH', __DIR__ . '/');
}

echo 'require_once ' . ABSOLUTE_PATH . 'loader.php' . "\n";
require_once ABSOLUTE_PATH . 'loader.php';

echo 'Container init (Start/Restart)' . "\n";
logger(SYSTEM_LOG, 'Container init (Start/Restart)');
