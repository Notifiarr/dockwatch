<?php

/*
----------------------------------
 ------  Created: 081324   ------
 ------  Austin Best	   ------
----------------------------------
*/

function stri_contains(string|null $haystack, string $needle)
{
    return str_contains(strtolower($haystack), strtolower($needle));
}

function str_equals_any(string|null $haystack, array $needles): bool
{
    if (!$haystack) {
        return false;
    }

    return in_array($haystack, $needles);
}

function str_contains_any(string|null $haystack, array $needles): bool
{
    if (!$haystack) {
        return false;
    }

    return array_reduce($needles, fn($a, $n) => $a || str_contains($haystack, $n), false);
}

function str_contains_all(string|null $haystack, array $needles): bool
{
    if (!$haystack) {
        return false;
    }

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
