<?php

/*
----------------------------------
 ------  Created: 120123   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    ?>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#" onclick="initPage('overview')"><?= $_SESSION['activeServerName'] ?></a><span class="ms-2">↦</span></li>
        <li class="breadcrumb-item active" aria-current="page">Commands</li>
    </ol>
    <div class="bg-secondary rounded p-4">
        <div class="row">
            <div class="col-sm-3">
                <div class="row">
                    <div class="col-sm-12 mb-2">
                        <select class="form-select" id="command">
                            <optgroup label="docker">
                                <option value="docker-inspect">inspect {container}</option>
                                <option value="docker-networks">network {params}</option>
                                <option value="docker-port">port {container}</option>
                                <option value="docker-processList">ps</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-sm-12 col-md-6">
                        <input id="command-container" type="text" placeholder="container" class="form-control">
                    </div>
                    <div class="col-sm-12 col-md-6">
                        <input id="command-parameters" type="text" placeholder="params" class="form-control">
                    </div>
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="w-50 rounded-top-left-1 bg-primary ps-3">Run</th>
                                <th class="w-50 rounded-top-right-1 bg-primary ps-3">Server</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($serversTable as $serverId => $serverData) {
                                ?>
                                <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                    <td class="bg-secondary"><input type="checkbox" class="form-check-input" id="command-<?= $serverId ?>"></td>
                                    <td class="bg-secondary"><?= $serverData['name'] ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                            <tr>
                                <td class="rounded-bottom-left-1 rounded-bottom-right-1 bg-primary ps-3 text-center" colspan="2">
                                    <button class="btn btn-secondary" onclick="runCommand()">Run Command</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-sm-9" id="commandResults"></div>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'runCommand') {
    logger(SYSTEM_LOG, 'Run command: ' . $_POST['command']);

    $servers    = explode(',', $_POST['servers']);
    $params     = $_POST['params'];

    foreach ($serversTable as $serverId => $serverData) {
        if (in_array($serverId, $servers)) {
            apiSetActiveServer($serverData['id'], $serversTable);

            $apiResponse = apiRequest($_POST['command'], ['name' => $_POST['container'], 'params' => $_POST['parameters']]);
            $apiResponse = $apiResponse['code'] == 200 ? $apiResponse['result'] : $apiResponse['code'] . ': ' . $apiResponse['error'];

            ?>
            <h4 class="d-inline-block"><?= $serverData['name'] ?></h4> <span class="small-text d-inline-block"><?= $serverData['url'] ?></span>
            <pre style="max-height: 500px; overflow: auto;"><?= htmlspecialchars($apiResponse) ?></pre>
            <?php
        }
    }

    apiSetActiveServer(APP_SERVER_ID, $serversTable);
}
