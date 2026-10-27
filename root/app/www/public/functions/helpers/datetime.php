<?php

/*
----------------------------------
 ------  Created: 081324   ------
 ------  Austin Best	   ------
----------------------------------
*/

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

function daysBetweenDates($ymdStart, $ymdEnd)
{
    $start = new DateTime($ymdStart . ' 12:00:00');
    $end   = new DateTime($ymdEnd . ' 12:00:00');
    $diff  = $end->diff($start)->format('%a');

    return $diff;
}

function normalizeBackupTime($time)
{
    if (!preg_match('/^(\d{1,2}):(\d{2})/', $time, $match)) {
        return '03:00';
    }

    $hour   = min(23, max(0, $match[1]));
    $minute = round(min(59, max(0, $match[2])) / 10) * 10;
    if ($minute >= 60) {
        $minute = 0;
        $hour   = ($hour + 1) % 24;
    }

    return sprintf('%02d:%02d', $hour, $minute);
}

function backupTimeLabel($time)
{
    $time = normalizeBackupTime($time);
    [$hour, $minute] = explode(':', $time);

    return date('g:i A', mktime($hour, $minute, 0));
}

function backupRuntime($started)
{
    if ($started <= 0) {
        return '0s';
    }

    $seconds = max(0, time() - $started);
    if ($seconds < 60) {
        return $seconds . 's';
    }

    $minutes = intdiv($seconds, 60);
    $remain  = $seconds % 60;
    if ($minutes < 60) {
        return $minutes . 'm ' . $remain . 's';
    }

    $hours   = intdiv($minutes, 60);
    $minutes = $minutes % 60;

    return $hours . 'h ' . $minutes . 'm';
}
