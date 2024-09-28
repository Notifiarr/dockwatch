<?php

/*
----------------------------------
 ------  Created: 072624   ------
 ------  Austin Best       ------
----------------------------------
*/

function getContainerStats()
{
    $stateFile  = getFile(STATE_FILE);
    $pullsFile  = getFile(PULL_FILE);
    $containers = [];

    foreach ($stateFile as $container) {
        $id         = $container['ID'];
        $name       = $container['Names'];
        $image      = $container['Image'];
        $imageSize  = $container['size'];
        $status     = $container['State'];
        $health     = $container['inspect'][0]['State']['Health']['Status'];
        $createdAt  = $container['CreatedAt'];

        $startedAt  = $container['inspect'][0]['State']['StartedAt'];
        $uptime     = (new DateTime())->diff(new DateTime($startedAt));
        $hours      = $uptime->h + ($uptime->days * 24);
        $minutes    = $uptime->i;
        $uptime     = sprintf('%02dh%02dm', $hours, $minutes);

        $networkMode   = !empty($container['Networks']) ? $container['Networks'] : 'container:' . explode(':', $container['inspect'][0]['Config']['Labels']['com.docker.compose.depends_on'])[0];

        $ports = [];
        $portList = explode(',', $container['Ports']);
        foreach ($portList as $port) {
            $protocol = $ip = $publicPort = $privatePort = $exposedPort = [];

            //-- GET PROTOCOL
            if (preg_match('/\/(\w+)/', $port, $matches)) {
                $protocol = $matches[1];
            }

            //-- GET IP
            if (preg_match('/^(\d+.\d+.\d+.\d+|::)\:/', trim($port), $matches)) {
                $ip = $matches[1];
            }

            //-- GET PRIVATE PORT
            if (preg_match('/->(\d+)\//', $port, $matches)) {
                $privatePort = $matches[1];
            }

            //-- GET PUBLIC PORT
            if (preg_match('/:(\d+)->|->(\d+)\//', $port, $matches)) {
                $publicPort = $matches[1];
            }

            //-- GET EXPOSED PORT
            if (preg_match('/:(\d+)\/|^(\d+)\//', $port, $matches)) {
                $exposedPort = $matches[1];
            }

            if (empty($privatePort) && empty($publicPort)) {
                continue;
            }

            $ports[]    = [
                            'ip'            => $ip,
                            'publicPort'    => !empty($publicPort) ? $publicPort : $exposedPort,
                            'privatePort'   => $privatePort,
                            'protocol'      => $protocol
                        ];
        }

        $dockwatch = [];
        foreach ($pullsFile as $hash => $pull) {
            if (md5($name) == $hash) {
                $dockwatch['pull'] = $pull['regctlDigest'] == $pull['imageDigest'] ? 'Up to date' : 'Outdated';
                $checked = new DateTime();
                $checked->setTimestamp($pull['checked']);
                $dockwatch['lastPull'] = $checked->format('Y-m-d H:i:s');

                break;
            }
        }

        $usage              = [];
        $usage['cpuPerc']   = $container['stats']['CPUPerc'];
        $usage['memPerc']   = $container['stats']['MemPerc'];
        $usage['memSize']   = $container['stats']['MemUsage'];
        $usage['blockIO']   = $container['stats']['BlockIO'];
        $usage['netIO']     = $container['stats']['NetIO'];

        $containers[]   = [
                            'id'            => $id,
                            'name'          => $name,
                            'image'         => $image,
                            'imageSize'     => $imageSize,
                            'status'        => $status,
                            'health'        => $health,
                            'createdAt'     => $createdAt,
                            'uptime'        => $uptime,
                            'networkMode'   => $networkMode,
                            'ports'         => $ports,
                            'dockwatch'     => $dockwatch,
                            'usage'         => $usage
                        ];
    }

    return $containers;
}

function getOverviewStats()
{
    $data = getContainerStats();
    $stats  = [
                'status'    => [
                                'running'   => 0,
                                'stopped'   => 0,
                                'total'     => 0
                            ],
                'health'    => [
                                'healthy'   => 0,
                                'unhealthy' => 0,
                                'unknown'   => 0
                            ],
                'updates'   => [
                                'uptodate'  => 0,
                                'outdated'  => 0,
                                'unchecked' => 0
                            ],
                'usage'     => [
                                'disk'      => 0,
                                'cpu'       => 0,
                                'memory'    => 0,
                                'netIO'     => 0
                            ],
                'network'   => [],
                'ports'     => []
            ];

    foreach ($data as $container) {
        // -- STATUS
        if ($container['status'] == 'running') {
            $stats['status']['running']++;
        }
        if ($container['status'] == 'exited') {
            $stats['status']['stopped']++;
        }
        $stats['status']['total'] = $stats['status']['running'] + $stats['status']['stopped'];

        // -- HEALTH
        if ($container['health'] == 'healthy') {
            $stats['health']['healthy']++;
        }
        if ($container['health'] == 'unhealthy') {
            $stats['health']['unhealthy']++;
        }
        if ($container['health'] == null) {
            $stats['health']['unknown']++;
        }

        // -- UPDATES
        if (!empty($container['dockwatch']) && $container['dockwatch']['pull'] == 'Up to date') {
            $stats['updates']['uptodate']++;
        }
        if (!empty($container['dockwatch']) && $container['dockwatch']['pull'] == 'Outdated') {
            $stats['updates']['outdated']++;
        }
        if (empty($container['dockwatch'])) {
            $stats['updates']['unchecked']++;
        }

        // -- USAGE
        if ($container['imageSize'] !== null) {
            $stats['usage']['disk'] += bytesFromString($container['imageSize']);
        }
        if ($container['usage']['cpuPerc'] !== null) {
            $stats['usage']['cpu'] += floatval(str_replace('%', '', $container['usage']['cpuPerc']));
        }
        if ($container['usage']['memPerc'] !== null) {
            $stats['usage']['memory'] += floatval(str_replace('%', '', $container['usage']['memPerc']));
        }
        if ($container['usage']['netIO'] !== null) {
            list($netUsed, $netAllowed) = explode(' / ', $container['usage']['netIO']);
            $stats['usage']['netIO'] += bytesFromString($netUsed);
        }

        // -- NETWORK
        if ($container['networkMode'] !== null && !$stats['network'][$container['networkMode']]) {
            $stats['network'][$container['networkMode']] = 0;
        }
        $stats['network'][$container['networkMode']]++;

        // -- PORTS
        if (!$stats['ports'][$container['name']]) {
            if (str_starts_with($container['networkMode'], 'container:')) {
                continue;
            }
            if (str_starts_with($container['networkMode'], 'host')) {
                continue;
            }

            $stats['ports'][$container['name']] = [];
        }
        foreach ($container['ports'] as $port) {
            if (!empty($port['publicPort']) && !in_array($port['publicPort'], $stats['ports'][$container['name']])) {
                $stats['ports'][$container['name']][] = $port['publicPort'];
            }
        }
    }

    return $stats;
}
