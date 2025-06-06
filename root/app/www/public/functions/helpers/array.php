<?php

/*
----------------------------------
 ------  Created: 081324   ------
 ------  Austin Best	   ------
----------------------------------
*/

function makeArray($array)
{
    return is_array($array) ? $array : [];
}

function array_equals_any($haystack, $needles)
{
    if (!is_array($haystack) || !is_array($needles)) {
        return;
    }

    foreach ($needles as $needle) {
        if (in_array($needle, $haystack)) {
            return $needle;
        }
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

function array_contains_any($haystack, $needles)
{
    foreach ($needles as $needle) {
        if (in_array($haystack, $needle)) {
            return true;
        }
    }

    return false;
}
