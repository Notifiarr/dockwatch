<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $dependencyFile = $docker->setContainerDependencies($processList);
    $ports = $networks = $graphs = [];
    $running = $stopped = $memory = $cpu = $network = $size = $updated = $outdated = $healthy = $unhealthy = $unknownhealth = 0;

    $overviewApiResult = apiRequest('stats/overview')['result']['result']; //-- Why is it like this?
    $containersApiResult = apiRequest('stats/containers')['result']['result']; //-- Same thing

    //-- HEALTH STATS
    $healthy = $overviewApiResult['health']['healthy'];
    $unhealthy = $overviewApiResult['health']['unhealthy'];
    $unknownhealth = $overviewApiResult['health']['unknown'];

    //-- CONTAINER STATES
    $running = $overviewApiResult['status']['running'];
    $stopped = $overviewApiResult['status']['stopped'];

    //-- UPDATE STATS
    $updated = $overviewApiResult['updates']['uptodate'];
    $outdated = $overviewApiResult['updates']['outdated'];
    $unchecked = $overviewApiResult['updates']['unchecked'];

    //-- USAGE
    $size = $overviewApiResult['usage']['disk'];
    $memory = $overviewApiResult['usage']['memory'];
    $cpu = $overviewApiResult['usage']['cpu'];
    $network = $overviewApiResult['usage']['netIO'];

    //-- NETWORKS
    $networks = $overviewApiResult['network'];
    $ports = $overviewApiResult['ports'];

    //-- CONTAINER GRAPHS
    foreach ($containersApiResult as $result) {
        //-- CPU USAGE
        $graphs['utilization']['cpu']['total']['percent'] = 100;
        $graphs['utilization']['cpu']['containers'][$result['name']] = str_replace('%', '', $result['usage']['cpuPerc']);

        //-- MEM USAGE
        list($memUsed, $memTotal) = explode('/', $result['usage']['memSize']);
        $graphs['utilization']['memory']['total']['size'] = trim(byteConversion(binaryBytesFromString($memTotal), 'MiB'));
        $graphs['utilization']['memory']['total']['percent'] = 100;
        $graphs['utilization']['memory']['containers'][$result['name']]['percent'] = str_replace('%', '', $result['usage']['memPerc']);
        $graphs['utilization']['memory']['containers'][$result['name']]['size'] = trim(byteConversion(binaryBytesFromString($memUsed), 'MiB'));
    }

    if (intval($settingsTable['cpuAmount']) > 0) {
        $cpuActual = number_format(($cpu / intval($settingsTable['cpuAmount'])), 2);
    }

    ?>
    <div class="row mb-2">
        <div class="col-sm-6"><?= APP_NAME ?> at a glance</div>
        <div class="col-sm-6 d-flex justify-content-end">
            <div class="form-check form-switch">
                <label class="form-check-label" for="overviewDetailed">Detailed</label>
                <input class="form-check-input bg-primary" type="checkbox" role="switch" id="overviewDetailed" onchange="toggleOverviewView()" <?= $settingsTable['overviewLayout'] == UI::OVERVIEW_DETAILED ? 'checked' : '' ?>>
            </div>
        </div>
    </div>
    <?php
    if (!$settingsTable['overviewLayout'] || $settingsTable['overviewLayout'] == UI::OVERVIEW_SIMPLE) {
    ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-2" style="cursor:pointer;" onclick="initPage('containers')">
                <div class="row bg-secondary rounded p-4 me-2">
                    <div class="col-sm-12 col-lg-4">
                        <h3>Status</h3>
                    </div>
                    <div class="col-sm-12 col-lg-8 text-end">
                        Running: <?= $running ?><br>
                        Stopped: <?= $stopped ?><br>
                        Total: <?= $running + $stopped ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-2" style="cursor:pointer;" onclick="initPage('containers')">
                <div class="row bg-secondary rounded p-4 me-2">
                    <div class="col-sm-12 col-lg-4">
                        <h3>Health</h3>
                    </div>
                    <div class="col-sm-12 col-lg-8 text-end">
                        Healthy: <?= $healthy ?><br>
                        Unhealthy: <?= $unhealthy ?><br>
                        Unknown: <?= $unknownhealth ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-2" style="cursor:pointer;" onclick="openUpdateOptions()">
                <div class="row bg-secondary rounded p-4 me-2">
                    <div class="col-sm-12 col-lg-4">
                        <h3>Updates</h3>
                    </div>
                    <div class="col-sm-12 col-lg-8 text-end">
                        Up to date: <?= $updated ?><br>
                        Outdated: <?= $outdated ?><br>
                        Unchecked: <?= ($running + $stopped) - ($updated + $outdated) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-2">
                <div class="row bg-secondary rounded p-4 me-2">
                    <div class="col-sm-12 col-lg-4">
                        <h3>Usage</h3>
                    </div>
                    <div class="col-sm-12 col-lg-8 text-end">
                        Disk:  <?= byteConversion($size) ?><br>
                        CPU: <span title="Docker reported CPU"><?= $cpu ?>%</span><?= $cpuActual ? ' <span title="Calculated CPU">(' . $cpuActual . '%)</span>' : '' ?><br>
                        Memory: <?= $memory ?>%<br>
                        Network I/O: <?= byteConversion($network) ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-2" style="cursor:pointer;" onclick="initPage('networks')">
                <div class="row bg-secondary rounded p-4 me-2">
                    <div class="col-sm-12 col-lg-4">
                        <h3>Network</h3>
                    </div>
                    <div class="col-sm-12 col-lg-8 text-end">
                        <?php
                        $networkList = '';
                        foreach ($networks as $networkName => $networkCount) {
                            $networkList .= ($networkList ? '<br>' : '') . truncateMiddle($networkName, 30) . ': ' . $networkCount;
                        }
                        echo '<div style="max-height: 250px; overflow: auto;">' . $networkList . '</div>';
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6 col-xl-4 mt-2">
                <div class="row bg-secondary rounded p-4 me-2">
                    <div class="col-sm-12 col-lg-2">
                        <h3>Ports</h3>
                    </div>
                    <div class="col-sm-12 col-lg-10">
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
                            $portArray = formatPortRanges($portArray);

                            if ($portArray) {
                                $portList = '<div style="max-height: 250px; overflow: auto;">';

                                foreach ($portArray as $port => $container) {
                                    $portList .= '<div class="row flex-nowrap p-0 m-0">';
                                    $portList .= '  <div class="col text-end">' . $port . '</div>';
                                    $portList .= '  <div class="col text-end" title="' . $container . '">' . truncateMiddle($container, 14) . '</div>';
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
    </div>
    <?php } elseif ($settingsTable['overviewLayout'] == UI::OVERVIEW_DETAILED) { ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="row bg-secondary rounded p-4">
                <div class="col-12 col-lg-4" style="cursor:pointer;" onclick="initPage('containers')">
                    <div class="row">
                        <div class="col-12 mb-2">
                            <span class="h5"><i class="fas fa-box-open"></i> Status</span>
                        </div>
                        <div class="col-12 mb-2">
                            <div class="row">
                                <div class="col-4">
                                    <span class="text-success">Running</span><br>
                                    <?= $running ?>
                                </div>
                                <div class="col-4">
                                    <span class="text-danger">Stopped</span><br>
                                    <?= $stopped ?>
                                </div>
                                <div class="col-4">
                                    Total<br>
                                    <?= $running + $stopped ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4" style="cursor:pointer;" onclick="initPage('containers')">
                    <div class="row">
                        <div class="col-12 mb-2">
                            <span class="h5"><i class="fas fa-heartbeat"></i> Health</span>
                        </div>
                        <div class="col-12 mb-2">
                            <div class="row">
                                <div class="col-4">
                                    <span class="text-success">Healthy</span><br>
                                    <?= $healthy ?>
                                </div>
                                <div class="col-4">
                                    <span class="text-danger">Unhealthy</span><br>
                                    <?= $unhealthy ?>
                                </div>
                                <div class="col-4">
                                    <span class="text-warning">Unknown</span><br>
                                    <?= $unknownhealth ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4" style="cursor:pointer;" onclick="openUpdateOptions()">
                    <div class="row">
                        <div class="col-12 mb-2">
                            <span class="h5"><i class="fas fa-database"></i> Updates</span>
                        </div>
                        <div class="col-12 mb-2">
                            <div class="row">
                                <div class="col-4">
                                    <span class="text-success">Updated</span><br>
                                    <?= $updated ?>
                                </div>
                                <div class="col-4">
                                    <span class="text-warning">Outdated</span><br>
                                    <?= $outdated ?>
                                </div>
                                <div class="col-4">
                                    Unchecked<br>
                                    <?= ($running + $stopped) - ($updated + $outdated) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-2">
        <div class="d-flex flex-wrap flex-lg-nowrap gap-sm-2 gap-lg-4 gap-2" style="justify-content: center;">
            <div class="bg-secondary rounded px-2 w-100">
                <div class="d-flex flex-row mt-2">
                    <p class="text-primary" style="font-size: 18px;">Disk Usage</p>
                    <i class="fas fa-hdd ms-auto p-2"></i>
                </div>
                <p style="font-size: 20px;"><?= byteConversion($size) ?></p>
            </div>
            <div class="bg-secondary rounded px-2 w-100">
                <div class="d-flex flex-row mt-2">
                    <p class="text-primary" style="font-size: 18px;">Network I/O</p>
                    <i class="fas fa-wifi ms-auto p-2"></i>
                </div>
                <p style="font-size: 20px;"><?= byteConversion($network) ?></p>
            </div>
            <div class="bg-secondary rounded px-2 w-100">
                <div class="d-flex flex-row mt-2">
                    <p class="text-primary" style="font-size: 18px;">CPU Usage</p>
                    <i class="fas fa-microchip ms-auto p-2"></i>
                </div>
                <p style="font-size: 20px;"><span title="Docker reported CPU"><?= $cpu ?>%</span><?= $cpuActual ? ' <span title="Calculated CPU">(' . $cpuActual . '%)</span>' : '' ?></p>
            </div>
            <div class="bg-secondary rounded px-2 w-100">
                <div class="d-flex flex-row mt-2">
                    <p class="text-primary" style="font-size: 18px;">Memory Usage</p>
                    <i class="fas fa-memory ms-auto p-2"></i>
                </div>
                <p style="font-size: 20px;"><?= $memory ?>%</p>
            </div>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-12 col-lg-6">
            <div class="bg-secondary rounded p-2" style="cursor:pointer;" onclick="initPage('networks')">
                <div class="table-responsive-sm" style="height:25vh; max-height:25vh; overflow:auto;">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th class="w-50">Network</th>
                                <th>Containers</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($networks as $networkName => $networkCount) { ?>
                            <tr>
                                <td><?= $networkName ?></td>
                                <td><?= $networkCount?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-lg-6 mt-lg-0 mt-2">
            <div class="bg-secondary rounded p-2">
                <div class="table-responsive-sm" style="height:25vh; max-height:25vh; overflow:auto;">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th class="w-50">Container</th>
                                <th>Port</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($ports) {
                                foreach ($ports as $container => $containerPorts) {
                                    foreach ($containerPorts as $containerPort) {
                                        $portArray[$containerPort] = $container;
                                    }
                                }
                                ksort($portArray);
                                $portArray = formatPortRanges($portArray);

                                if ($portArray) {
                                    foreach ($portArray as $port => $container) {
                                        ?>
                                        <tr>
                                            <td><?= $container ?></td>
                                            <td><?= $port?></td>
                                        </tr>
                                        <?php
                                    }
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-2 mt-lg-2">
        <div class="col-lg-6 mt-lg-0 mt-2">
            <div class="bg-secondary rounded px-2 w-100" style="height:auto; max-height:50vh; overflow:auto;">
                <div id="chart-cpu-container" class="bg-secondary rounded p-2"></div>
            </div>
        </div>
        <div class="col-lg-6 mt-lg-0 mt-2">
            <div class="bg-secondary rounded px-2 w-100" style="height:auto; max-height:50vh; overflow:auto;">
                <div id="chart-memoryPercent-container" class="bg-secondary rounded p-2"></div>
            </div>
        </div>
        <div class="col-lg-12 mt-2">
            <div class="bg-secondary rounded px-2 w-100 h-2">
                <div id="chart-memorySize-container" class="bg-secondary rounded"></div>
            </div>
        </div>
    </div>
    </div>
    <?php
    }

    displayTimeTracking($loadTimes);

    //-- CPU
    foreach ($graphs['utilization']['cpu']['containers'] as $containerName => $containerPercent) {
        $utilizationCPULabels[] = $containerName;
        $utilizationCPUData[]   = $containerPercent;
    }

    //-- MEMORY PERCENT
    $utilizationMemoryPercentLabels = $utilizationMemoryPercentData = [];
    foreach ($graphs['utilization']['memory']['containers'] as $containerName => $graphDetails) {
        $utilizationMemoryPercentLabels[]   = $containerName;
        $utilizationMemoryPercentData[]     = $graphDetails['percent'];
    }

    //-- MEMORY SIZE
    $utilizationMemorySizeLabels = $utilizationMemorySizeData = $utilizationmemorySizeColors = [];
    foreach ($graphs['utilization']['memory']['containers'] as $containerName => $graphDetails) {
        $g = str_contains($graphDetails['size'], 'GiB') ? true : false;
        $utilizationMemorySizeLabels[]  = $containerName;
        $utilizationMemorySizeData[]    = preg_replace('/[^0-9.]/', '', $graphDetails['size']) * ($g ? 1024 : 1);
        $utilizationmemorySizeColors[]  = '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }

    ?>
    <script>
        GRAPH_UTILIZATION_CPU_LABELS            = '<?= json_encode($utilizationCPULabels) ?>';
        GRAPH_UTILIZATION_CPU_DATA              = '<?= json_encode($utilizationCPUData) ?>';
        GRAPH_UTILIZATION_MEMORY_PERCENT_LABELS = '<?= json_encode($utilizationMemoryPercentLabels) ?>';
        GRAPH_UTILIZATION_MEMORY_PERCENT_DATA   = '<?= json_encode($utilizationMemoryPercentData) ?>';
        GRAPH_UTILIZATION_MEMORY_SIZE_LABELS    = '<?= json_encode($utilizationMemorySizeLabels) ?>';
        GRAPH_UTILIZATION_MEMORY_SIZE_DATA      = '<?= json_encode($utilizationMemorySizeData) ?>';
        GRAPH_UTILIZATION_MEMORY_SIZE_COLORS    = '<?= json_encode($utilizationmemorySizeColors) ?>';
    </script>
    <?php
}

if ($_POST['m'] == 'toggleOverviewView') {
    $layout = $_POST['enabled'] ? UI::OVERVIEW_DETAILED : UI::OVERVIEW_SIMPLE;
    $database->setSetting('overviewLayout', $layout);
}
