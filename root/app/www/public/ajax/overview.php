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

    $overviewApiResult      = apiRequest('stats/overview')['result']['result'];
    $containersApiResult    = apiRequest('stats/containers')['result']['result'];

    //-- HEALTH STATS
    $healthy        = $overviewApiResult['health']['healthy'];
    $unhealthy      = $overviewApiResult['health']['unhealthy'];
    $unknownhealth  = $overviewApiResult['health']['unknown'];

    //-- CONTAINER STATES
    $running        = $overviewApiResult['status']['running'];
    $stopped        = $overviewApiResult['status']['stopped'];

    //-- UPDATE STATS
    $updated        = $overviewApiResult['updates']['uptodate'];
    $outdated       = $overviewApiResult['updates']['outdated'];
    $unchecked      = $overviewApiResult['updates']['unchecked'];

    //-- USAGE
    $size           = $overviewApiResult['usage']['disk'];
    $memory         = $overviewApiResult['usage']['memory'];
    $cpu            = $overviewApiResult['usage']['cpu'];
    $network        = $overviewApiResult['usage']['netIO'];

    //-- NETWORKS
    $networks       = $overviewApiResult['network'];
    $ports          = $overviewApiResult['ports'];

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
    <ol class="breadcrumb rounded p-1 ps-2">
        <li class="breadcrumb-item active" aria-current="page"><?= $_SESSION['activeServerName'] ?></li>
    </ol>
    <div class="row">
        <div class="d-flex flex-wrap flex-lg-nowrap" style="justify-content:center;">
            <div class="bg-secondary p-2 w-100 rounded-start" style="cursor:pointer;" onclick="initPage('containers')">
                <div class="d-flex flex-row">
                    <span class="h5"><i class="fas fa-box-open"></i> Status</span>
                </div>
                <div class="d-flex flex-row">
                    <div class="p-2 flex-fill bd-highlight">
                        <span class="text-success">Running</span><br>
                        <?= $running ?>
                    </div>
                    <div class="p-2 flex-fill bd-highlight">
                        <span class="text-danger">Stopped</span><br>
                        <?= $stopped ?>
                    </div>
                    <div class="p-2 flex-fill bd-highlight">
                        Total<br>
                        <?= $running + $stopped ?>
                    </div>
                </div>
            </div>
            <div class="bg-secondary p-2 w-100" style="cursor:pointer;" onclick="initPage('containers')">
                <div class="d-flex flex-row">
                    <span class="h5"><i class="fas fa-heartbeat"></i> Health</span>
                </div>
                <div class="d-flex flex-row">
                    <div class="p-2 flex-fill bd-highlight">
                        <span class="text-success">Healthy</span><br>
                        <?= $healthy ?>
                    </div>
                    <div class="p-2 flex-fill bd-highlight">
                        <span class="text-danger">Unhealthy</span><br>
                        <?= $unhealthy ?>
                    </div>
                    <div class="p-2 flex-fill bd-highlight">
                        <span class="text-warning">Unknown</span><br>
                        <?= $unknownhealth ?>
                    </div>
                </div>
            </div>
            <div class="bg-secondary p-2 w-100 rounded-end" style="cursor:pointer;" onclick="openUpdateOptions()">
                <div class="d-flex flex-row">
                    <span class="h5"><i class="fas fa-database"></i> Updates</span>
                </div>
                <div class="d-flex flex-row">
                    <div class="p-2 flex-fill bd-highlight">
                        <span class="text-success">Updated</span><br>
                        <?= $updated ?>
                    </div>
                    <div class="p-2 flex-fill bd-highlight">
                        <span class="text-warning">Outdated</span><br>
                        <?= $outdated ?>
                    </div>
                    <div class="p-2 flex-fill bd-highlight">
                        Unchecked<br>
                        <?= $unchecked ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-2">
        <div class="d-flex flex-wrap flex-lg-nowrap gap-sm-2" style="justify-content:center;">
            <div class="bg-secondary rounded px-2 w-100">
                <div class="d-flex flex-row mt-2">
                    <p class="text-info" style="font-size: 18px;">Disk Usage</p>
                    <i class="fas fa-hdd ms-auto p-2"></i>
                </div>
                <p style="font-size: 20px;"><?= byteConversion($size) ?></p>
            </div>
            <div class="bg-secondary rounded px-2 w-100">
                <div class="d-flex flex-row mt-2">
                    <p class="text-info" style="font-size: 18px;">Network I/O</p>
                    <i class="fas fa-wifi ms-auto p-2"></i>
                </div>
                <p style="font-size: 20px;"><?= byteConversion($network) ?></p>
            </div>
            <div class="bg-secondary rounded px-2 w-100">
                <div class="d-flex flex-row mt-2">
                    <p class="text-info" style="font-size: 18px;">CPU Usage</p>
                    <i class="fas fa-microchip ms-auto p-2"></i>
                </div>
                <p style="font-size: 20px;"><span title="Docker reported CPU"><?= $cpu ?>%</span><?= $cpuActual ? ' <span title="Calculated CPU">(' . $cpuActual . '%)</span>' : '' ?></p>
            </div>
            <div class="bg-secondary rounded px-2 w-100">
                <div class="d-flex flex-row mt-2">
                    <p class="text-info" style="font-size: 18px;">Memory Usage</p>
                    <i class="fas fa-memory ms-auto p-2"></i>
                </div>
                <p style="font-size: 20px;"><?= $memory ?>%</p>
            </div>
        </div>
    </div>
    <div class="row g-2 mt-2">
        <div class="col-sm-12 col-lg-6 mt-sm-0">
            <div class="bg-secondary rounded p-2" style="cursor:pointer;" onclick="initPage('networks')">
                <div class="table-responsive bg-secondary" style="height:25vh; max-height:25vh; overflow:auto;">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="w-50 rounded-top-left-1 bg-primary ps-3">Network</th>
                                <th class="w-50 rounded-top-right-1 bg-primary ps-3">Containers</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($networks as $networkName => $networkCount) { ?>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary"><?= $networkName ?></td>
                                <td class="bg-secondary"><?= $networkCount?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-lg-6 mt-sm-0">
            <div class="bg-secondary rounded p-2">
                <div class="table-responsive-sm" style="height:25vh; max-height:25vh; overflow:auto;">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="w-50 rounded-top-left-1 bg-primary ps-3">Container</th>
                                <th class="w-50 rounded-top-right-1 bg-primary ps-3">Port</th>
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
                                        <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                            <td class="bg-secondary"><?= $container ?></td>
                                            <td class="bg-secondary"><?= $port?></td>
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
    <div class="row g-2 mt-1">
        <div class="col-lg-6 mt-sm-1">
            <div class="bg-secondary rounded px-2 w-100" style="height:auto; max-height:50vh; overflow:auto;">
                <div id="chart-cpu-container" class="bg-secondary rounded p-2"></div>
            </div>
        </div>
        <div class="col-lg-6 mt-sm-1">
            <div class="bg-secondary rounded px-2 w-100" style="height:auto; max-height:50vh; overflow:auto;">
                <div id="chart-memoryPercent-container" class="bg-secondary rounded p-2"></div>
            </div>
        </div>
        <div class="col-lg-6 mt-sm-1"></div>
        <div class="col-lg-6 mt-sm-2">
            <div class="bg-secondary rounded px-2 w-100 h-2 d-flex flex-column" style="justify-content: center;">
                <div class="text-center text-light mt-2">Memory Usage - MiB</div>
                <div class="d-flex flex-lg-row gap-lg-3 flex-column mt-2 mt-lg-0" style="justify-content: center;">
                    <div id="chart-memorySizeLegend-container" class="bg-secondary rounded px-4" style="place-self: anchor-center; height:auto; max-height:25vh; overflow:auto;"></div>
                    <div id="chart-memorySize-container" class="bg-secondary rounded"></div>
                </div>
            </div>
        </div>
    </div>
    <?php

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
