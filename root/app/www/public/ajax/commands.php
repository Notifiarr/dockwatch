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
    <ol class="breadcrumb rounded p-1 ps-2">
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
                                <option value="docker-startContainer">start {container}</option>
                                <option value="docker-stopContainer">stop {container}</option>
                                <option value="docker-restartContainer">restart {container}</option>
                                <option value="docker-exec">exec {container} {command}</option>
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
                                <td class="rounded-bottom-left-1 rounded-bottom-right-1 bg-primary ps-3 text-center" colspan="3">
                                    <button class="btn btn-secondary w-25" onclick="runCommand()">Run</button>
                                    <button class="btn btn-secondary w-25" onclick="saveCommand()">Save</button>
                                    <button class="btn btn-secondary w-25" onclick="listCommand()">List</button>
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
    $servers = explode(',', $_POST['servers']);

    foreach ($serversTable as $serverId => $serverData) {
        if (in_array($serverId, $servers)) {
            apiSetActiveServer($serverData['id'], $serversTable);

            $apiResponse = apiRequest($_POST['command'], ['name' => $_POST['container'], 'params' => $_POST['parameters']], ['name' => $_POST['container'], 'params' => $_POST['parameters']]);
            $apiResponse = $apiResponse['code'] == 200 ? $apiResponse['result'] : $apiResponse['code'] . ': ' . $apiResponse['error'];

            ?>
            <h4 class="d-inline-block"><?= $serverData['name'] ?></h4> <span class="small-text d-inline-block"><?= $serverData['url'] ?></span>
            <pre class="bg-dark primary p-3 rounded" style="color: white; max-height: 500px; overflow: auto; white-space: pre-wrap;"><?= htmlspecialchars(cleanTTYOutput($apiResponse)) ?></pre>
            <?php
        }
    }

    apiSetActiveServer(APP_SERVER_ID, $serversTable);
}

if ($_POST['m'] == 'saveCommand') {
    logger(SYSTEM_LOG, 'Save command: ' . $_POST['command']);
    $servers = explode(',', $_POST['servers']);
    $id = $_POST['id'] ?: md5(bin2hex(random_bytes(16)));
    $commandsFile = getFile(COMMANDS_FILE) ?: [];

    foreach ($serversTable as $serverId => $serverData) {
        if (in_array($serverId, $servers)) {
            if ($commandsFile[$id]) {
                $_POST['command']   = $commandsFile[$id]['command'];
                $_POST['container'] = $commandsFile[$id]['container'];
            }

            $commandsFile[$id] = [
                'command'    => $_POST['command'],
                'container'  => $_POST['container'],
                'parameters' => $_POST['parameters'],
                'servers'    => $serverId,
                'cron'       => $_POST['cron'] ?: []
            ];

            setFile(COMMANDS_FILE, json_encode($commandsFile, JSON_PRETTY_PRINT));
            echo true;
        }
    }

    echo false;
}

if ($_POST['m'] == 'deleteCommand') {
    logger(SYSTEM_LOG, 'Delete command: ' . $_POST['command']);
    $id = $_POST['id'];
    $commandsFile = getFile(COMMANDS_FILE) ?: [];

    if (!empty($id)) {
        unset($commandsFile[$id]);
        setFile(COMMANDS_FILE, json_encode($commandsFile, JSON_PRETTY_PRINT));
        echo true;
    }

    echo false;
}

if ($_POST['m'] == 'listCommand') {
    $commandsFile = getFile(COMMANDS_FILE) ?: [];
    logger(SYSTEM_LOG, 'List commands: ' . count($commandsFile));

    ?>
    <div class="bg-secondary rounded h-100 p-4">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col">ID</th>
                        <th class="bg-primary" scope="col">Command</th>
                        <th class="bg-primary" scope="col">Container</th>
                        <th class="bg-primary" scope="col">Parameters</th>
                        <th class="bg-primary" scope="col">Server</th>
                        <th class="bg-primary" scope="col">Cron</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col"></th>
                    </tr>
                </thead>
                <tbody id="listCommandRows">
                <?php
                foreach ($commandsFile as $id => $command) {
                    $escapedCommandJson = htmlspecialchars(json_encode($command));
                    ?>
                    <tr id="list-command-<?= $id ?>" class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary"><?= truncateMiddle($id, 16) ?></td>
                        <td class="bg-secondary"><?= $command['command'] ?></td>
                        <td class="bg-secondary"><?= $command['container'] ?></td>
                        <td class="bg-secondary">
                            <input id="list-command-<?= $id ?>-parameters" type="text" class="form-control" placeholder="None" value="<?= $command['parameters'] ?>" disabled>
                        </td>
                        <td class="bg-secondary">
                            <select class="form-select" id="list-command-<?= $id ?>-servers" disabled>
                                <?php
                                    foreach ($serversTable as $serverId => $serverData) {
                                        $isSelected = $command['servers'] == $serverId;
                                        if ($serversTable[$serverId]) { ?>
                                            <option value="<?= $serverId ?>" <?= $isSelected ? 'selected' : ''?>><?= $serverData['name'] ?></option>
                                        <?php
                                        }
                                    }
                                ?>
                            </select>
                        </td>
                        <td class="bg-secondary">
                            <div style="position: relative; display: flex; align-items: center;">
                                <input id="list-command-<?= $id ?>-cron" type="text" placeholder="Not active" class="form-control" value="<?= $command['cron'] ?: '' ?>" onclick="frequencyCronEditor(this.value, 'list-command-<?= $id ?>-cron', 'Command Cron')" disabled style="padding-right: 25px;">
                                <i id="list-command-<?= $id ?>-cron-clear" class="fas fa-times text-danger" style="display: none; position: absolute; right: 10px; cursor: pointer;" title="Clear cron" onclick="$('#list-command-<?= $id ?>-cron').val('')"></i>
                            </div>
                        </td>
                        <td class="bg-secondary text-center">
                            <div id="list-command-<?= $id ?>-buttons">
                                <i class="fas fa-play text-success" style="display: inline; cursor: pointer;" title="Run command" onclick="runCommand(<?= $escapedCommandJson ?>)"></i>
                                <i class="far fa-edit text-warning" style="display: inline; cursor: pointer;" title="Edit command" onclick="editCommand('<?= $id ?>')"></i>
                                <i class="far fa-trash-alt text-danger" style="display: inline; cursor: pointer;" title="Delete command" onclick="deleteCommand('<?= $id ?>')"></i>
                            </div>
                            <i id="list-command-<?= $id ?>-save" class="fas fa-check text-success" style="display: none; cursor: pointer;" title="Save command" onclick="editCommand('<?= $id ?>')"></i>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}