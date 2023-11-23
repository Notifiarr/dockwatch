<?php

/*
----------------------------------
 ------  Created: 111623   ------
 ------  Austin Best	   ------
----------------------------------
*/

function createDirectoryTree($tree)
{
    system('mkdir -p ' . $tree);
}

function findContainerFromHash($hash)
{
    $state = getFile(STATE_FILE);
    foreach ($state as $container) {
        if (md5($container['Names']) == $hash) {
            return $container;
        }
    }
}

function loadJS()
{
    $jsDir  = 'js/';
    $dir    = opendir($jsDir);
    while ($file = readdir($dir)) {
        if (strpos($file, '.js') !== false) {
            echo '<script src="'. $jsDir . $file .'?t='. filemtime($jsDir . $file) .'"></script>';
        }
    }
    closedir($dir);
}

function getFile($file) 
{
    global $systemLog;

    logger($systemLog, 'getFile() ' . $file, 'info');

    if (!file_exists($file)) {
        file_put_contents($file, '[]');
    }
    return json_decode(file_get_contents($file), true);
}

function setFile($file, $contents)
{
    global $systemLog;

    logger($systemLog, 'setFile() ' . $file, 'info');

    $contents = is_array($contents) ? json_encode($contents) : $contents;
    file_put_contents($file, $contents);
}

function bytesFromString($string)
{
    $units      = ['b', 'kb', 'mb', 'gb', 'tb'];
    $stringUnit = strtolower(substr($string, -2));
    $number     = floatval(trim(str_replace($stringUnit, '', $string)));

    if (is_numeric(substr($stringUnit, 0, 1))) {
        return preg_replace('/[^\d]/', '', $string);
    }

    $exponent = array_flip($units)[$stringUnit] ?? null;

    if ($exponent === null) {
        return 0;
    }

    return $number * (1000 ** $exponent);
}

function byteConversion($bytes, $measurement = false, $dec = 2)
{
    if (!$bytes || $bytes <= 0) {
        return 0;
    }

    //-- SEND LARGEST ONE
    if (!$measurement) {
        $units  = array('B', 'KiB', 'MiB', 'GiB', 'TiB');
        $bytes  = max($bytes, 0);
        $pow    = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow    = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $dec) . ' ' . $units[$pow];
    }

    switch ($measurement) {
        case 'KiB':
            return round($bytes / 1024, $dec);
        case 'MiB':
            return round($bytes / pow(1024, 2), $dec);
        case 'GiB':
            return round($bytes / pow(1024, 3), $dec);
        case 'TiB':
            return round($bytes / pow(1024, 4), $dec);
    }
}

function truncateEnd($str, $max, $minLength = false)
{
    if (!is_string($str)) {
        return $str;
    }

    if (!$minLength && strlen($str) <= $max) {
        return $str;
    }

    if (strlen($str) > $max) {
        $str = substr($str, 0, $max - 3) . '...';
    }

    if ($minLength && strlen($str) < $max) {
        $str = str_pad($str, $max, ' ', STR_PAD_RIGHT);
    }

    return $str;
}

function truncateMiddle($str, $max)
{
    if (strlen($str) <= $max) {
        return $str;
    }

    $padMax = $max - 5;

    return substr($str, 0, floor($padMax / 2)) . '.....' . substr($str, (floor($padMax / 2) * -1));
}

function daysBetweenDates($ymdStart, $ymdEnd)
{
    $start  = new DateTime($ymdStart . ' 00:00:01');
    $end    = new DateTime($ymdEnd . '23:59:59');
    $diff   = $end->diff($start)->format('%a');

    return $diff;
}

function calculateDaysFromString($str)
{
    if (strpos($str, 'd') !== false) { //-- [1-29]d
        return intval(str_replace('d', '', $str));
    } elseif (strpos($str, 'w') !== false) { //-- [1-3]w
        return intval(str_replace('w', '', $str)) * 7;
    } elseif (strpos($str, 'm') !== false) { //-- [1-12]m
        return intval(str_replace('m', '', $str)) * 30;
    }
}