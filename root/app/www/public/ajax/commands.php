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
                                    <option value="dockerInspect">inspect {container}</option>
                                    <option value="dockerNetworks">network {params}</option>
                                    <option value="dockerPort">port {container}</option>
                                    <option value="dockerProcessList">ps</option>
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
                            foreach ($serversFile as $serverIndex => $serverData) {
                                ?>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input" id="command-<?= $serverIndex ?>"></td>
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

    foreach ($serversFile as $serverIndex => $serverData) {
        if (in_array($serverIndex, $servers)) {
            $serverOverride = $serverData;
            $apiResponse = apiRequest($_POST['command'], ['name' => $_POST['container'], 'params' => $_POST['parameters']]);

            if ($apiResponse['code'] == 200) {
                $apiResponse = $apiResponse['response']['docker'];
            } else {
                $apiResponse = $apiResponse['code'] .': '. $apiResponse['error'];
            }

            ?>
            <h4><?= $serverData['name'] ?></h4>
            <pre style="max-height: 500px; overflow: auto;"><?= $apiResponse ?></pre>
            <?php
        }
    }
}