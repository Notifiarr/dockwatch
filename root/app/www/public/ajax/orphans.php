<?php

/*
----------------------------------
 ------  Created: 112223   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $images           = apiRequest('docker/orphans/containers');
    $images           = json_decode($images['result'], true);
    $volumes          = apiRequest('docker/orphans/volumes');
    $volumes          = json_decode($volumes['result'], true);
    $networks         = apiRequest('docker/orphans/networks');
    $networks         = json_decode($networks['result'], true);
    $unusedContainers = apiRequest('docker/unused/containers');
    $unusedContainers = json_decode($unusedContainers['result'], true);
    ?>
    <div class="orphans-page">
    <div class="col-sm-12 access-rw" id="orphan-button-row">
        <div id="orphan-control-buttons" class="text-center">
            <div class="container-control-buttons-bg">
                <div class="bg-primary w-100" style="height: 100%; border-radius: 8px;"></div>
            </div>
            <span class="me-2">With selected:</span>
            <select id="massOrphanTrigger" class="form-select d-inline-block w-auto">
                <option value="0">-- Select option --</option>
                <option value="1">Remove</option>
            </select>
            <button type="button" class="btn btn-success btn-sm ms-2" onclick="removeOrphans()">Apply</button>
        </div>
    </div>
    <div class="bg-secondary rounded h-100 p-4">
        <h5 class="text-info mb-2">Images</h5>
        <div class="border border-secondary rounded p-3 mb-4">
        <span class="text-muted d-block mb-2"><code><?= DockerSock::ORPHAN_CONTAINERS ?></code></span>
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col"><input type="checkbox" class="form-check-input orphan-checkall" onclick="$('.orphanImages-check').prop('checked', $(this).prop('checked'));"></th>
                        <th class="bg-primary ps-3" scope="col">ID</th>
                        <th class="bg-primary ps-3" scope="col">Created</th>
                        <th class="bg-primary ps-3" scope="col">Repository</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col">Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($images) {
                        foreach ($images as $image) {
                            ?>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0" id="image-<?= $image['ID'] ?>">
                                <td class="bg-secondary" scope="row"><input id="orphanImage-<?= $image['ID'] ?>" type="checkbox" class="form-check-input orphanImages-check orphan"></td>
                                <td class="bg-secondary"><?= $image['ID'] ?></td>
                                <td class="bg-secondary"><?= $image['CreatedSince'] ?></td>
                                <td class="bg-secondary"><?= $image['Repository'] ?></td>
                                <td class="bg-secondary"><?= $image['Size'] ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <td class="bg-secondary" colspan="5">No orphaned images found</td><?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        </div>
        <h5 class="text-info mb-2">Unused containers</h5>
        <div class="border border-secondary rounded p-3 mb-4">
        <span class="text-muted d-block mb-2"><code><?= DockerSock::UNUSED_CONTAINERS ?></code></span>
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col"><input type="checkbox" class="form-check-input orphan-checkall" onclick="$('.orphanUnusedContainers-check').prop('checked', $(this).prop('checked'));"></th>
                        <th class="bg-primary ps-3" scope="col">ID</th>
                        <th class="bg-primary ps-3" scope="col">Repository</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col">Tag</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($unusedContainers) {
                        foreach ($unusedContainers as $container) {
                            ?>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0" id="unused-<?= $container['ID'] ?>">
                                <td class="bg-secondary" scope="row"><input id="unusedContainer-<?= $container['ID'] ?>" type="checkbox" class="form-check-input orphanUnusedContainers-check orphan"></td>
                                <td class="bg-secondary"><?= $container['ID'] ?></td>
                                <td class="bg-secondary"><?= $container['Repository'] ?></td>
                                <td class="bg-secondary"><?= $container['Tag'] ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <td class="bg-secondary" colspan="4">No unused containers found</td><?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        </div>
        <h5 class="text-info mb-2">Volumes</h5>
        <div class="border border-secondary rounded p-3 mb-4">
        <span class="text-muted d-block mb-2"><code><?= DockerSock::ORPHAN_VOLUMES ?></code></span>
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col"><input type="checkbox" class="form-check-input orphan-checkall" onclick="$('.orphanVolumes-check').prop('checked', $(this).prop('checked'));"></th>
                        <th class="bg-primary ps-3" scope="col">Name</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col">Mount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($volumes) {
                        foreach ($volumes as $volume) {
                            ?>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0" id="volume-<?= $volume['Name'] ?>">
                                <td class="bg-secondary" scope="row"><input id="orphanVolume-<?= $volume['Name'] ?>" type="checkbox" class="form-check-input orphanVolumes-check orphan"></td>
                                <td class="bg-secondary"><?= $volume['Name'] ?></td>
                                <td class="bg-secondary"><?= $volume['Mountpoint'] ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <td class="bg-secondary" colspan="3">No orphaned volumes found</td><?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        </div>
        <h5 class="text-info mb-2">Networks</h5>
        <div class="border border-secondary rounded p-3">
        <span class="text-muted d-block mb-2"><code><?= DockerSock::ORPHAN_NETWORKS ?></code></span>
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col"><input type="checkbox" class="form-check-input orphan-checkall" onclick="$('.orphanNetworks-check').prop('checked', $(this).prop('checked'));"></th>
                        <th class="bg-primary ps-3" scope="col">ID</th>
                        <th class="bg-primary ps-3" scope="col">Name</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col">Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($networks) {
                        foreach ($networks as $network) {
                            ?>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0" id="network-<?= $network['ID'] ?>">
                                <td class="bg-secondary" scope="row"><input id="orphanNetwork-<?= $network['ID'] ?>" type="checkbox" class="form-check-input orphanNetworks-check orphan"></td>
                                <td class="bg-secondary"><?= $network['ID'] ?></td>
                                <td class="bg-secondary"><?= $network['Name'] ?></td>
                                <td class="bg-secondary"><?= $network['Driver'] ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <td class="bg-secondary" colspan="4">No orphaned networks found</td><?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>
    </div>
    <?php
}

if ($_POST['m'] == 'removeOrphans') {
    switch ($_POST['action']) {
        case 'remove':
            if ($_POST['type'] == 'image' || $_POST['type'] == 'unused') {
                $apiRequest = apiRequest('docker/image/remove', [], ['image' => $_POST['orphan']])['result'];
                if (stri_contains($apiRequest, 'error') || stri_contains($apiRequest, 'help')) {
                    echo $apiRequest;
                }
            }
            if ($_POST['type'] == 'network') {
                $apiRequest = apiRequest('docker/network/remove', [], ['id' => $_POST['orphan']])['result'];
                if (stri_contains($apiRequest, 'error') || stri_contains($apiRequest, 'help')) {
                    echo $apiRequest;
                }
            }
            if ($_POST['type'] == 'volume') {
                $apiRequest = apiRequest('docker/volume/remove', [], ['name' => $_POST['orphan']])['result'];
                if (stri_contains($apiRequest, 'error') || stri_contains($apiRequest, 'help')) {
                    echo $apiRequest;
                }
            }
            break;
    }
}
