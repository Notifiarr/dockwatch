<?php

/*
----------------------------------
 ------  Created: 111623   ------
 ------  Austin Best	   ------
----------------------------------
*/

//-- COMPOSER: IP VALIDATOR
use Chialab\Ip;

function loadClassExtras($class)
{
    $extras = ['interfaces', 'traits'];

    foreach ($extras as $extraDir) {
        if (file_exists(ABSOLUTE_PATH . 'classes/' . $extraDir . '/' . $class . '.php')) {
            require ABSOLUTE_PATH . 'classes/' . $extraDir . '/' . $class . '.php';
        } else {
            $extraFolder = ABSOLUTE_PATH . 'classes/' . $extraDir . '/' . $class . '/';

            if (is_dir($extraFolder)) {
                $openExtraDir = opendir($extraFolder);
                while ($extraFile = readdir($openExtraDir)) {
                    if (str_contains($extraFile, '.php')) {
                        require $extraFolder . $extraFile;
                    }
                }
                closedir($openExtraDir);
            }
        }
    }
}

function isDockwatchContainer($container)
{
    $imageMatch = str_replace(':main', '', APP_IMAGE);
    if (str_contains($container['inspect'][0]['Config']['Image'], $imageMatch) && $container['Names'] != 'dockwatch-maintenance') {
        return true;
    }
    if (str_contains($container['image'], $imageMatch) && $container['name'] != 'dockwatch-maintenance') {
        return true;
    }

    return false;
}

function getDockwatchContainerName()
{
    global $shell;

    if (memcacheGet('dockwatchContainerName')) {
        return ltrim(memcacheGet('dockwatchContainerName'), '/');
    }

    $hostname = trim($shell->exec('grep /etc/hostname /proc/self/mountinfo | awk -F/containers/ \'{print $2}\' | cut -d/ -f1 2>&1')); //-- RETURNS VOLUME PATH THAT MATCHES CONTAINER ID
    $cmd      = sprintf(
        "/usr/bin/docker inspect --format='{{.Name}}' %s 2>&1",
        $shell->prepare($hostname),
    );

    $containerName = trim($shell->exec($cmd . ' 2>&1'));
    memcacheSet('dockwatchContainerName', $containerName, 300);

    return substr($containerName, 1);
}

function getDockwatchContainerHash()
{
    global $shell;

    if (memcacheGet('dockwatchContainerHash')) {
        return memcacheGet('dockwatchContainerHash');
    }

    $hostname = trim($shell->exec('grep /etc/hostname /proc/self/mountinfo | awk -F/containers/ \'{print $2}\' | cut -d/ -f1 2>&1')); //-- RETURNS VOLUME PATH THAT MATCHES CONTAINER ID
    $cmd      = sprintf(
        "/usr/bin/docker inspect --format='{{.Image}}' %s 2>&1",
        $shell->prepare($hostname),
    );

    $containerHash = trim($shell->exec($cmd . ' 2>&1'));
    $containerHash = str_replace('sha256:', '', $containerHash);
    memcacheSet('dockwatchContainerHash', $containerHash, 300);

    return $containerHash;
}

function isComposeContainer($container)
{
    $path = COMPOSE_PATH . preg_replace('/[^ \w]+/', '', $container);
    return file_exists($path . '/docker-compose.yml');
}

function automation()
{
    //-- CREATE DIRECTORIES
    createDirectoryTree(LOGS_PATH . 'crons');
    createDirectoryTree(LOGS_PATH . 'notifications');
    createDirectoryTree(LOGS_PATH . 'system');
    createDirectoryTree(LOGS_PATH . 'ui');
    createDirectoryTree(LOGS_PATH . 'api');
    createDirectoryTree(BACKUP_PATH);
    createDirectoryTree(TMP_PATH);
    createDirectoryTree(COMPOSE_PATH);
    createDirectoryTree(DATABASE_PATH);
    createDirectoryTree(MIGRATIONS_PATH);
}

function createDirectoryTree($tree)
{
    system('mkdir -p ' . $tree);
}

function cpuTotal()
{
    global $shell;

    $cpus    = 0;
    $cmd     = 'cat /proc/cpuinfo';
    $cpuinfo = $shell->exec($cmd . ' 2>&1');
    $lines   = explode("\n", $cpuinfo);

    foreach ($lines as $line) {
        if (str_contains($line, 'processor')) {
            $cpus++;
        }
    }

    return $cpus;
}

function trackTime($label, $microtime = 0)
{
    $backtrace = debug_backtrace();
    $line      = $backtrace[0]['line'];
    $file      = $backtrace[0]['file'];

    return ['label' => $label, 'microtime' => ($microtime ? $microtime : microtime(true)), 'file' => $file, 'line' => $line];
}

function displayTimeTracking($loadTimes = [])
{
    $backtrace = debug_backtrace();
    $line      = $backtrace[0]['line'];
    $file      = $backtrace[0]['file'];

    $loadTimes[]  = ['label' => 'page <-', 'microtime' => microtime(true), 'file' => $file, 'line' => $line];
    $previousTime = 0;
    $runningTime  = 0;

    foreach ($loadTimes as $index => $event) {
        if ($index == 0) {
            $display[] = $event['file'] . '::' . $event['line'] . ' | 0 | ' . $event['label'];
        } else {
            $runTime      = number_format(($event['microtime'] - $previousTime), 4);
            $runningTime += $runTime;
            $display[]    = $event['file'] . '::' . $event['line'] . ' | ' . $runningTime . ' | ' . $event['label'];
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
    $ranges     = [];
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
                $rangeStart                             = $rangeStop = 0;
            } else { //-- NOT A NUMERICALLY SEQUENTIAL PORT
                $ranges[$port] = $container;
            }
        } elseif ($rangeStart) { //-- NOT SEQUENTIAL NAME WISE BUT PREVIOUS WAS
            $ranges[$rangeStart . '-' . $rangeStop] = $container;
            $rangeStart                             = $rangeStop = 0;
        } else { //-- NOT IN THE SAME CONTAINER GROUP
            $ranges[$port] = $container;
        }
    }

    return $ranges;
}

function breakpoint()
{
    $backtrace = debug_backtrace();
    echo '<br>breakpoint -><br>' . $backtrace[0]['file'] . '->' . $backtrace[0]['line'] . '<br>';
    exit('breakpoint <-');
}

function getServers()
{
    $serverPings = apiRequestServerPings();
    $serverList  = ['activeServer' => apiGetActiveServer()];

    foreach ($serverPings as $serverPing) {
        $serverList['servers'][] = [
            'id'       => $serverPing['id'],
            'name'     => $serverPing['name'],
            'url'      => $serverPing['url'],
            'disabled' => $serverPing['code'] != 200 ? ' [HTTP: ' . $serverPing['code'] . ']' : '',
        ];
    }

    return $serverList;
}

function validateServers($servers = [])
{
    if (empty($servers)) {
        return [];
    }

    $availableServers = apiRequestLocal('database/servers');
    $matchingServers  = [];

    foreach ($availableServers as $server) {
        $serverName = strtoupper($server['name']);

        if (in_array($serverName, array_map('strtoupper', $servers))) {
            $matchingServers[] = $server;
        }
    }

    return $matchingServers;
}

function getThemes()
{
    $themesPath = ABSOLUTE_PATH . 'themes/';
    $dir        = opendir($themesPath);
    while ($file = readdir($dir)) {
        if (str_contains($file, '.min.css')) {
            $themes[] = str_replace('.min.css', '', $file);
        }
    }
    closedir($dir);

    return $themes;
}

function extractRegistryName($image)
{
    $imageRegistry = [];
    if (preg_match('/^([a-zA-Z0-9][a-zA-Z0-9-._]*(?:\.[a-zA-Z0-9][a-zA-Z0-9-._]*)+)\//', $image, $imageRegistry)) {
        $imageRegistry = $imageRegistry[1];
    }
    if (empty($imageRegistry)) {
        $imageRegistry = 'docker.io';
    }
    return $imageRegistry;
}

function cleanTTYOutput($input)
{
    $input = preg_replace(
        [
            '/^"(.*)"$/s',             //-- REMOVE WRAPPING DOUBLE QUOTES
            '/^.*?@.*?#\s/',           //-- INITIAL PROMPT
            '/\r?\n.*?@.*?#\s*$/',     //-- FINAL PROMPT
            '/\r?\n(\d+)\r?\n$/',      //-- EXIT CODE
            '/\x1B\[[0-9;]*[a-zA-Z]/', //-- ANSI CODES
            '/\\\\r\\\\n/',            //-- LINE BREAK
            '/\\\\n/',                 //-- LINE BREAK
            '/\\\\r/',                 //-- LINE BREAK
            '/\\\\\\//',               //-- BACKSLASHES
        ],
        [
            '\1',
            '',
            '',
            '',
            '',
            "\n",
            "\n",
            "\n",
            '\\',
        ],
        $input,
    );

    return $input;
}

function appServerUrl()
{
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';

    if ($host == '') {
        return 'http://localhost';
    }

    if (str_contains($host, ':')) {
        $parsed = parse_url('http://' . $host);
        $host   = $parsed['host'] ?? explode(':', $host, 2)[0];
    }

    $scheme = 'http';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        $scheme = 'https';
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $scheme = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : 'http';
    } elseif (!empty($_SERVER['REQUEST_SCHEME'])) {
        $scheme = $_SERVER['REQUEST_SCHEME'];
    }

    return $scheme . '://' . $host;
}

function ipMatchesSubnet($ip, $ipmask)
{
    try {
        $address = Ip\Address::parse($ip);

        if (str_contains($ipmask, '/')) {
            return Ip\Subnet::parse($ipmask)->contains($address);
        }

        return $address->equals(Ip\Address::parse($ipmask));
    } catch (\InvalidArgumentException $e) {
        return false;
    }
}

function buildWebSocketURL($url = [])
{
    if (empty($url)) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'wss' : 'ws';
        $host   = $_SERVER['HTTP_HOST'] ?? '';
        if ($host === '') {
            $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
            $port = $_SERVER['SERVER_PORT'] ?? '';
            $host = $port ? $host . ':' . $port : $host;
        }

        $basePath = rtrim($_SERVER['BASE_URL'] ?? '', '/');
        $url      = $scheme . '://' . $host . $basePath . '/ws';
    }

    return $url;
}

//-- ISSUE A SINGLE-USE WEBSOCKET CHANNEL TOKEN AND RETURN THE CONNECT URL
function createWebSocketSession($keyFormat, $id, $query)
{
    global $memcache, $settingsTable;

    $response = ['url' => '', 'token' => '', 'error' => ''];

    //-- GENERATE A SINGLE-USE TOKEN FOR THIS CHANNEL SESSION
    $token = bin2hex(random_bytes(32));
    $key   = sprintf($keyFormat, $id);

    $tokens = [];
    if ($memcache) {
        $existing = $memcache->get($key);
        $tokens   = is_array($existing) ? $existing : [];
    }

    $tokens[] = $token;
    $tokens   = array_slice(array_values($tokens), -20); //-- CAP TO AVOID UNBOUNDED GROWTH
    $stored   = $memcache ? $memcache->set($key, $tokens, MEMCACHE_WS_TOKEN_TIME) : false;

    $response['url']   = buildWebSocketURL($settingsTable['websocketUrl'] ?? []) . $query;
    $response['token'] = $token;

    if (!$stored) {
        $response['error'] = 'Unable to store token';
    }

    return $response;
}
