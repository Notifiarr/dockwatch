<?php

/*
----------------------------------
 ------  Created: 072624   ------
 ------  Austin Best       ------
----------------------------------
*/

function getStats()
{
    $stateFile = getFile(STATE_FILE);
    $pullsFile = getFile(PULL_FILE);
    
    $containers = [];
    $ports = $networks = [];
    $running = $stopped = $memory = $cpu = $network = $size = $updated = $outdated = $healthy = $unhealthy = $unknownhealth = 0;

    foreach ($stateFile as $state) {
        $size += bytesFromString($state['size']);

        $healthStatus = 'Unknown';
        if (str_contains($state['Status'], 'healthy')) {
            $healthy++;
            $healthStatus = 'Healthy';
        } elseif (str_contains($state['Status'], 'unhealthy')) {
            $unhealthy++;
            $healthStatus = 'Unhealthy';
        } elseif (!str_contains($state['Status'], 'health')) {
            $unknownhealth++;
        }

        if ($state['State'] == 'running') {
            $running++;
        } else {
            $stopped++;
        }

        //-- GET UPDATES
        $pullInfo = 'Unchecked';
        if ($pullsFile) {
            foreach ($pullsFile as $hash => $pull) {
                if (md5($state['Names']) == $hash) {
                    if ($pull['regctlDigest'] == $pull['imageDigest']) {
                        $updated++;
                        $pullInfo = 'Updated';
                    } else {
                        $outdated++;
                        $pullInfo = 'Outdated';
                    }
                    break;
                }
            }
        }

        //-- GET USED NETWORKS
        if ($state['inspect'][0]['NetworkSettings']['Networks']) {
            $networkKeys = array_keys($state['inspect'][0]['NetworkSettings']['Networks']);
            foreach ($networkKeys as $networkKey) {
                $networks[$networkKey]++;
            }
        } else {
            $containerNetwork = $state['inspect'][0]['HostConfig']['NetworkMode'];
            if (str_contains($containerNetwork, ':')) {
                list($null, $containerId) = explode(':', $containerNetwork);
                $containerNetwork = 'container:' . $docker->findContainer(['id' => $containerId, 'data' => $stateFile]);
            }

            $networks[$containerNetwork]++;
        }

        //-- GET USED PORTS
        if ($state['inspect'][0]['HostConfig']['PortBindings']) {
            foreach ($state['inspect'][0]['HostConfig']['PortBindings'] as $internalBind => $portBinds) {
                foreach ($portBinds as $portBind) {
                    if ($portBind['HostPort']) {
                        $ports[$state['Names']][] = $portBind['HostPort'];
                    }
                }
            }
        }

        //-- GET MEMORY UAGE
        $memory += floatval(str_replace('%', '', $state['stats']['MemPerc']));

        //-- GET CPU USAGE
        $cpu += floatval(str_replace('%', '', $state['stats']['CPUPerc']));

        //-- GET NETWORK USAGE
        list($netUsed, $netAllowed) = explode(' / ', $state['stats']['NetIO']);
        $network += bytesFromString($netUsed);

        $containers['containers'][$state['Names']] = [
                                                        'id'        => $state['ID'],
                                                        'image'     => $state['Image'],
                                                        'ports'     => $state['Ports'],
                                                        'started'   => $state['RunningFor'],
                                                        'running'   => $state['State'] == 'running' ? true : false,
                                                        'status'    => $healthStatus,
                                                        'size'      => $state['size'],
                                                        'update'    => $pullInfo
                                                    ];
    }

    return [
                ...$containers,
                'status' => [
                    'running' => $running,
                    'stopped' => $stopped,
                    'total' => ($running + $stopped),
                ],
                'health' => [
                    'healthy' => $healthy,
                    'unhealthy' => $unhealthy,
                    'unknown' => $unknownhealth
                ],
                'updates' => [
                    'updated' => $updated,
                    'outdated' => $outdated,
                    'unchecked' => (($running + $stopped) - ($updated + $outdated))
                ],
                'usage' => [
                    'disk' => byteConversion($size),
                    'cpu' => $cpu,
                    'memory' => $memory,
                    'network' => byteConversion($network)
                ],
                'networks' => $networks,
                'ports' => $ports
        ];
}