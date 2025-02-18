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
    <ol class="breadcrumb rounded p-1 ps-2">
        <li class="breadcrumb-item"><a href="#" onclick="initPage('overview')"><?= $_SESSION['activeServerName'] ?></a><span class="ms-2">↦</span></li>
        <li class="breadcrumb-item active" aria-current="page">Tasks</li>
    </ol>
    <div class="bg-secondary rounded p-4">
        <div class="row">
            <div class="col-sm-6">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="rounded-top-left-1 bg-primary ps-3">Disable</th>
                                <th class="bg-primary">Task</th>
                                <th class="bg-primary">Interval</th>
                                <th class="rounded-top-right-1 bg-primary pe-3">Execute</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3"><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('taskStatsDisabled', ($(this).prop('checked') ? 1 : 0))" <?= $settingsTable['taskStatsDisabled'] ? 'checked' : '' ?>></td>
                                <td class="bg-secondary">Stats changes</td>
                                <td class="bg-secondary">1m</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('stats')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3"><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('sseEnabled', ($(this).prop('checked') ? 1 : 0))" <?= !$settingsTable['sseEnabled'] ? 'checked' : '' ?>></td>
                                <td class="bg-secondary">SSE</td>
                                <td class="bg-secondary">1m</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('sse')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3"><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('taskStateDisabled', ($(this).prop('checked') ? 1 : 0))" <?= $settingsTable['taskStateDisabled'] ? 'checked' : '' ?>></td>
                                <td class="bg-secondary">State changes</td>
                                <td class="bg-secondary">5m</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('state')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3"><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('taskPullsDisabled', ($(this).prop('checked') ? 1 : 0))" <?= $settingsTable['taskPullsDisabled'] ? 'checked' : '' ?>></td>
                                <td class="bg-secondary">Pulls</td>
                                <td class="bg-secondary">5m</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('pulls')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3"><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('taskHousekeepingDisabled', ($(this).prop('checked') ? 1 : 0))" <?= $settingsTable['taskHousekeepingDisabled'] ? 'checked' : '' ?>></td>
                                <td class="bg-secondary">Housekeeping</td>
                                <td class="bg-secondary">10m</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('housekeeper')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3"><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('taskHealthDisabled', ($(this).prop('checked') ? 1 : 0))" <?= $settingsTable['taskHealthDisabled'] ? 'checked' : '' ?>></td>
                                <td class="bg-secondary">Health</td>
                                <td class="bg-secondary">15m</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('health')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3"><input type="checkbox" class="form-check-input" onclick="updateTaskDisabled('taskPruneDisabled', ($(this).prop('checked') ? 1 : 0))" <?= $settingsTable['taskPruneDisabled']? 'checked' : '' ?>></td>
                                <td class="bg-secondary">Prune</td>
                                <td class="bg-secondary">24h</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('prune')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3"></td>
                                <td class="bg-secondary">Icon update</td>
                                <td class="bg-secondary">24h</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('icons')"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <br clear="all">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="rounded-top-left-1 bg-primary ps-3">Task</th>
                                <th class="rounded-top-right-1 bg-primary">Execute</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3">View telemetry</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('telemetry')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3">View server variables</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('server')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3">View session variables</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('session')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3">View process list</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('processList')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3">View pull file</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('pullFile')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3">View state file</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('stateFile')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3">View dependency file</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('dependencyFile')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3">View icon alias files</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('aliasFile')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3">View containers</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('containersList')"></i></td>
                            </tr>
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary ps-3">View overview stats</td>
                                <td class="bg-secondary text-center"><i class="far fa-play-circle text-info" style="cursor: pointer;" onclick="runTask('overviewStats')"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-sm-6"><pre id="taskViewer" style="max-height: 500px; overflow: auto;">Select a task</pre></div>
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
