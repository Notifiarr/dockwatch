<?php

/*
----------------------------------
 ------  Created: 111623   ------
 ------  Austin Best	   ------
----------------------------------
*/

function automation()
{
    if (!file_exists(SERVERS_FILE)) {
        $servers[] = ['name' => 'localhost', 'url' => 'http://localhost', 'apikey' => generateApikey()];
        setFile(SERVERS_FILE, $servers);
    }

    //-- CREATE DIRECTORIES
    createDirectoryTree(LOGS_PATH . 'crons');
    createDirectoryTree(LOGS_PATH . 'notifications');
    createDirectoryTree(LOGS_PATH . 'system');
    createDirectoryTree(LOGS_PATH . 'ui');
    createDirectoryTree(LOGS_PATH . 'api');
    createDirectoryTree(BACKUP_PATH);
}

function createDirectoryTree($tree)
{
    system('mkdir -p ' . $tree);
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
    $start  = new DateTime($ymdStart . ' 12:00:00');
    $end    = new DateTime($ymdEnd . '12:00:00');
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

function cpuTotal()
{
    $cpus       = 0;
    $cmd        = 'cat /proc/cpuinfo';
    $cpuinfo    = shell_exec($cmd . ' 2>&1');
    $lines      = explode("\n", $cpuinfo);

    foreach ($lines as $line) {
        if (strpos($line, 'processor') !== false) {
            $cpus++;
        }
    }

    return $cpus;
}

function linkWebroot($location)
{
    switch ($location) {
        case 'internal':
            if (!is_dir('/app/www/internal')) {
                $cmd = 'mv /app/www/public /app/www/internal && ln -s /app/www/internal /app/www/public';
            } else {
                $cmd = 'rm -f /app/www/public && ln -s /app/www/internal /app/www/public';
            }
            shell_exec($cmd . ' 2>&1');
            break;
        case 'external':
            if (!is_dir('/app/www/internal')) {
                $cmd = 'mv /app/www/public /app/www/internal && ln -s /config/www /app/www/public';
            } else {
                $cmd = 'rm -f /app/www/public && ln -s /config/www /app/www/public';
            }
            shell_exec($cmd . ' 2>&1');
            break;
    }
}

function array_sort_by_key(&$array, $field, $direction = 'asc')
{
    if (!is_array($array)) {
        return $array;
    }

    uasort($array, function ($a, $b) use ($field, $direction) {
        if ($direction == 'asc') {
            return strtolower($a[$field]) <=> strtolower($b[$field]);
        } else {
            return strtolower($b[$field]) <=> strtolower($a[$field]);
        }
    });
}

function generateApikey($length = 32)
{
    return bin2hex(random_bytes($length));
}

function convertDockerTimestamp($input) 
{
    //-- 300000000000 -> 300s -> 5m
    $seconds = $input / 1e9;
    if ($seconds > 60) {
        $minutes = $seconds / 60;
        return $minutes . 'm';
    }

    return $seconds . 's';
}