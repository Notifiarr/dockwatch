<?php

/*
----------------------------------
 ------  Created: 112823   ------
 ------  Austin Best	   ------
----------------------------------
*/

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

function getServerFile($file) 
{
    $apiResponse    = apiRequest($file);
    $code           = $apiResponse['code'];
    $file           = $apiResponse['response'][$file];

    return ['code' => $code, 'file' => $file];
}

function setServerFile($file, $contents) 
{
    $apiResponse = apiRequest($file, [], ['contents' => $contents]);

    return $apiResponse;
}

function getFile($file) 
{
    logger(SYSTEM_LOG, 'getFile() ' . $file);

    if (!file_exists($file)) {
        file_put_contents($file, '[]');
    }

    return json_decode(file_get_contents($file), true);
}

function setFile($file, $contents)
{
    logger(SYSTEM_LOG, 'setFile() ' . $file);

    $contents = is_array($contents) ? json_encode($contents) : $contents;
    file_put_contents($file, $contents);
}