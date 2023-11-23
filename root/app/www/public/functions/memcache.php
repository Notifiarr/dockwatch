<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

function memcacheBust($key)
{
    global $memcache;

    if (!$memcache) {
        return;
    }

    $memcache->set($key, null, 0);
}

function memcacheGet($key)
{
    global $memcache;

    if (!$memcache) {
        return;
    }

    return $memcache->get($key);
}

function memcacheSet($key, $data, $seconds)
{
    global $memcache;

    if (!$memcache) {
        return;
    }

    $memcache->set($key, $data, $seconds);
}

function memcacheStats()
{
    global $memcache;

    if (!$memcache) {
        return;
    }

    return $memcache->getStats();
}
