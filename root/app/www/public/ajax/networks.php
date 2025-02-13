<?php

/*
----------------------------------
 ------  Created: 112424   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $apiResult  = apiRequest('docker-networks', ['params' => 'ls'])['result'];
    $neworks    = explode("\n", $apiResult);

    ?>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#" onclick="initPage('overview')"><?= $_SESSION['activeServerName'] ?></a><span class="ms-2">↦</span></li>
        <li class="breadcrumb-item active" aria-current="page">Networks</li>
    </ol>
    <div class="bg-secondary rounded p-4">
        <div class="table-responsive bg-secondary">
        <table class="table">
            <thead>
                <tr>
                    <th class="rounded-top-left-1 bg-primary ps-3">Name</th>
                    <th class="bg-primary ps-3">Driver</th>
                    <th class="bg-primary ps-3">Gateway</th>
                    <th class="bg-primary ps-3">Subnet</th>
                    <th class="rounded-top-right-1 bg-primary ps-3 w-50">Containers</th>
                </tr>
            </thead>
            <?php
            foreach ($neworks as $index => $network) {
                if ($index == 0) {
                    continue;
                }

                list($network, $id, $name, $driver, $scope) = explode(' ', $network);

                if ($network) {
                    $apiResult      = apiRequest('docker-networks', ['params' => 'inspect ' . $network])['result'];
                    $networkData    = json_decode($apiResult, true);

                    ?><tbody><?php
                    foreach ($networkData as $network) {
                        $gateway = $subnet = $containers = '';
                        if ($network['IPAM'] && $network['IPAM']['Config']) {
                            $gateway    = $network['IPAM']['Config'][0]['Gateway'];
                            $subnet     = $network['IPAM']['Config'][0]['Subnet'];
                        }

                        if ($network['Containers']) {
                            $containerList = [];
                            foreach ($network['Containers'] as $containerId => $container) {
                                $containerList[] = $container['Name'];
                            }

                            $containers = implode(', ', $containerList);
                        }
                        ?>
                        <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                            <td class="bg-secondary"><?= $network['Name'] ?><br><span class="small-text text-muted" title="<?= $network['Id'] ?>"><?= truncateMiddle($network['Id'], 30) ?></span></td>
                            <td class="bg-secondary"><?= $network['Driver'] ?></td>
                            <td class="bg-secondary"><?= $gateway ?></td>
                            <td class="bg-secondary"><?= $subnet ?></td>
                            <td class="bg-secondary"><?= $containers ?></td>
                        </tr>
                        <?php
                    }
                    ?></tbody><?php
                }
            }
    ?>
        </table>
        </div>
    </div>
    <?php
}
