<?php

/*
----------------------------------
 ------  Created: 112223   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $images             = apiRequest('docker-getOrphanContainers');
    $images             = json_decode($images['result'], true);
    $volumes            = apiRequest('docker-getOrphanVolumes');
    $volumes            = json_decode($volumes['result'], true);
    $networks           = apiRequest('docker-getOrphanNetworks');
    $networks           = json_decode($networks['result'], true);
    $unusedContainers   = apiRequest('docker-getUnusedContainers');
    $unusedContainers   = json_decode($unusedContainers['result'], true);
    ?>
    <div class="container-fluid pt-4 px-4 mb-5">
        <div class="bg-secondary rounded h-100 p-4">
            <h4 class="mt-3 mb-0">Images</h4>
            <span class="small-text text-muted"><?= DockerSock::ORPHAN_CONTAINERS ?></span>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><input type="checkbox" class="form-check-input orphan-checkall" onclick="$('.orphanImages-check').prop('checked', $(this).prop('checked'));"></th>
                            <th scope="col">ID</th>
                            <th scope="col">Created</th>
                            <th scope="col">Repository</th>
                            <th scope="col">Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($images as $image) {
                            ?>
                            <tr id="image-<?= $image['ID'] ?>">
                                <th scope="row"><input id="orphanImage-<?= $image['ID'] ?>" type="checkbox" class="form-check-input orphanImages-check orphan"></th>
                                <td><?= $image['ID'] ?></td>
                                <td><?= $image['CreatedSince'] ?></td>
                                <td><?= $image['Repository'] ?></td>
                                <td><?= $image['Size'] ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <h4 class="mt-3 mb-0">Unused containers</h4>
            <span class="small-text text-muted"><?= DockerSock::UNUSED_CONTAINERS ?></span>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><input type="checkbox" class="form-check-input orphan-checkall" onclick="$('.orphanUnusedContainers-check').prop('checked', $(this).prop('checked'));"></th>
                            <th scope="col">ID</th>
                            <th scope="col">Repository</th>
                            <th scope="col">Tag</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($unusedContainers as $container) {
                            ?>
                            <tr id="unused-<?= $container['ID'] ?>">
                                <th scope="row"><input id="unusedContainer-<?= $container['ID'] ?>" type="checkbox" class="form-check-input orphanUnusedContainers-check orphan"></th>
                                <td><?= $container['ID'] ?></td>
                                <td><?= $container['Repository'] ?></td>
                                <td><?= $container['Tag'] ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <h4 class="mt-3 mb-0">Volumes</h4>
            <span class="small-text text-muted"><?= DockerSock::ORPHAN_VOLUMES ?></span>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><input type="checkbox" class="form-check-input orphan-checkall" onclick="$('.orphanVolumes-check').prop('checked', $(this).prop('checked'));"></th>
                            <th scope="col">Name</th>
                            <th scope="col">Mount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($volumes as $volume) {
                            ?>
                            <tr id="volume-<?= $volume['Name'] ?>">
                                <th scope="row"><input id="orphanVolume-<?= $volume['Name'] ?>" type="checkbox" class="form-check-input orphanVolumes-check orphan"></th>
                                <td><?= $volume['Name'] ?></td>
                                <td><?= $volume['Mountpoint'] ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <h4 class="mt-3 mb-0">Networks</h4>
            <span class="small-text text-muted"><?= DockerSock::ORPHAN_NETWORKS ?></span>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><input type="checkbox" class="form-check-input orphan-checkall" onclick="$('.orphanNetworks-check').prop('checked', $(this).prop('checked'));"></th>
                            <th scope="col">ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($networks as $network) {
                            ?>
                            <tr id="network-<?= $network['ID'] ?>">
                                <th scope="row"><input id="orphanNetwork-<?= $network['ID'] ?>" type="checkbox" class="form-check-input orphanNetworks-check orphan"></th>
                                <td><?= $network['ID'] ?></td>
                                <td><?= $network['Name'] ?></td>
                                <td><?= $network['Driver'] ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">
                                With selected:
                                <select id="massOrphanTrigger" class="form-control d-inline-block w-50">
                                    <option value="0">-- Select option --</option>
                                    <option value="1">Remove</option>
                                </select>
                                <button type="button" class="btn btn-outline-info" onclick="removeOrphans()">Apply</button>
                            </td>
                            <td>&nbsp;</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'removeOrphans') {
    switch ($_POST['action']) {
        case 'remove':
            if ($_POST['type'] == 'image' || $_POST['type'] == 'unused') {
                $apiRequest = apiRequest('docker-removeImage', [], ['image' => $_POST['orphan']])['result'];
                if (stri_contains($apiRequest, 'error') || stri_contains($apiRequest, 'help')) {
                    echo $apiRequest;
                }
            }
            if ($_POST['type'] == 'network') {
                $apiRequest = apiRequest('docker-removeNetwork', [], ['id' => $_POST['orphan']])['result'];
                if (stri_contains($apiRequest, 'error') || stri_contains($apiRequest, 'help')) {
                    echo $apiRequest;
                }
            }
            if ($_POST['type'] == 'volume') {
                $apiRequest = apiRequest('docker-removeVolume', [], ['name' => $_POST['orphan']])['result'];
                if (stri_contains($apiRequest, 'error') || stri_contains($apiRequest, 'help')) {
                    echo $apiRequest;
                }
            }
            break;
    }
}
