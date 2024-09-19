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
                                <td><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('taskStatsDisabled', ($(this).prop('checked') ? 1 : 0))" <?= $settingsTable['taskStatsDisabled'] ? 'checked' : '' ?>></td>
                                <td>Stats changes</td>
                                <td>1m</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('stats')"></i></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('sseEnabled', ($(this).prop('checked') ? 1 : 0))" <?= !$settingsTable['sseEnabled'] ? 'checked' : '' ?>></td>
                                <td>SSE</td>
                                <td>1m</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('sse')"></i></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('taskStateDisabled', ($(this).prop('checked') ? 1 : 0))" <?= $settingsTable['taskStateDisabled'] ? 'checked' : '' ?>></td>
                                <td>State changes</td>
                                <td>5m</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('state')"></i></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('taskPullsDisabled', ($(this).prop('checked') ? 1 : 0))" <?= $settingsTable['taskPullsDisabled'] ? 'checked' : '' ?>></td>
                                <td>Pulls</td>
                                <td>5m</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('pulls')"></i></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('taskHousekeepingDisabled', ($(this).prop('checked') ? 1 : 0))" <?= $settingsTable['taskHousekeepingDisabled'] ? 'checked' : '' ?>></td>
                                <td>Housekeeping</td>
                                <td>10m</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('housekeeper')"></i></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('taskHealthDisabled', ($(this).prop('checked') ? 1 : 0))" <?= $settingsTable['taskHealthDisabled'] ? 'checked' : '' ?>></td>
                                <td>Health</td>
                                <td>15m</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('health')"></i></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('taskPruneDisabled', ($(this).prop('checked') ? 1 : 0))" <?= $settingsTable['taskPruneDisabled']? 'checked' : '' ?>></td>
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
                                <td align="center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('server')"></i></td>
                            </tr>
                            <tr>
                                <td>View session variables</td>
                                <td align="center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('session')"></i></td>
                            </tr>
                            <tr>
                                <td>View process list</td>
                                <td align="center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('processList')"></i></td>
                            </tr>
                            <tr>
                                <td>View pull file</td>
                                <td align="center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('pullFile')"></i></td>
                            </tr>
                            <tr>
                                <td>View state file</td>
                                <td align="center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('stateFile')"></i></td>
                            </tr>
                            <tr>
                                <td>View dependency file</td>
                                <td align="center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('dependencyFile')"></i></td>
                            </tr>
                            <tr>
                                <td>View icon alias files</td>
                                <td align="center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('aliasFile')"></i></td>
                            </tr>
                            <tr>
                                <td>API: View containers</td>
                                <td align="center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('containersList')"></i></td>
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

    $apiRequest = apiRequest('server-runTask', [], ['task' => $_POST['task']]);
    $result = $error = '';
    if ($apiRequest['code'] == 200) {
        $result = $apiRequest['result'];
    } else {
        $error = 'Failed to run taks on server ' . ACTIVE_SERVER_NAME;
    }

    echo json_encode(['error' => $error, 'result' => $result, 'server' => ACTIVE_SERVER_NAME]);
}

if ($_POST['m'] == 'updateTaskDisabled') {
    if ($_POST['task'] == 'sseEnabled') {
        apiRequest('database-setSetting', [], ['setting' => 'sseEnabled', 'value' => !intval($_POST['disabled'])]);
    } else {
        apiRequest('database-setSetting', [], ['setting' => $_POST['task'], 'value' => intval($_POST['disabled'])]);
    }

    echo json_encode(['error' => $error, 'server' => ACTIVE_SERVER_NAME]);
}
