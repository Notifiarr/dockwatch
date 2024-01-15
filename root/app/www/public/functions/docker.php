<?php

/*
----------------------------------
 ------  Created: 100723   ------
 ------  Austin Best	   ------
----------------------------------
*/

function getExpandedProcessList($fetchProc, $fetchStats, $fetchInspect)
{
    $processList = $loadTimes = [];

    if ($fetchProc) {
        $loadTimes[]    = trackTime('dockerProcessList ->');
        $processList    = apiRequest('dockerProcessList', ['format' => true]);
        $processList    = json_decode($processList['response']['docker'], true);
        $loadTimes[]    = trackTime('dockerProcessList <-');
    
        $loadTimes[]    = trackTime('dockerImageSizes ->');
        $imageSizes     = apiRequest('dockerImageSizes');
        $imageSizes     = json_decode($imageSizes['response']['docker'], true);
        $loadTimes[]    = trackTime('dockerImageSizes <-');
    }
    
    if ($fetchStats) {
        $loadTimes[] = trackTime('dockerStats ->');
        $dockerStats = apiRequest('stats');
    
        if (!$dockerStats['response']['state']) { //-- NOT WRITTEN YET
            $dockerStats = apiRequest('dockerStats');
            $dockerStats = json_decode($dockerStats['response']['docker'], true);
        } else {
            $dockerStats = $dockerStats['response']['state'];
        }
    
        $loadTimes[] = trackTime('dockerStats <-');
    }
    
    if (!empty($processList)) {
        $loadTimes[] = trackTime('dockerInspect ->');
        //-- GATHER ALL CONTAINERS FOR A SINGLE INSPECT
        $inspectResults = [];
        if ($fetchInspect) {
            foreach ($processList as $index => $process) {
                $inspectContainers[] = $process['Names'];
            }
            $inspect        = apiRequest('dockerInspect', ['name' => implode(' ', $inspectContainers), 'format' => true]);
            $inspectResults = json_decode($inspect['response']['docker'], true);
        }
    
        //-- ADD INSPECT AND STATS TO PROCESS LIST
        foreach ($processList as $index => $process) {
            //-- ADD THE INSPECT OBJECT IF IT EXISTS
            $processList[$index]['inspect'][] = $inspectResults[$index];
    
            if ($fetchStats) {
                foreach ($dockerStats as $dockerStat) {
                    if ($dockerStat['Name'] == $process['Names']) {
                        $processList[$index]['stats'] = $dockerStat;
                        break;
                    }
                }
            }
        }
        $loadTimes[] = trackTime('dockerInspect <-');
    }

    return ['processList' => $processList, 'loadTimes' => $loadTimes];
}

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
    $processList = apiRequest('dockerProcessList', ['format' => true, 'useCache' => false]);
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
    logger(UI_LOG, 'dockerPermissionCheck ->');
    $response = apiRequest('dockerProcessList', ['format' => true]);
    logger(UI_LOG, '$response: ' . json_encode($response));
    logger(UI_LOG, 'dockerPermissionCheck <-');
    return empty(json_decode($response['response']['docker'], true)) ? false : true;
}

function dockerProcessList($useCache = true, $format = true, $params = '')
{
    logger(SYSTEM_LOG, 'dockerProcessList ->');
    if ($format) {
        $cmd = '/usr/bin/docker ps --all --no-trunc --size=false --format="{{json . }}" | jq -s --tab .';
    } else {
        $cmd = '/usr/bin/docker ps ' . $params;
    }

    logger(SYSTEM_LOG, '$cmd=' . $cmd);
    $shell  = shell_exec($cmd . ' 2>&1');
    logger(SYSTEM_LOG, '$shell=' . $shell);
    logger(SYSTEM_LOG, 'dockerProcessList <-');
    return $shell;
}

function dockerStats($useCache = true)
{
    if (file_exists(STATS_FILE)) {
        $statsFile = file_get_contents(STATS_FILE);
    }

    if ($statsFile && $useCache) {
        return $statsFile;
    } else {
        $cmd    = '/usr/bin/docker stats --all --no-trunc --no-stream --format="{{json . }}" | jq -s --tab .';
        $shell  = shell_exec($cmd . ' 2>&1');
        setServerFile('stats', $shell);
        return $shell;
    }
}

function dockerInspect($containerName, $useCache = true, $format = true, $params = '')
{
    if ($format) {
        $cmd = '/usr/bin/docker inspect ' . $containerName . ' --format="{{json . }}" | jq -s --tab .';
    } else {
        $cmd = '/usr/bin/docker inspect ' . $containerName . ' ' . $params;
    }

    $shell  = shell_exec($cmd . ' 2>&1');
    return $shell;
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

function dockerRemoveContainer($containerName)
{
    $cmd = '/usr/bin/docker rm -f ' . $containerName;
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

function dockerUpdateContainer($inspect)
{
    $create     = dockerContainerCreateAPI($inspect);
    $apiRequest = dockerCurlAPI($create, 'post');
    return $apiRequest;
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

function dockerNetworks($params = '')
{
    $cmd = '/usr/bin/docker network ' . $params;
    return shell_exec($cmd . ' 2>&1');
}

function dockerPort($containerName, $params = '')
{
    $cmd = '/usr/bin/docker port ' . $containerName . ' ' . $params;
    return shell_exec($cmd . ' 2>&1');
}

function dockerImageSizes()
{
    $cmd = '/usr/bin/docker images --format=\'{"ID":"{{ .ID }}", "Size": "{{ .Size }}"}\' | jq -s --tab .';
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
        if (str_contains($line, 'networks:') || str_contains($line, 'services:')) {
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

function dockerAutoRun($container)
{
    $indent         = '  ';
    $glue           = "\n";
    $cmd            = '/usr/bin/docker inspect ' . $container . ' --format="{{json . }}" | jq -s --tab .';
    $containerJson  = shell_exec($cmd . ' 2>&1');
    $containerArray = json_decode($containerJson, true);
    $containerArray = $containerArray[0];
    $image          = $containerArray['Config']['Image'];

    $cmd            = '/usr/bin/docker inspect ' . $image . ' --format="{{json . }}" | jq -s --tab .';
    $imageJson      = shell_exec($cmd . ' 2>&1');
    $imageArray     = json_decode($imageJson, true);
    $imageArray     = $imageArray[0];

    $runCommand[] = 'docker run \\';
    $runCommand[] = $indent . "--detach \\";

    //-- CHECK FOR AN OVERRIDE
    $name = dockerRunFieldValue('Name', $imageArray['Name'], $containerArray['Name']);
    $runCommand[] = $indent . '--name "' . $name . '" \\';

    //-- <key> FIELDS
    $hostConfigKeyFields    = [
                                'Privileged' => 'privileged',
                                'AutoRemove' => 'rm'
                            ];

    foreach ($hostConfigKeyFields as $fieldLabel => $fieldKey) {
        if ($containerArray['HostConfig'][$fieldLabel]) {
            $runCommand[] = $indent . '--' . $fieldKey . ' \\';
        }
    }

    //-- <key>:<val> FIELDS
    $hostConfigPairFields   = [
                                'Runtime'       => 'runtime',
                                'UTSMode'       => 'uts'
                            ];
    
    foreach ($hostConfigPairFields as $fieldLabel => $fieldKey) {
        if ($containerArray['HostConfig'][$fieldLabel]) {
            $runCommand[] = $indent . '--' . $fieldKey . ' "' . $containerArray['HostConfig'][$fieldLabel] . '" \\';
        }
    }

    //-- VOLUMES
    if ($containerArray['HostConfig']['Binds']) {
        foreach ($containerArray['HostConfig']['Binds'] as $volume) {
            $runCommand[] = $indent . '--volume "' . $volume . '" \\';
        }
    }

    if ($containerArray['HostConfig']['VolumesFrom']) {
        foreach ($containerArray['HostConfig']['VolumesFrom'] as $volumeFrom) {
            $runCommand[] = $indent . '--volumes-from "' . $volumeFrom . '" \\';
        }
    }

    //-- LINKS
    if ($containerArray['HostConfig']['Links']) {
        foreach ($containerArray['HostConfig']['Links'] as $link) {
            $runCommand[] = $indent . '--link "' . $link . '" \\';
        }
    }

    //-- MOUNTS
    if ($containerArray['HostConfig']['Mounts']) {
        foreach ($containerArray['HostConfig']['Mounts'] as $mount) {
            $thisMount = [];
            $thisMount[] = '--mount type=' . $mount['Type'];
            if ($mount['Source']) {
                $thisMount[] = 'source=' . $mount['Source'];
            }
            if ($mount['Target']) {
                $thisMount[] = 'target=' . $mount['Target'];
            }
            if ($mount['ReadOnly']) {
                $thisMount[] = 'readOnly';
            }
            if (!empty($mount['VolumeOptions'])) {
                foreach ($mount['VolumeOptions'] as $volumeOption) {
                    // stuff here...
                }
            }
            if (!empty($mount['DriverConfig'])) {
                foreach ($mount['DriverConfig'] as $driverConfig) {
                    // stuff here...
                }
            }
            if (!empty($mount['BindOptions'])) {
                foreach ($mount['BindOptions'] as $bindOptions) {
                    // stuff here...
                }
            }
            // UNCOMMENT THIS WHEN THE UPPER SECTION IS FINISHED
            //$runCommand[] = $indent . implode(', ', $thisMount) .' \\';
        }
    }

    //-- LOGGING
    if (!empty($containerArray['HostConfig']['LogConfig'])) {
        $runCommand[] = $indent . '--log-driver "' . $containerArray['HostConfig']['LogConfig']['Type'] . '" \\';

        if (!empty($containerArray['HostConfig']['LogConfig']['Config'])) {
            foreach ($containerArray['HostConfig']['LogConfig']['Config'] as $logConfigKey => $logConfigVal) {
                $runCommand[] = $indent . '--log-opt ' . $logConfigKey . '="' . $logConfigVal . '" \\';
            }
        }
    }

    //-- RESTART
    $runCommand[] = $indent . '--restart "' . $containerArray['HostConfig']['RestartPolicy']['Name'] . ($containerArray['HostConfig']['RestartPolicy']['Name'] == 'on-failure' ? ':' . $containerArray['HostConfig']['RestartPolicy']['MaximumRetryCount'] : '') . '" \\';

    //-- HOSTS
    if ($containerArray['HostConfig']['ExtraHosts']) {
        foreach ($containerArray['HostConfig']['ExtraHosts'] as $host) {
            $runCommand[] = $indent . '--add-host "' . $host . '" \\';
        }
    }

    //-- CAPABILITES
    if ($containerArray['HostConfig']['CapAdd']) {
        foreach ($containerArray['HostConfig']['CapAdd'] as $addCap) {
            $runCommand[] = $indent . '--cap-add "' . $addCap . '" \\';
        }
    }
    if ($containerArray['HostConfig']['CapDrop']) {
        foreach ($containerArray['HostConfig']['CapDrop'] as $dropCap) {
            $runCommand[] = $indent . '--cap-drop "' . $dropCap . '" \\';
        }
    }

    //-- DEVICES
    if ($containerArray['HostConfig']['Devices']) {
        foreach ($containerArray['HostConfig']['Devices'] as $device) {
            $runCommand[] = $indent . '--device "' . $device['PathOnHost'] . ':' . $device['PathInContainer'] . ':' . $device['CgroupPermissions'] . '" \\';
        }
    }

    //-- SECURITY OPTIONS
    if ($containerArray['HostConfig']['SecurityOpt']) {
        foreach ($containerArray['HostConfig']['SecurityOpt'] as $securityOpt) {
            $runCommand[] = $indent . '--security-opt "' . $securityOpt . '" \\';
        }
    }

    //-- NETWORK
    $containerNetwork = str_contains($containerArray['HostConfig']['NetworkMode'], ':') ? true : false;
    if ($containerNetwork) {
        $runCommand[] = $indent . '--network "' . $containerArray['HostConfig']['NetworkMode'] . '" \\';
    } else {
        if ($containerArray['Config']['Hostname']) {
            $runCommand[] = $indent . '--hostname "' . $containerArray['Config']['Hostname'] . '" \\';
        }

        if (!empty($containerArray['NetworkSettings']['Networks'])) {
            foreach ($containerArray['NetworkSettings']['Networks'] as $networkName => $networkSettings) {
                $runCommand[] = $indent . '--network "' . $networkName . '" \\';

                if (!empty($networkSettings['Aliases'])) {
                    foreach ($networkSettings['Aliases'] as $networkAlias) {
                        $runCommand[] = $indent . '--network-alias "' . $networkAlias . '" \\';
                    }
                }
            }
        }

        if (!empty($containerArray['NetworkSettings']['Ports'])) {
            foreach ($containerArray['NetworkSettings']['Ports'] as $port => $portSettings) {
                if (empty($portSettings)) {
                    continue;
                }

                foreach ($portSettings as $portSetting) {
                    $runCommand[] = $indent . '--publish "' . $portSetting['HostIp'] . ':' . $portSetting['HostPort'] . ':' . $port . '" \\';
                    break; //-- ONLY PULL THE FIRST ONE
                }
            }
        }

        if ($containerArray['HostConfig']['PublishAllPorts']) {
            $runCommand[] = $indent . '--publish-all \\';
        }
    }

    //-- STATIC IP
    if (!empty($containerArray['NetworkSettings']['Networks'])) {
        $network = array_keys($containerArray['NetworkSettings']['Networks'])[0];

        if (!empty($containerArray['NetworkSettings']['Networks'][$network]['IPAMConfig'])) {
            if ($containerArray['NetworkSettings']['Networks'][$network]['IPAMConfig']['IPv4Address']) {
                $runCommand[] = $indent . '--ip "'. $containerArray['NetworkSettings']['Networks'][$network]['IPAMConfig']['IPv4Address'] .'" \\';
            }
            if ($containerArray['NetworkSettings']['Networks'][$network]['IPAMConfig']['IPv6Address']) {
                $runCommand[] = $indent . '--ip6 "'. $containerArray['NetworkSettings']['Networks'][$network]['IPAMConfig']['IPv6Address'] .'" \\';
            }
        }
    }

    //-- <key> FIELDS
    $configKeyFields    = [
                            'Tty' => 'tty'
                        ];

    foreach ($configKeyFields as $fieldLabel => $fieldKey) {
        if ($containerArray['Config'][$fieldLabel]) {
            $runCommand[] = $indent . '--' . $fieldKey . ' \\';
        }
    }

    //-- <key>:<val> FIELDS
    $configPairFields   = [
                            'Domainname' => 'domainname'
                        ];

    foreach ($configPairFields as $fieldLabel => $fieldKey) {
        if ($containerArray['Config'][$fieldLabel]) {
            $runCommand[] = $indent . '--' . $fieldKey . ' "' . $containerArray['Config'][$fieldLabel] . '" \\';
        }
    }

    $user = dockerRunFieldValue('User', $imageArray['Config']['User'], $containerArray['Config']['User']);
    if ($user) {
        $runCommand[] = $indent . '--user "' . $user . '" \\';
    }

    //-- EXPOSED PORTS
    if (!$containerNetwork && $containerArray['Config']['ExposedPorts']) {
        foreach (array_keys($containerArray['Config']['ExposedPorts']) as $port) {
            $runCommand[] = $indent . '--expose "' . $port . '" \\';
        }
    }

    //-- IPC MODE
    if ($containerArray['HostConfig']['IpcMode']) {
        $runCommand[] = $indent . '--ipc "' . trim($containerArray['HostConfig']['IpcMode']) . '" \\';
    }

    //-- WORKING DIRECTORY
    if ($containerArray['Config']['WorkingDir']) {
        $runCommand[] = $indent . '--workdir "' . trim($containerArray['Config']['WorkingDir']) . '" \\';
    }

    //-- ENV VARS
    if ($containerArray['Config']['Env']) {
        foreach ($containerArray['Config']['Env'] as $env) {
            $runCommand[] = $indent . '--env "' . trim(str_replace('"', '\"', $env)) . '" \\';
        }
    }

    //-- HEALTHCHECK
    if ($containerArray['Config']['Healthcheck']) {
        $healthCommand = []; 

        if ($containerArray['Config']['Healthcheck']['Test']) {
            foreach ($containerArray['Config']['Healthcheck']['Test'] as $cmd) {
                $healthCommand[] = $cmd;
            }
        }

        if (!empty($healthCommand) && $healthCommand[0] != 'NONE') {
            array_shift($healthCommand); //-- REMOVE [CMD, CMD-SHELL, NONE]
            $runCommand[] = $indent . '--health-cmd "' . implode(' ', $healthCommand) . '" \\';

            if ($containerArray['Config']['Healthcheck']['Interval']) {
                $runCommand[] = $indent . '--health-interval "' . convertDockerTimestamp($containerArray['Config']['Healthcheck']['Interval']) . '" \\';
            }
    
            if ($containerArray['Config']['Healthcheck']['Timeout']) {
                $runCommand[] = $indent . '--health-timeout "' . convertDockerTimestamp($containerArray['Config']['Healthcheck']['Timeout']) . '" \\';
            }

            if ($containerArray['Config']['Healthcheck']['Retries']) {
                $runCommand[] = $indent . '--health-retries "' . $containerArray['Config']['Healthcheck']['Retries'] . '" \\';
            }

            if ($containerArray['Config']['Healthcheck']['StartPeriod']) {
                $runCommand[] = $indent . '--health-start-period "' . convertDockerTimestamp($containerArray['Config']['Healthcheck']['StartPeriod']) . '" \\';
            }
        }
    }

    //-- LABELS
    if ($containerArray['Config']['Labels']) {
        foreach ($containerArray['Config']['Labels'] as $label => $value) {
            $runCommand[] = $indent . '--label "' . trim($label) . '"="' . trim(str_replace(['"', '`'], ['\"', '\`'], $value)) . '" \\';
        }
    }

    if ($containerArray['Config']['OpenStdin']) {
        $runCommand[] = $indent . '--interactive \\';
    }

    //-- ENTRY
    if ($containerArray['Config']['Entrypoint']) {
        $entryPoints = [];

        if (!empty($containerArray['Config']['Cmd'])) {
            foreach ($containerArray['Config']['Entrypoint'] as $entryPoint) {
                $entryPoints[] = $entryPoint;
            }
        } else {
            $entryPoints[] = $containerArray['Config']['Entrypoint'][0];
        }

        $runCommand[] = $indent . '--entrypoint "' .  implode('" "', $entryPoints) . '" \\';
    }

    $runCommand[] = $indent . '"' . $containerArray['Config']['Image'] . '" \\';

    //-- COMMAND
    if (!empty($containerArray['Config']['Cmd'])) {
        $runCommand[] = $indent . '"' . implode('" "', $containerArray['Config']['Cmd']) . '" \\';
    } else {
        $containerCmd = $containerArray['Config']['Entrypoint'];
        array_shift($containerCmd);

        if (!empty($containerCmd)) {
            $runCommand[] = $indent . '"' . implode('" "', $containerCmd) . '" \\';
        }
    }

    $runCommand = implode($glue, $runCommand);
    $runCommand = rtrim($runCommand, '\\');

    return $runCommand;
}

function dockerRunFieldValue($field, $imageVal, $containerVal)
{
    /*
        This will compare the values of an inspect from the container and the image to look for differences
        If the container is different from the image, the default was overridden and should be used
        In some cases, if the values are the same it should be ommited
    */

    switch ($field) {
        //-- RETURN THE $containerVal IF DIFFERENT FROM $imageVal
        case 'Name':
            if ($containerVal != $imageVal) {
                return trim($containerVal);
            }
            return trim($imageVal);
        //-- RETURN NOTHING IF THEY ARE THE SAME
        case 'User':
            if ($containerVal == $imageVal) {
                return;
            } elseif ($containerVal != $imageVal) {
                return trim($containerVal);
            }
            return trim($imageVal);
    }
}

function isDockerIO($name)
{
    if (!$name) {
        return;
    }

    return str_contains($name, '/') ? $name : 'library/' . $name;
}
