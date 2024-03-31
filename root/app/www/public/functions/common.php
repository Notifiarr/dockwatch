<?php

/*
----------------------------------
 ------  Created: 111623   ------
 ------  Austin Best	   ------
----------------------------------
*/

function isDockwatchContainer($container)
{
    $imageMatch = str_replace(':main', '', APP_IMAGE);
    if (str_contains($container['inspect'][0]['Config']['Image'], $imageMatch) && $container['Names'] != 'dockwatch-maintenance') {
        return true;
    }

    return false;
}

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
    createDirectoryTree(TMP_PATH);
    createDirectoryTree(COMPOSE_PATH);
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
    if (str_contains($str, 'd')) { //-- [1-29]d
        return intval(str_replace('d', '', $str));
    } elseif (str_contains($str, 'w')) { //-- [1-3]w
        return intval(str_replace('w', '', $str)) * 7;
    } elseif (str_contains($str, 'm')) { //-- [1-12]m
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
        if (str_contains($line, 'processor')) {
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

function trackTime($label, $microtime = 0)
{
    $backtrace  = debug_backtrace();
    $line       = $backtrace[0]['line'];
    $file       = $backtrace[0]['file'];

    return ['label' => $label, 'microtime' => ($microtime ? $microtime : microtime(true)), 'file' => $file, 'line' => $line];
}

function displayTimeTracking($loadTimes = [])
{
    $backtrace  = debug_backtrace();
    $line       = $backtrace[0]['line'];
    $file       = $backtrace[0]['file'];

    $loadTimes[] = ['label' => 'page <-', 'microtime' => microtime(true), 'file' => $file, 'line' => $line];
    $previousTime = 0;
    $runningTime = 0;

    foreach ($loadTimes as $index => $event) {
        if ($index == 0) {
            $display[] = $event['file'] . '::' . $event['line'] . ' | 0 | ' . $event['label'];
        } else {
            $runTime = number_format(($event['microtime'] - $previousTime), 4);
            $runningTime += $runTime;
            $display[] = $event['file'] . '::' . $event['line'] . ' | ' . $runningTime  . ' | ' . $event['label'];
        }

        $previousTime = $event['microtime'];
    }
    ?>
    <div id="loadtime-debug" class="mt-3" style="display: none;">
        <h4>Load time tracking</h4>
        <?= implode('<br>', $display) ?>
    </div>
    <?php
}

//-- TAKE ARRAY INPUT OF PORTS AND GROUP THEM TO A RANGE IF THEY ARE WITHIN A SINGLE DIGIT
function formatPortRanges($ports) 
{
    $ranges = [];
    $rangeStart = $rangeStop = 0;

    foreach ($ports as $port => $container) {
        if ($ports[$port + 1] == $container) { //-- SEE IF THEY ARE THE SAME NAME SEQUENTIALLY
            if ($ports[$port + 1]) { //-- CHECK NEXT SEQUENTIAL PORT
                if (!$rangeStart) {
                    $rangeStart = $port;
                }
                $rangeStop = $port + 1;
            } elseif ($rangeStart) { //-- RANGE EXISTS BUT IS LAST SEQUENTIAL PORT
                $ranges[$rangeStart . '-' . $rangeStop] = $container;
                $rangeStart = $rangeStop = 0;
            } else { //-- NOT A NUMERICALLY SEQUENTIAL PORT
                $ranges[$port] = $container;
            }
        } elseif ($rangeStart) { //-- NOT SEQUENTIAL NAME WISE BUT PREVIOUS WAS
            $ranges[$rangeStart . '-' . $rangeStop] = $container;
            $rangeStart = $rangeStop = 0;
        } else { //-- NOT IN THE SAME CONTAINER GROUP
            $ranges[$port] = $container;
        }
    }

    return $ranges;
}

function str_contains_any(string $haystack, array $needles): bool
{
    return array_reduce($needles, fn($a, $n) => $a || str_contains($haystack, $n), false);
}

function str_contains_all(string $haystack, array $needles): bool
{
    return array_reduce($needles, fn($a, $n) => $a && str_contains($haystack, $n), true);
}

function str_compare($str1, $str2, $case = false): bool
{
    if ($case) {
        return $str1 == $str2;
    } else {
        return strtolower($str1) == strtolower($str2);
    }
}