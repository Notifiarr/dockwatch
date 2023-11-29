<?php

/*
----------------------------------
 ------  Created: 100723   ------
 ------  Austin Best	   ------
----------------------------------
*/

function findContainerFromHash($hash)
{
    global $stateFile;

    if (!$stateFile) {
        $stateFile = getServerFile('state');
        $stateFile = $stateFile['file'];
    }

    foreach ($stateFile as $container) {
        if (md5($container['Names']) == $hash) {
            return $container;
        }
    }
}

function dockerState()
{
    $processList = apiRequest('dockerProcessList', ['useCache' => false]);
    $processList = json_decode($processList['response']['docker'], true);

    $dockerStats = apiRequest('dockerStats', ['useCache' => false]);
    $dockerStats = json_decode($dockerStats['response']['docker'], true);

    if (!empty($processList)) {
        foreach ($processList as $index => $process) {
            $inspect = apiRequest('dockerInspect', ['name' => $process['Names'], 'useCache' => false]);
            $processList[$index]['inspect'] = json_decode($inspect['response']['docker'], true);

            foreach ($dockerStats as $dockerStat) {
                if ($dockerStat['Name'] == $process['Names']) {
                    $processList[$index]['stats'] = $dockerStat;
                    break;
                }
            }
        }
    }

    return $processList;
}

function dockerPermissionCheck()
{
    $response = apiRequest('dockerProcessList');
    return empty(json_decode($response['response']['docker'], true)) ? false : true;
}

function dockerProcessList($useCache = true)
{
    $cacheKey   = MEMCACHE_PREFIX . 'dockerProcessList';
    $cache      = memcacheGet($cacheKey);
    if ($cache && $useCache) {
        return $cache;
    } else {
        $cmd    = '/usr/bin/docker ps --all --no-trunc --format="{{json . }}" | jq -s --tab .';
        $shell  = shell_exec($cmd . ' 2>&1');
        memcacheSet($cacheKey, $shell, MEMCACHE_DOCKER_PROCESS);
        return $shell;
    }
}

function dockerStats($useCache = true)
{
    $cacheKey   = MEMCACHE_PREFIX . 'dockerStats';
    $cache      = memcacheGet($cacheKey);
    if ($cache && $useCache) {
        return $cache;
    } else {
        $cmd    = '/usr/bin/docker stats --all --no-trunc --no-stream --format="{{json . }}" | jq -s --tab .';
        $shell  = shell_exec($cmd . ' 2>&1');
        memcacheSet($cacheKey, $shell, MEMCACHE_DOCKER_STATS);
        return $shell;
    }
}

function dockerInspect($containerName, $useCache = true)
{
    $cacheKey   = MEMCACHE_PREFIX . 'dockerInspect.' . md5($containerName);
    $cache      = memcacheGet($cacheKey);
    if ($cache && $useCache) {
        return $cache;
    } else {
        $cmd    = '/usr/bin/docker inspect ' . $containerName . ' --format="{{json . }}" | jq -s --tab .';
        $shell  = shell_exec($cmd . ' 2>&1');
        memcacheSet($cacheKey, $shell, MEMCACHE_DOCKER_INSPECT);
        return $shell;
    }
}

function dockerContainerLogs($containerName, $log)
{
    if ($log != 'docker' && file_exists('/appdata/' . $containerName . '/logs/' . $log . '.log')) {
        $logFile    = file('/appdata/' . $containerName . '/logs/' . $log . '.log');
        $return     = '';
        foreach ($logFile as $line) {
            $line = json_decode($line, true);
            $return .= '[' . $line['timestamp'] . '] {' . $line['level'] . '} ' . $line['message'] . "\n";
        }
        return $return;
    }

    if ($log == 'docker') {
        $cmd = '/usr/bin/docker logs ' . $containerName;
        return shell_exec($cmd . ' 2>&1');
    }
}

function dockerStartContainer($containerName)
{
    $cmd = '/usr/bin/docker start ' . $containerName;
    return shell_exec($cmd . ' 2>&1');
}

function dockerRemoveContainer($containerId)
{
    $cmd = '/usr/bin/docker rm -f ' . $containerId;
    return shell_exec($cmd . ' 2>&1');
}

function dockerStopContainer($containerName)
{
    $cmd = '/usr/bin/docker stop ' . $containerName;
    return shell_exec($cmd . ' 2>&1');
}

function dockerPullContainer($image)
{
    $cmd = '/usr/bin/docker pull ' . $image;
    return shell_exec($cmd . ' 2>&1');
}

function dockerAutoCompose($containerName)
{
    $cmd        = '/usr/bin/docker run --rm -v /var/run/docker.sock:/var/run/docker.sock ghcr.io/red5d/docker-autocompose ' . $containerName;
    $compose    = shell_exec($cmd . ' 2>&1');
    $lines      = explode("\n", $compose);
    $skip       = true;
    $command    = [];
    //-- LOOP THIS SO IT REMOVES ALL THE ADD CONTAINER OVERHEAD
    foreach ($lines as $line) {
        if (strpos($line, 'networks:') !== false || strpos($line, 'services:') !== false) {
            $skip = false;
        }

        if ($skip) {
            continue;
        }

        if (trim($line)) {
            $command[] = $line;
        }
    }

    return implode("\n", $command);
}

function dockerAutoRun($containerName)
{
    // Smarter people than me... https://gist.github.com/efrecon/8ce9c75d518b6eb863f667442d7bc679
    $cmd = '/usr/bin/docker inspect --format "$(cat ' . ABSOLUTE_PATH . 'run.tpl)" ' . $containerName;
    return shell_exec($cmd . ' 2>&1');
}

function dockerUpdateContainer($command)
{
    $cmd = '/usr/bin/' . $command;
    return shell_exec($cmd . ' 2>&1');
}

function dockerGetOrphanContainers()
{
    $cmd = '/usr/bin/docker images -f dangling=true --format="{{json . }}" | jq -s --tab .';
    return shell_exec($cmd . ' 2>&1');
}

function dockerGetOrphanVolumes()
{
    $cmd = '/usr/bin/docker volume ls -qf dangling=true --format="{{json . }}" | jq -s --tab .';
    return shell_exec($cmd . ' 2>&1');
}

function dockerRemoveImage($id)
{
    $cmd = '/usr/bin/docker rmi ' . $id;
    return shell_exec($cmd . ' 2>&1');
}

function dockerPruneImage()
{
    $cmd = '/usr/bin/docker image prune -af';
    return shell_exec($cmd . ' 2>&1');
}

function dockerRemoveVolume($name)
{
    $cmd = '/usr/bin/docker volume rm ' . $name;
    return shell_exec($cmd . ' 2>&1');
}

function dockerPruneVolume()
{
    $cmd = '/usr/bin/docker volume prune -af';
    return shell_exec($cmd . ' 2>&1');
}