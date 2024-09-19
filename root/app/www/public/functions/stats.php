<?php

/*
----------------------------------
 ------  Created: 072624   ------
 ------  Austin Best       ------
----------------------------------
*/

function getContainersList() {
    global $docker;

    $stateFile = getFile(STATE_FILE);
    $pullsFile = getFile(PULL_FILE);

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
                $protocol       = $matches[1];
            }

            //-- GET IP
            if (preg_match('/^(\d+.\d+.\d+.\d+|::)\:/', trim($port), $matches)) {
                $ip             = $matches[1];
            }

            //-- GET PRIVATE PORT
            if (preg_match('/->(\d+)\//', $port, $matches)) {
                $privatePort     = $matches[1];
            }

            //-- GET PUBLIC PORT
            if (preg_match('/:(\d+)->|->(\d+)\//', $port, $matches)) {
                $publicPort    = $matches[1];
            }

            //-- GET EXPOSED PORT
            if (preg_match('/:(\d+)\/|^(\d+)\//', $port, $matches)) {
                $exposedPort    = $matches[1];
            }

            if (empty($privatePort) && empty($publicPort)) {
                continue;
            }

            $ports[]    = [
                            'ip'            => $ip,
                            'publicPort'    => !empty($publicPort) ? $publicPort : $exposedPort,
                            'privatePort'   => $privatePort,
                            'protocol'      => $protocol,
            ];
        }

        $dockwatch = [];
        foreach ($pullsFile as $hash => $pull) {
            if (md5($name) == $hash) {
                if ($pull['regctlDigest'] == $pull['imageDigest']) {
                    $dockwatch['pull'] = 'Up to date';
                } else {
                    $dockwatch['pull'] = 'Outdated';
                }

                $checked               = (new DateTime());
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

        $containers[] = [
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