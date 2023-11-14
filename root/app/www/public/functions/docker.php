<?php

/*
----------------------------------
 ------  Created: 100723   ------
 ------  Austin Best	   ------
----------------------------------
*/

function dockerState()
{
    $state = dockerProcessList();
    $state = json_decode($state, true);

    $dockerStats = dockerStats();
    $dockerStats = json_decode($dockerStats, true);
    
    if (!empty($state)) {
        foreach ($state as $index => $process) {
            $inspect = dockerInspect($process['Names']);
            $state[$index]['inspect'] = json_decode($inspect, true);

            foreach ($dockerStats as $dockerStat) {
                if ($dockerStat['Name'] == $process['Names']) {
                    $state[$index]['stats'] = $dockerStat;
                    break;
                }
            }
        }
    }

    return $state;
}

function dockerPermissionCheck()
{
    $response = dockerProcessList();
    return empty(json_decode($response, true)) ? false : true;
}

function dockerStats()
{
    $cmd = '/usr/bin/docker stats --all --no-trunc --no-stream --format="{{json . }}" | jq -s --tab .';
    return shell_exec($cmd . ' 2>&1');
}

function dockerInspect($containerName)
{
    $cmd = '/usr/bin/docker inspect ' . $containerName . ' --format="{{json . }}" | jq -s --tab .';
    return shell_exec($cmd . ' 2>&1');
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

function dockerProcessList()
{
    $cmd = '/usr/bin/docker ps --all --no-trunc --format="{{json . }}" | jq -s --tab .';
    return shell_exec($cmd . ' 2>&1');
}

function dockerCopyFile($from, $to)
{
    $cmd = '/usr/bin/docker cp ' . $from . ' ' . $to;
    return shell_exec($cmd . ' 2>&1');
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
