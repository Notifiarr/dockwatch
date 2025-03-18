<?php

/*
----------------------------------
 ------  Created: 072624   ------
 ------  Austin Best       ------
----------------------------------
*/

function getContainerStats($servers = [])
{
    $processList                = getFile(STATE_FILE);
    $pullsFile                  = getFile(PULL_FILE);
    $containers                 = [];

    if (!empty($servers)) {
        foreach ($servers as $server) {
            if (strtoupper(APP_NAME) == strtoupper($server['name'])) {
                continue;
            }

            $processListRemote  = apiRequestRemote('file-state', [], [], $server)['result'] ?: [];
            $pullsFileRemote    = apiRequestRemote('file-pull', [], [], $server)['result'] ?: [];

            //-- ADD SERVER IDENTIFIER TO CONTAINERS
            foreach ($processListRemote as &$container) {
                $container['server'] = $server['name'];
            }

            //-- MERGE ARRAYS
            $processList = array_merge($processList, $processListRemote);
            $pullsFile = array_merge($pullsFile, $pullsFileRemote);
        }
    }

    foreach ($processList as $container) {
        $id         = $container['ID'];
        $name       = $container['Names'];
        $image      = $container['Image'];
        $imageSize  = $container['size'];
        $status     = $container['State'];
        $health     = $container['inspect'][0]['State']['Health']['Status'];
        $createdAt  = $container['CreatedAt'];
        $server     = $container['server'] ?: 'local';

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
                            'usage'         => $usage,
                            'server'        => $server
                        ];
    }

    return $containers;
}

function initializeStats() {
    return [
        'status' => [
            'running' => 0,
            'stopped' => 0,
            'total' => 0
        ],
        'health' => [
            'healthy' => 0,
            'unhealthy' => 0,
            'unknown' => 0
        ],
        'updates' => [
            'uptodate' => 0,
            'outdated' => 0,
            'unchecked' => 0
        ],
        'usage' => [
            'disk' => 0,
            'cpu' => 0,
            'memory' => 0,
            'netIO' => 0
        ],
        'network' => [],
        'ports' => []
    ];
}

function updateContainerStats(&$stats, $container, $serverKey = '')
{
    $target = $serverKey ? ['servers', $serverKey] : [];

    //-- STATUS
    if ($serverKey) {
        $stats['servers'][$serverKey]['status']['running'] += ($container['status'] == 'running' ? 1 : 0);
        $stats['servers'][$serverKey]['status']['stopped'] += ($container['status'] == 'exited' ? 1 : 0);
        $stats['servers'][$serverKey]['status']['total'] = $stats['servers'][$serverKey]['status']['running'] + $stats['servers'][$serverKey]['status']['stopped'];
    } else {
        $stats['status']['running'] += ($container['status'] == 'running' ? 1 : 0);
        $stats['status']['stopped'] += ($container['status'] == 'exited' ? 1 : 0);
        $stats['status']['total'] = $stats['status']['running'] + $stats['status']['stopped'];
    }

    //-- HEALTH
    if ($container['status'] == 'running') {
        if ($serverKey) {
            $stats['servers'][$serverKey]['health']['healthy'] += ($container['health'] == 'healthy' ? 1 : 0);
            $stats['servers'][$serverKey]['health']['unhealthy'] += ($container['health'] == 'unhealthy' ? 1 : 0);
            $stats['servers'][$serverKey]['health']['unknown'] += ($container['health'] === null ? 1 : 0);
        } else {
            $stats['health']['healthy'] += ($container['health'] == 'healthy' ? 1 : 0);
            $stats['health']['unhealthy'] += ($container['health'] == 'unhealthy' ? 1 : 0);
            $stats['health']['unknown'] += ($container['health'] === null ? 1 : 0);
        }
    }

    //-- UPDATES
    if ($serverKey) {
        if (!empty($container['dockwatch'])) {
            $stats['servers'][$serverKey]['updates']['uptodate'] += ($container['dockwatch']['pull'] == 'Up to date' ? 1 : 0);
            $stats['servers'][$serverKey]['updates']['outdated'] += ($container['dockwatch']['pull'] == 'Outdated' ? 1 : 0);
        } else {
            $stats['servers'][$serverKey]['updates']['unchecked']++;
        }
    } else {
        if (!empty($container['dockwatch'])) {
            $stats['updates']['uptodate'] += ($container['dockwatch']['pull'] == 'Up to date' ? 1 : 0);
            $stats['updates']['outdated'] += ($container['dockwatch']['pull'] == 'Outdated' ? 1 : 0);
        } else {
            $stats['updates']['unchecked']++;
        }
    }

    //-- USAGE
    if ($serverKey) {
        $stats['servers'][$serverKey]['usage']['disk'] += ($container['imageSize'] !== null ? bytesFromString($container['imageSize']) : 0);
        $stats['servers'][$serverKey]['usage']['cpu'] += ($container['usage']['cpuPerc'] !== null ? floatval(str_replace('%', '', $container['usage']['cpuPerc'])) : 0);
        $stats['servers'][$serverKey]['usage']['memory'] += ($container['usage']['memPerc'] !== null ? floatval(str_replace('%', '', $container['usage']['memPerc'])) : 0);

        if ($container['usage']['netIO'] !== null && !str_starts_with($container['networkMode'], 'container:')) {
            list($netUsed, $netAllowed) = explode(' / ', $container['usage']['netIO']);
            $stats['servers'][$serverKey]['usage']['netIO'] += bytesFromString($netUsed);
        }
    } else {
        $stats['usage']['disk'] += ($container['imageSize'] !== null ? bytesFromString($container['imageSize']) : 0);
        $stats['usage']['cpu'] += ($container['usage']['cpuPerc'] !== null ? floatval(str_replace('%', '', $container['usage']['cpuPerc'])) : 0);
        $stats['usage']['memory'] += ($container['usage']['memPerc'] !== null ? floatval(str_replace('%', '', $container['usage']['memPerc'])) : 0);

        if ($container['usage']['netIO'] !== null && !str_starts_with($container['networkMode'], 'container:')) {
            list($netUsed, $netAllowed) = explode(' / ', $container['usage']['netIO']);
            $stats['usage']['netIO'] += bytesFromString($netUsed);
        }
    }

    //-- NETWORK
    $networkKey = $container['networkMode'];
    if ($networkKey !== null) {
        if ($serverKey) {
            $stats['servers'][$serverKey]['network'][$networkKey] = ($stats['servers'][$serverKey]['network'][$networkKey] ?? 0) + 1;
        } else {
            $stats['network'][$networkKey] = ($stats['network'][$networkKey] ?? 0) + 1;
        }
    }

    //-- PORTS
    $containerKey = $container['name'];
    if (!str_starts_with($container['networkMode'], 'container:') &&
        !str_starts_with($container['networkMode'], 'host')) {
        if ($serverKey) {
            $stats['servers'][$serverKey]['ports'][$containerKey] = $stats['servers'][$serverKey]['ports'][$containerKey] ?? [];
            foreach ($container['ports'] as $port) {
                if (!empty($port['publicPort']) && !in_array($port['publicPort'], $stats['servers'][$serverKey]['ports'][$containerKey])) {
                    $stats['servers'][$serverKey]['ports'][$containerKey][] = $port['publicPort'];
                }
            }
        } else {
            $stats['ports'][$containerKey] = $stats['ports'][$containerKey] ?? [];
            foreach ($container['ports'] as $port) {
                if (!empty($port['publicPort']) && !in_array($port['publicPort'], $stats['ports'][$containerKey])) {
                    $stats['ports'][$containerKey][] = $port['publicPort'];
                }
            }
        }
    }
}

function getOverviewStats($servers = [])
{
    $data = getContainerStats($servers);
    $stats = initializeStats();

    foreach ($data as $container) {
        $isRemote = $container['server'] !== 'local';
        if ($isRemote) {
            if (!isset($stats['servers'][$container['server']])) {
                $stats['servers'][$container['server']] = initializeStats();
            }
            updateContainerStats($stats, $container, $container['server']);
        } else {
            updateContainerStats($stats, $container);
        }
    }

    return $stats;
}

function getUsageMetrics($servers = [])
{
    $metricsFile = getFile(METRICS_FILE);
    $metrics = $metricsFile ?: ['history' => ['disk' => [], 'netIO' => []]];
    $allMetrics = ['local' => $metrics];
    $retentions = ['local' => apiRequestLocal('database-getSettings')['usageMetricsRetention']];

    if (!empty($servers)) {
        foreach ($servers as $server) {
            if (strtoupper(APP_NAME) == strtoupper($server['name'])) {
                continue;
            }

            $remoteMetrics = apiRequestRemote('file-metrics', [], [], $server)['result'] ?: ['history' => ['disk' => [], 'netIO' => []]];
            $remoteSettings = apiRequestRemote('database-getSettings', [], [], $server)['result'] ?: ['usageMetricsRetention' => 0];

            $allMetrics[$server['name']] = $remoteMetrics;
            $retentions[$server['name']] = $remoteSettings['usageMetricsRetention'];
        }
    }

    $summary = [];

    foreach ($allMetrics as $serverName => $serverMetrics) {
        if ($serverName === 'local') {
            $summary = calculateUsageMetrics($serverMetrics, $retentions[$serverName]);
        } else {
            $summary['servers'][$serverName] = calculateUsageMetrics($serverMetrics, $retentions[$serverName]);
        }
    }

    return $summary;
}

function calculateUsageMetrics($metrics, $retention = 1)
{
    if ($retention == 0) {
        return null;
    }

    $summary = [];

    foreach (['disk', 'netIO'] as $key) {
        //-- CALCULATE CHANGE
        if (count($metrics['history'][$key]) > 1) {
            $latest = end($metrics['history'][$key]);
            $oldest = reset($metrics['history'][$key]);
            $change = $latest['value'] - $oldest['value'];

            $sign = $change > 0 ? '+' : '-';

            $summary[$key] = $sign . byteConversion(binaryBytesFromString($change)) . ($change == 0 ? ' B' : '') . ' over last ' .
            ($retention == 1 ? 'day' : ($retention == 2 ? 'week' : 'month'));
        } else {
            $summary[$key] = '+0 B over last ' .
            ($retention == 1 ? 'day' : ($retention == 2 ? 'week' : 'month'));
        }
    }

    return $summary;
}

function cacheUsageMetrics($retention = 0)
{
    if ($retention == 0) {
        return null;
    }

    $currentUsage   = getOverviewStats()['usage'];
    $metricsFile    = getFile(METRICS_FILE);
    $metrics        = $metricsFile ?: ['history' => ['disk' => [], 'netIO' => []]];
    $timestamp      = time();

    if ($currentUsage['disk'] == 0 || $currentUsage['netIO'] == 0) {
        return null;
    }

    $timeLimit = match ($retention) {
        1 => strtotime('-1 day'),
        2 => strtotime('-7 days'),
        3 => strtotime('-30 days'),
        default => null
    };

    foreach (['disk', 'netIO'] as $key) {
        //-- ENSURE IT EXISTS
        if (!isset($metrics['history'][$key])) {
            $metrics['history'][$key] = [];
        }

        //-- CHECK IF PREVIOUS VALUE EXISTS AND IS LARGER THAN CURRENT
        if ($key === 'netIO' && !empty($metrics['history'][$key])) {
            $lastEntry = end($metrics['history'][$key]);
            if ($lastEntry['value'] > $currentUsage[$key]) {
                //-- ADD CURRENT VALUE TO PREVIOUS ONE
                $currentUsage[$key] = $lastEntry['value'];
            }
        }

        //-- APPEND ENTRY
        $metrics['history'][$key][] = ['timestamp' => $timestamp, 'value' => $currentUsage[$key]];

        //-- PRUNE OLD ENTRIES
        if ($timeLimit) {
            $metrics['history'][$key] = array_values(array_filter(
                $metrics['history'][$key],
                fn($entry) => isset($entry['timestamp']) && $entry['timestamp'] >= $timeLimit
            ));
        }
    }

    setFile(METRICS_FILE, json_encode($metrics, JSON_PRETTY_PRINT));
    return calculateUsageMetrics($metrics);
}

