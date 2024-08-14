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
    $start  = new DateTime($ymdStart . ' 12:00:00');
    $end    = new DateTime($ymdEnd . '12:00:00');
    $diff   = $end->diff($start)->format('%a');

    return $diff;
}
