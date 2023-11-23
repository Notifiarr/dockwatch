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
    $running = $stopped = $memory = $cpu = $network = $size = $updated = $outdated = 0;

    foreach ($processList as $process) {
        if ($process['State'] == 'running') {
            $running++;
        } else {
            $stopped++;
        }

        //-- GET UPDATES
        if ($pulls) {
            $pulls = is_array($pulls) ? $pulls : json_decode($pulls, true);
            foreach ($pulls as $hash => $pull) {
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
        $networks[$process['inspect'][0]['HostConfig']['NetworkMode']]++;

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

        //-- GET THE SIZE
        list($idk, $virtual) = explode('virtual ', $process['Size']);
        $size += bytesFromString(str_replace(')', '', $virtual));

        //-- GET MEMORY UAGE
        $memory += floatval(str_replace('%', '', $process['stats']['MemPerc']));

        //-- GET CPU USAGE
        $cpu += floatval(str_replace('%', '', $process['stats']['CPUPerc']));

        //-- GET NETWORK USAGE
        list($netUsed, $netAllowed) = explode(' / ', $process['stats']['NetIO']);
        $network += bytesFromString($netUsed);
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
                    CPU: <?= number_format((($running + $stopped) * 100) / $cpu, 2) ?>%<br>
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
                        $networkList .= ($networkList ? '<br>' : '') . $networkName . ': ' . $networkCount;
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
                            foreach ($portArray as $port => $container) {
                                $portList .= ($portList ? '<br>' : '') . $port . ' :: ' . $container;
                            }
                            $portList = '<div style="max-height: 250px; overflow: auto;">' . $portList . '</div>';
                        }
                    }
                    echo $portList;
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}