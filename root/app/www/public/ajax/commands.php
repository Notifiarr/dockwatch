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
    <div class="container-fluid pt-4 px-4">
        <div class="bg-secondary rounded h-100 p-4">
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
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Run</th>
                                <th>Server</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($serversTable as $serverId => $serverData) {
                                ?>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input" id="command-<?= $serverId ?>"></td>
                                    <td><?= $serverData['name'] ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                            <tr>
                                <td colspan="2" align="center">
                                    <button class="btn btn-outline-info" onclick="runCommand()">Run Command</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-sm-9" id="commandResults"></div>
            </div>
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
