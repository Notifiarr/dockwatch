<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $ports = $networks = [];
    $running = $stopped = $memory = $cpu = $network = $size = $updated = $outdated = $healthy = $unhealthy = $unknownhealth = 0;

    foreach ($processList as $process) {
        if (strpos($process['Status'], 'healthy') !== false) {
            $healthy++;
        }
        if (strpos($process['Status'], 'unhealthy') !== false) {
            $unhealthy++;
        }
        if (strpos($process['Status'], 'health') === false) {
            $unknownhealth++;
        }

        if ($process['State'] == 'running') {
            $running++;
        } else {
            $stopped++;
        }

        //-- GET UPDATES
        if ($pullsFile) {
            foreach ($pullsFile as $hash => $pull) {
                if (md5($process['Names']) == $hash) {
                    if ($pull['image'] == $pull['container']) {
                        $updated++;
                    } else {
                        $outdated++;
                    }
                    break;
                }
            }
        }

        //-- GET USED NETWORKS
        if ($process['inspect'][0]['NetworkSettings']['Networks']) {
            $networkKeys = array_keys($process['inspect'][0]['NetworkSettings']['Networks']);
            foreach ($networkKeys as $networkKey) {
                $networks[$networkKey]++;
            }
        } else {
            $networks[$process['inspect'][0]['HostConfig']['NetworkMode']]++;
        }

        //-- GET USED PORTS
        if ($process['inspect'][0]['HostConfig']['PortBindings']) {
            foreach ($process['inspect'][0]['HostConfig']['PortBindings'] as $internalBind => $portBinds) {
                foreach ($portBinds as $portBind) {
                    if ($portBind['HostPort']) {
                        $ports[$process['Names']][] = $portBind['HostPort'];
                    }
                }
            }
        }

        //-- GET MEMORY UAGE
        $memory += floatval(str_replace('%', '', $process['stats']['MemPerc']));

        //-- GET CPU USAGE
        $cpu += floatval(str_replace('%', '', $process['stats']['CPUPerc']));

        //-- GET NETWORK USAGE
        list($netUsed, $netAllowed) = explode(' / ', $process['stats']['NetIO']);
        $network += bytesFromString($netUsed);
    }

    $cpu = $cpu > 0 ? number_format((($running + $stopped) * 100) / $cpu, 2) : 0;
    if (intval($settingsFile['global']['cpuAmount']) > 0) {
        $cpuActual = number_format(($cpu / intval($settingsFile['global']['cpuAmount'])), 2);
    }

    //-- GET THE SIZE
    foreach ($imageSizes as $imageSize) {
        $size += bytesFromString($imageSize['Size']);
    }

    ?>
    <div class="container-fluid pt-4 px-4">
        <div class="row">
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-3">
                <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
                    <h3>Status</h3>
                    Running: <?= $running ?><br>
                    Stopped: <?= $stopped ?><br>
                    Total: <?= ($running + $stopped) ?><br><br>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-3">
                <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
                    <h3>Health</h3>
                    Healthy: <?= $healthy ?><br>
                    Unhealthy: <?= $unhealthy ?><br>
                    Unknown: <?= $unknownhealth ?><br><br>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-3">
                <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
                    <h3>Updates</h3>
                    Updated: <?= $updated ?><br>
                    Outdated: <?= $outdated ?><br>
                    Unchecked: <?= (($running + $stopped) - ($updated + $outdated)) ?><br><br>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-3">
                <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
                    <h3>Usage</h3>
                    Disk:  <?= byteConversion($size) ?><br>
                    CPU: <?= $cpu ?>%<?= ($cpuActual ? ' (' . $cpuActual . '%)' : '') ?><br>
                    Memory: <?= $memory ?>%<br>
                    Network I/O: <?= byteConversion($network) ?>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-3">
                <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
                    <h3>Networks</h3>
                    <?php
                    $networkList = '';
                    foreach ($networks as $networkName => $networkCount) {
                        $networkList .= ($networkList ? '<br>' : '') . truncateMiddle($networkName, 30) . ': ' . $networkCount;
                    }
                    echo '<div style="max-height: 250px; overflow: auto;">' . $networkList . '</div>';
                    ?>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-3">
                <div class="bg-secondary rounded d-flex align-items-center justify-content-between p-4">
                    <h3>Ports</h3>
                    <?php
                    $portArray = [];
                    $portList = '';
                    if ($ports) {
                        foreach ($ports as $container => $containerPorts) {
                            foreach ($containerPorts as $containerPort) {
                                $portArray[$containerPort] = $container;
                            }
                        }
                        ksort($portArray);
                        
                        if ($portArray) {
                            $portList = '<div style="max-height: 250px; overflow: auto;">';

                            foreach ($portArray as $port => $container) {
                                $portList .= '<div class="row p-0 m-0">';
                                $portList .= '  <div class="col-sm-3 text-end">' . str_pad($port, 5, ' ', STR_PAD_LEFT) . '</div>';
                                $portList .= '  <div class="col-sm-1">&nbsp</div>';
                                $portList .= '  <div class="col-sm-7" title="' . $container . '">' . truncateMiddle($container, 14) . '</div>';
                                $portList .= '</div>';    
                            }

                            $portList .= '</div>';
                        }
                    }
                    echo $portList;
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    displayTimeTracking($loadTimes);
}