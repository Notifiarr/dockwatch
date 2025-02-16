<?php

/*
----------------------------------
 ------  Created: 020925   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'serverListToggle') {
    $serverList = getServers();

    ?>
    <div class="card border-primary mb-3">
        <div class="card-header">Loaded server</div>
        <div class="card-body">
            <div class="bg-secondary rounded w-100 text-center h4">
                <div class="p-2"><?= $_SESSION['activeServerName'] ?></div>
            </div>
        </div>
    </div>
    <?php

    if ($serverList['servers']) {
        ?>
        <div class="card border-primary mb-3">
            <div class="card-header">Connected servers</div>
            <div class="card-body">

                <?php
                foreach ($serverList['servers'] as $server) {
                    ?>
                    <div class="row mt-2">
                        <div class="d-flex flex-wrap flex-lg-nowrap gap-sm-2" style="justify-content:center;">
                            <div class="bg-secondary rounded px-2 w-100">
                                <div class="d-flex flex-row mt-2 card-server-name">
                                    <?= $server['name'] ?>
                                </div>
                                <div class="d-flex flex-row mt-2 card-server-status">
                                    <div class="col-6">
                                        Status: <?= $server['disabled'] ? '<span class="text-danger">' . $server['disabled'] . '</span>' : 'Online' ?>
                                    </div>
                                    <div class="col-6 text-end me-2">
                                        <i class="fas fa-object-group me-1" style="cursor:pointer;" onclick="updateActiveServer(<?= $server['id'] ?>)" title="Load server"></i>
                                        <i class="fas fa-external-link-alt" style="cursor:pointer;" onclick="window.open('<?= $server['url'] ?>')" title="Open server"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    }
}
