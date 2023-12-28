<?php

/*
----------------------------------
 ------  Created: 112223   ------
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
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Disable</th>
                                <th>Task</th>
                                <th>Interval</th>
                                <th>Execute</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('state', ($(this).prop('checked') ? 1 : 0))" <?= ($settingsFile['tasks']['state']['disabled'] ? 'checked' : '') ?>></td>
                                <td>State changes</td>
                                <td>5m</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('state')"></i></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('housekeeping', ($(this).prop('checked') ? 1 : 0))" <?= ($settingsFile['tasks']['housekeeping']['disabled'] ? 'checked' : '') ?>></td>
                                <td>Housekeeping</td>
                                <td>10m</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('housekeeper')"></i></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('health', ($(this).prop('checked') ? 1 : 0))" <?= ($settingsFile['tasks']['health']['disabled'] ? 'checked' : '') ?>></td>
                                <td>Health</td>
                                <td>15m</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('health')"></i></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('pulls', ($(this).prop('checked') ? 1 : 0))" <?= ($settingsFile['tasks']['pulls']['disabled'] ? 'checked' : '') ?>></td>
                                <td>Pulls</td>
                                <td>1h</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('pulls')"></i></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('prune', ($(this).prop('checked') ? 1 : 0))" <?= ($settingsFile['tasks']['prune']['disabled'] ? 'checked' : '') ?>></td>
                                <td>Prune</td>
                                <td>24h</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('prune')"></i></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>Icon update</td>
                                <td>24h</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('icons')"></i></td>
                            </tr>
                        </tbody>
                    </table>
                    <br clear="all">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Execute</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>View server variables</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('server')"></i></td>
                            </tr>
                            <tr>
                                <td>View session variables</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('session')"></i></td>
                            </tr>
                            <tr>
                                <td>View pull file</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('pullFile')"></i></td>
                            </tr>
                            <tr>
                                <td>View state file</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('stateFile')"></i></td>
                            </tr>
                            <tr>
                                <td>View icon alias files</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('aliasFile')"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-sm-9"><pre id="taskViewer" style="max-height: 500px; overflow: auto;">Select a task</pre></div>
            </div>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'runTask') {
    logger(SYSTEM_LOG, 'Run task: ' . $_POST['task']);

    $apiResponse = apiRequest('runTask', [], ['task' => $_POST['task']]);

    if ($apiResponse['code'] == 200) {
        $result = $apiResponse['response']['result'];
    } else {
        $error = 'Failed to run taks on server ' . ACTIVE_SERVER_NAME;
    }

    echo json_encode(['error' => $error, 'result' => $result, 'server' => ACTIVE_SERVER_NAME]);
}

if ($_POST['m'] == 'updateTaskDisabled') {
    $settingsFile['tasks'][$_POST['task']] = ['disabled' => intval($_POST['disabled'])];
    $saveSettings = setServerFile('settings', $settingsFile);

    if ($saveSettings['code'] != 200) {
        $error = 'Error saving task settings on server ' . ACTIVE_SERVER_NAME;
    }

    echo json_encode(['error' => $error, 'server' => ACTIVE_SERVER_NAME]);
}