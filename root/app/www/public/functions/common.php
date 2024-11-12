<?php

/*
----------------------------------
 ------  Created: 111623   ------
 ------  Austin Best	   ------
----------------------------------
*/

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

    return false;
}

function isComposeContainer($container)
{
    return file_exists(COMPOSE_PATH . $container . '/docker-compose.yml');
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

    $cpus       = 0;
    $cmd        = 'cat /proc/cpuinfo';
    $cpuinfo    = $shell->exec($cmd . ' 2>&1');
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
    global $shell;

    switch ($location) {
        case 'internal':
            if (!is_dir('/app/www/internal')) {
                $cmd = 'mv /app/www/public /app/www/internal && ln -s /app/www/internal /app/www/public';
            } else {
                $cmd = 'rm -f /app/www/public && ln -s /app/www/internal /app/www/public';
            }
            $shell->exec($cmd . ' 2>&1');
            break;
        case 'external':
            if (!is_dir('/app/www/internal')) {
                $cmd = 'mv /app/www/public /app/www/internal && ln -s /config/www /app/www/public';
            } else {
                $cmd = 'rm -f /app/www/public && ln -s /config/www /app/www/public';
            }
            $shell->exec($cmd . ' 2>&1');
            break;
    }
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

function breakpoint()
{
    $backtrace = debug_backtrace();
    echo '<br>breakpoint -><br>' . $backtrace[0]['file'] . '->' . $backtrace[0]['line'] . '<br>';
    exit('breakpoint <-');
}

function getRemoteServerSelect()
{
    $activeServer   = apiGetActiveServer();
    $serverPings    = apiRequestServerPings();
    $links          = '';
    $serverList     = '<select class="form-select w-75 d-inline-block" id="activeServerId" onchange="updateActiveServer()">';
    foreach ($serverPings as $serverPing) {
        $disabled = $serverPing['code'] != 200 ? ' [HTTP: ' . $serverPing['code'] . ']' : '';

        $serverList .= '<option ' . ($disabled ? 'disabled ' : '') . ($activeServer['id'] == $serverPing['id'] ? 'selected' : '') . ' value="' . $serverPing['id'] . '">' . $serverPing['name'] . $disabled . '</option>';

        if ($serverPing['id'] != APP_SERVER_ID) {
            $links .= ' <a style="display: ' . ($activeServer['id'] == $serverPing['id'] ? 'inline-block' : 'none') . ';" id="external-server-icon-link-' . $serverPing['id'] . '" class="text-info" href="' . $serverPing['url'] . '" target="_blank" title="Open this server in a new tab"><i class="fas fa-external-link-alt fa-lg"></i></a>';
        }
    }
    $serverList .= '</select>';
    $serverList .= $links;

    $_SESSION['serverList']         = $serverList;
    $_SESSION['serverListUpdated']  = time();

    return $serverList;
}
