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
    <div class="bg-secondary rounded p-4">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Driver</th>
                    <th>Gateway</th>
                    <th>Subnet</th>
                    <th class="w-50">Containers</th>
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
                        <tr>
                            <td><?= $network['Name'] ?><br><span class="small-text" title="<?= $network['Id'] ?>"><?= truncateMiddle($network['Id'], 20) ?></span></td>
                            <td><?= $network['Driver'] ?></td>
                            <td><?= $gateway ?></td>
                            <td><?= $subnet ?></td>
                            <td><?= $containers ?></td>
                        </tr>
                        <?php
                    }
                    ?></tbody><?php
                }
            }
    ?>
        </table>
    </div>
    <?php
}
