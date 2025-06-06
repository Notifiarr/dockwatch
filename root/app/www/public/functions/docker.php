<?php

/*
----------------------------------
 ------  Created: 100723   ------
 ------  Austin Best	   ------
----------------------------------
*/

function getExpandedProcessList($fetchProc, $fetchStats, $fetchInspect, $maintenance = false)
{
    global $docker;

    if ($maintenance) {
        logger(MAINTENANCE_LOG, '$fetchProc=' . $fetchProc);
        logger(MAINTENANCE_LOG, '$fetchStats=' . $fetchStats);
        logger(MAINTENANCE_LOG, '$fetchInspect=' . $fetchInspect);
    }

    $processList = $loadTimes = [];

    if ($fetchProc) {
        $loadTimes[] = trackTime('dockerProcessList ->');
        if ($maintenance) {
            logger(MAINTENANCE_LOG, 'dockerProcessList ->');
            $processList = $docker->processList(false);
            logger(MAINTENANCE_LOG, json_encode(json_decode($processList)));
            $processList = json_decode($processList, true);
            logger(MAINTENANCE_LOG, 'dockerProcessList <-');
        } else {
            $processList = apiRequest('docker/processList', ['format' => true])['result'];
            $processList = json_decode($processList, true);
        }
        $loadTimes[] = trackTime('dockerProcessList <-');

        $loadTimes[] = trackTime('dockerImageSizes ->');
        if ($maintenance) {
            logger(MAINTENANCE_LOG, 'dockerImageSizes ->');
            $imageSizes = $docker->getImageSizes();
            logger(MAINTENANCE_LOG, json_encode(json_decode($imageSizes)));
            $imageSizes = json_decode($imageSizes, true);
            logger(MAINTENANCE_LOG, 'dockerImageSizes <-');
        } else {
            $imageSizes = apiRequest('docker/images/sizes')['result'];
            $imageSizes = json_decode($imageSizes, true);
        }
        $loadTimes[] = trackTime('dockerImageSizes <-');
    }

    if ($fetchStats) {
        $loadTimes[] = trackTime('dockerStats ->');

        if ($maintenance) {
            logger(MAINTENANCE_LOG, 'dockerStats ->');
            $dockerStats = getFile(STATS_FILE);
            logger(MAINTENANCE_LOG, json_encode($dockerStats, JSON_UNESCAPED_SLASHES));
            logger(MAINTENANCE_LOG, 'dockerStats <-');
        } else {
            $dockerStats = apiRequest('file/stats')['result'];
        }

        if (!$dockerStats) { //-- NOT WRITTEN YET
            if ($maintenance) {
                $dockerStats = $docker->stats(false);
                logger(MAINTENANCE_LOG, $dockerStats);
                $dockerStats = json_decode($dockerStats, true);
            } else {
                $dockerStats = apiRequest('docker/stats', $_GET)['result'];
                $dockerStats = json_decode($dockerStats, true);
            }
        }

        $loadTimes[] = trackTime('dockerStats <-');
    }

    if (!empty($processList)) {
        $loadTimes[] = trackTime('dockerInspect ->');
        //-- GATHER ALL CONTAINERS FOR A SINGLE INSPECT
        $inspectResults = $inspectContainers = [];
        if ($fetchInspect) {
            foreach ($processList as $index => $process) {
                $inspectContainers[] = $process['Names'];
            }

            if ($maintenance) {
                logger(MAINTENANCE_LOG, 'dockerInspect ->');
                logger(MAINTENANCE_LOG, 'inspecting: ' . implode(' ', $inspectContainers));
                $inspect        = $docker->inspect(implode(' ', $inspectContainers));
                $inspectResults = json_decode($inspect, true);
                logger(MAINTENANCE_LOG, '$docker->inspect ' . json_encode(json_decode($inspect)));
                logger(MAINTENANCE_LOG, 'dockerInspect <-');
            } else {
                $inspect        = apiRequest('docker/container/inspect', ['name' => implode(' ', $inspectContainers), 'format' => true])['result'];
                $inspectResults = json_decode($inspect, true);
            }
        }

        //-- ADD INSPECT AND STATS TO PROCESS LIST
        foreach ($processList as $index => $process) {
            //-- ADD THE INSPECT OBJECT IF IT EXISTS
            $processList[$index]['inspect'][] = $inspectResults[$index];

            if ($inspectResults[$index]) {
                $image = str_replace('sha256:', '', $inspectResults[$index]['Image']);

                foreach ($imageSizes as $imageSize) {
                    if (substr($image, 0, 12) == $imageSize['ID']) {
                        $processList[$index]['size'] = $imageSize['Size'];
                        break;
                    }
                }
            }

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

function updateDependencyParentId($container, $id)
{
    global $dependencyFile;

    $dependencyFile[$container]['id'] = $id;
    apiRequest('file/dependency', [], ['contents' => $dependencyFile]);
}

function dockerState()
{
    $processList = apiRequest('docker/processList', ['format' => true, 'useCache' => false])['result'];
    $processList = json_decode($processList, true);

    $dockerStats = apiRequest('docker/stats', ['useCache' => false])['result'];
    $dockerStats = json_decode($dockerStats, true);

    if (!empty($processList)) {
        foreach ($processList as $index => $process) {
            $inspect = apiRequest('docker/container/inspect', ['name' => $process['Names'], 'useCache' => false])['result'];
            $processList[$index]['inspect'] = json_decode($inspect, true);

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
    $apiRequest = apiRequest('docker/processList', ['format' => true]);
    logger(UI_LOG, '$apiRequest: ' . json_encode($apiRequest));
    logger(UI_LOG, 'dockerPermissionCheck <-');

    return empty(json_decode($apiRequest['result'], true)) ? false : true;
}

function dockerCreateContainer($inspect)
{
    global $docker;

    $create     = $docker->apiCreateContainer($inspect);
    $apiRequest = $docker->apiCurl($create, 'post');

    return $apiRequest;
}

function dockerAutoCompose($containerName)
{
    global $shell;

    $cmd        = sprintf(DockerSock::RUN, '--rm -v /var/run/docker.sock:/var/run/docker.sock ghcr.io/red5d/docker-autocompose ' . $shell->prepare($containerName));
    $compose    = $shell->exec($cmd . ' 2>&1');
    $lines      = explode("\n", $compose);
    $compose    = [];
    $skip       = true; //-- IGNORE ALL THE IMAGE PULL NOISE

    //-- LOOP THIS SO IT REMOVES ALL THE ADD CONTAINER OVERHEAD
    foreach ($lines as $line) {
        if (str_contains($line, 'networks:') || str_contains($line, 'services:')) {
            $skip = false;
        }

        if (!$skip && trim($line)) {
            $compose[] = $line;
        }
    }

    return $cmd . '<hr>' . implode("\n", $compose);
}

function dockerAutoRun($container)
{
    global $shell;

    $indent         = '  ';
    $glue           = "\n";
    $cmd            = sprintf(DockerSock::INSPECT_FORMAT, $container);
    $containerJson  = $shell->exec($cmd . ' 2>&1');
    $containerArray = json_decode($containerJson, true);
    $containerArray = $containerArray[0];
    $image          = $containerArray['Config']['Image'];

    $cmd            = sprintf(DockerSock::INSPECT_FORMAT, $image);
    $imageJson      = $shell->exec($cmd . ' 2>&1');
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
                                'Runtime'   => 'runtime',
                                'UTSMode'   => 'uts'
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
