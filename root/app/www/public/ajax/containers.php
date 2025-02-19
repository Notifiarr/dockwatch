<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $containersTable        = apiRequest('database-getContainers')['result'];
    $containerGroupsTable   = apiRequest('database-getContainerGroups')['result'];
    $containerLinksTable    = apiRequest('database-getContainerGroupLinks')['result'];
    $dependencyFile         = $docker->setContainerDependencies($processList);
    $pulls                  = is_array($pullsFile) ? $pullsFile : json_decode($pullsFile, true);
    $pullsNotice            = empty($pullsFile) ? true : false;
    array_sort_by_key($processList, 'Names');

    $activeServer = apiGetActiveServer();
    if ($activeServer['id'] != APP_SERVER_ID) {
        $sseTitle = 'SSE is disabled (remote management)';
        $sseLabel = 'disabled';
    } else {
        if ($settingsTable['sseEnabled']) {
            $sseTitle = 'SSE is enabled and updating';
            $sseLabel = 'every minute (<span class="small-text text-muted" id="sse-timer">60</span>)';
        } else {
            $sseTitle = 'SSE is disabled in your settings';
            $sseLabel = 'disabled';
        }
    }
    ?>
    <ol class="breadcrumb rounded p-1 ps-2">
        <li class="breadcrumb-item"><a href="#" onclick="initPage('overview')"><?= $_SESSION['activeServerName'] ?></a><span class="ms-2">↦</span></li>
        <li class="breadcrumb-item active" aria-current="page">Containers</li>
    </ol>
    <div class="bg-secondary rounded p-4 mb-2" style="height:65px;">
        <div class="row" id="container-button-row">
            <div class="col-sm-12">
                <div id="container-control-buttons" class="text-center bg-secondary">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-light bg-secondary" onclick="massApplyContainerTrigger(false, 4)"><i class="fas fa-cloud-download-alt fa-xs me-1"></i><span class="container-control-button-label text-light hide-mobile"> Pull</span></button>
                        <button type="button" class="btn btn-outline-light bg-secondary" onclick="massApplyContainerTrigger(false, 2)"><i class="fas fa-sync-alt fa-xs me-1"></i><span class="container-control-button-label text-light hide-mobile"> Restart</span></button>
                        <button type="button" class="btn btn-outline-light bg-secondary" onclick="massApplyContainerTrigger(false, 1)"><i class="fas fa-play fa-xs me-1"></i><span class="container-control-button-label text-light hide-mobile"> Start</span></button>
                        <button type="button" class="btn btn-outline-light bg-secondary" onclick="massApplyContainerTrigger(false, 3)"><i class="fas fa-power-off fa-xs me-1"></i><span class="container-control-button-label text-light hide-mobile"> Stop</span></button>
                        <button type="button" class="btn btn-outline-warning bg-secondary" onclick="massApplyContainerTrigger(false, 13)"><i class="fas fa-skull-crossbones fa-xs me-1"></i><span class="container-control-button-label text-warning hide-mobile"> Kill</span></button>
                        <button type="button" class="btn btn-outline-danger bg-secondary" onclick="massApplyContainerTrigger(false, 9)"><i class="far fa-trash-alt fa-xs me-1"></i><span class="container-control-button-label text-danger hide-mobile"> Remove</span></button>
                    </div>
                    <div class="btn-group hide-mobile" role="group">
                        <button type="button" class="btn btn-outline-info bg-secondary disabled">Updates:</button>
                        <button type="button" class="btn btn-outline-info bg-secondary" onclick="massApplyContainerTrigger(false, 11)">Check</button>
                        <button type="button" class="btn btn-outline-info bg-secondary" onclick="massApplyContainerTrigger(false, 7)">Apply</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="bg-secondary rounded p-4">
        <div class="row">
            <?php if ($pullsNotice) { ?>
            <div class="col-sm-12">
                <div class="rounded m-2 p-2" style="background-color: var(--primary);">
                    There is currently no pull data available to show the Updates state. If the Updates column is set to Ignore then no checks will be made for that container. If you want current data, please set all the Update Options to Check for updates or Auto update and click save at the bottom. Once that is done you can click the check all and select Update: Check or Pull from the list. This will take a minute or two as it has to check every image.
                </div>
            </div>
            <?php } ?>
            <div class="col-sm-12 hide-mobile">
                <div class="text-end mb-2">
                    <span class="small-text text-muted">
                        Real time updates: <span class="small-text text-muted" title="<?= $sseTitle ?>"><?= $sseLabel ?></span>
                    </span>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="table-responsive">
                    <table class="table" id="container-table">
                        <thead>
                            <tr>
                                <th scope="col" class="rounded-top-left-1 bg-primary ps-3 container-table-header noselect no-sort"></th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect no-sort"></th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect hide-mobile">Name</th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect hide-mobile">Updates</th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect hide-mobile">State</th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect hide-mobile">Health</th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect no-sort hide-mobile">Mounts</th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect no-sort hide-mobile">Environment</th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect no-sort hide-mobile">Ports</th>
                                <th scope="col" class="rounded-top-right-1 bg-primary ps-3 container-table-header noselect no-sort hide-mobile">CPU/MEM</th>

                                <th scope="col" class="bg-primary ps-3 container-table-header noselect hide-desktop">Container</th>
                                <th scope="col" class="rounded-top-right-1 bg-primary ps-3 container-table-header noselect hide-desktop">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            //-- GROUPS
                            $groupContainerHashes = [];
                            if ($containerLinksTable) {
                                foreach ($containerGroupsTable as $containerGroup) {
                                    $groupHash          = $containerGroup['hash'];
                                    $groupContainers    = apiRequest('database-getGroupLinkContainersFromGroupId', ['group' => $containerGroup['id']])['result'];
                                    $groupCPU           = $groupMemory = $groupContainerCount = 0;

                                    foreach ($processList as $process) {
                                        $nameHash = md5($process['Names']);

                                        foreach ($groupContainers as $groupContainer) {
                                            if ($nameHash == $groupContainer['hash']) {
                                                $memUsage = floatval(str_replace('%', '', $process['stats']['MemPerc']));
                                                $groupMemory += $memUsage;

                                                $cpuUsage = floatval(str_replace('%', '', $process['stats']['CPUPerc']));
                                                if (intval($settingsTable['cpuAmount']) > 0) {
                                                    $cpuUsage = number_format(($cpuUsage / intval($settingsTable['cpuAmount'])), 2);
                                                }
                                                $groupCPU += $cpuUsage;

                                                $groupContainerCount++;
                                            }
                                        }
                                    }
                                    ?>
                                    <tr id="<?= $groupHash ?>" class="container-group">
                                        <td class="container-table-row bg-secondary"><input type="checkbox" class="form-check-input containers-check" onchange="$('.group-<?= $groupHash ?>-check').prop('checked', $(this).prop('checked'));"></td>
                                        <td class="container-table-row bg-secondary"><img src="<?= ABSOLUTE_PATH ?>images/container-group.png" height="32" width="32"></td>
                                        <td class="container-table-row bg-secondary">
                                            <span class="text-info container-group-label" style="cursor: pointer;" onclick="$('.<?= $groupHash ?>').toggle()"><?= $containerGroup['name'] ?></span><br>
                                            <span class="text-muted small-text">Containers: <?= $groupContainerCount ?></span>
                                        </td>
                                        <td class="container-table-row bg-secondary">&nbsp;</td>
                                        <td class="container-table-row bg-secondary hide-mobile">&nbsp;</td>
                                        <td class="container-table-row bg-secondary hide-mobile">&nbsp;</td>
                                        <td class="container-table-row bg-secondary hide-mobile">&nbsp;</td>
                                        <td class="container-table-row bg-secondary hide-mobile">&nbsp;</td>
                                        <td class="container-table-row bg-secondary hide-mobile">&nbsp;</td>
                                        <td class="container-table-row bg-secondary hide-mobile"><?= $groupCPU ?>%<br><?= $groupMemory ?>%</td>

                                        <td class="container-table-row bg-secondary hide-mobile hide-desktop">&nbsp;</td>
                                        <td class="container-table-row bg-secondary hide-mobile hide-desktop">&nbsp;</td>
                                    </tr>
                                    <?php

                                    foreach ($groupContainers as $groupContainer) {
                                        foreach ($processList as $process) {
                                            $nameHash = md5($process['Names']);

                                            if ($nameHash == $groupContainer['hash']) {
                                                $groupContainerHashes[] = $nameHash;
                                                renderContainerRow($nameHash, 'html');
                                                break;
                                            }
                                        }
                                    }
                                }
                            }

                            //-- NON GROUPS
                            $groupHash  = '';
                            foreach ($processList as $process) {
                                $nameHash = md5($process['Names']);

                                if ($groupContainerHashes) {
                                    if (in_array($nameHash, $groupContainerHashes)) {
                                        continue;
                                    }
                                }

                                renderContainerRow($nameHash, 'html');
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="rounded-bottom-right-1 rounded-bottom-left-1 bg-primary ps-3" colspan="12">
                                    <div style="float:right;">
                                        <button id="check-all-btn" class="dt-button mt-2 buttons-collection" tabindex="0" aria-controls="container-table" type="button"><input type="checkbox" class="form-check-input" onclick="toggleAllContainers()" id="containers-toggle-all"></button>
                                        <button id="group-restore-btn" style="display:none;" class="dt-button buttons-collection" tabindex="0" aria-controls="container-table" type="button" onclick="restoreContainerGroups()">Restore groups</button>
                                        <button id="container-groups-btn" class="dt-button mt-2 buttons-collection" tabindex="0" aria-controls="container-table" type="button" onclick="openContainerGroups()">Groups</button>
                                        <button id="container-updates-btn" class="dt-button mt-2 buttons-collection" tabindex="0" aria-controls="container-table" type="button" onclick="openUpdateOptions()">Updates</button>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Check all BC -->
    <select id="massContainerTrigger" class="d-none">
        <option value="0">-- Select option --</option>
        <option value="4">Pull</option>
        <option value="1">Start</option>
        <option value="3">Stop</option>
        <option value="13">Kill</option>
        <option value="2">Restart</option>
        <option value="9">Remove</option>
        <option value="12">Re-create</option>
        <option value="7">Update: Apply</option>
        <option value="11">Update: Check</option>
        <option value="8">Mount compare</option>
        <option value="10">Generate docker api create</option>
        <option value="6">Generate docker-compose</option>
        <option value="5">Generate docker run</option>
    </select>
    <?php
}

if ($_POST['m'] == 'containerInfo') {
    $containerSettings = apiRequest('database-getContainerFromHash', ['hash' => $_POST['hash']])['result'];
    foreach ($processList as $thisProcess) {
        if (md5($thisProcess['Names']) == $_POST['hash']) {
            $process = $thisProcess;
            break;
        }
    }

    switch ($containerSettings['updates']) {
        case 1:
            $updates = 'Auto update';
            break;
        case 2:
            $updates = 'Check only';
            break;
        default:
            $updates = 'Disabled';
            break;
    }

    $isDockwatch    = isDockwatchContainer($process) ? true : false;
    $skipActions    = skipContainerActions($process['inspect'][0]['Config']['Image'], $skipContainerActions);

    switch (true) {
        case str_contains($process['Status'], 'healthy'):
        case str_contains($process['Status'], 'unhealthy'):
        case str_contains($process['Status'], 'health:'):
            $health = true;
            break;
        default:
            $health = false;
    }

    $networkDependencies = $docker->getContainerNetworkDependencies($process['ID'], $processList);
    sort($networkDependencies);
    $networkDependencyList = implode(', ', $networkDependencies);

    if (count($networkDependencies) > 3) {
        $show = array_slice($networkDependencies, 0, 3);
        $networkDependencyList = implode(', ', $show);
        $networkDependencyList .= '<span class="depenency-list-toggle">';
        $networkDependencyList .= ' <br><input type="checkbox" class="depenency-list-toggle" onclick="$(\'.depenency-list-toggle\').toggle().prop(\'checked\', false);"> Show '. (count($networkDependencies) - 3) .' more';
        $networkDependencyList .= '</span>';

        $hide = array_slice($networkDependencies, 3, count($networkDependencies));
        $networkDependencyList .= '<span class="depenency-list-toggle" style="display: none;">';
        $networkDependencyList .= ', ' . implode(', ', $hide);
        $networkDependencyList .= ' <br><input type="checkbox" class="depenency-list-toggle" style="display: none;" onclick="$(\'.depenency-list-toggle\').toggle().prop(\'checked\', false);"> Hide '. (count($networkDependencies) - 3) .' more';
        $networkDependencyList .= '</span>';
    }

    if (!$networkDependencies) {
        $labelDependencies = $docker->getContainerLabelDependencies($process['Names'], $processList);
        sort($labelDependencies);
        $labelDependencyList = implode(', ', $labelDependencies);

        if (count($labelDependencies) > 3) {
            $show = array_slice($labelDependencies, 0, 3);
            $labelDependencyList = implode(', ', $show);
            $labelDependencyList .= '<span class="depenency-list-toggle">';
            $labelDependencyList .= ' <br><input type="checkbox" class="depenency-list-toggle" onclick="$(\'.depenency-list-toggle\').toggle().prop(\'checked\', false);"> Show '. (count($labelDependencies) - 3) .' more';
            $labelDependencyList .= '</span>';

            $hide = array_slice($labelDependencies, 3, count($labelDependencies));
            $labelDependencyList .= '<span class="depenency-list-toggle" style="display: none;">';
            $labelDependencyList .= ', ' . implode(', ', $hide);
            $labelDependencyList .= ' <br><input type="checkbox" class="depenency-list-toggle" style="display: none;" onclick="$(\'.depenency-list-toggle\').toggle().prop(\'checked\', false);"> Hide '. (count($labelDependencies) - 3) .' more';
            $labelDependencyList .= '</span>';
        }
    }
    ?>
    <div class="text-center">
        <span class="h3"><?= $process['Names'] ?></span>
        <div style="float:right;"><i style="cursor:pointer;" class="far fa-window-close fa-lg text-danger popout-close"></i></div>
    </div>
    <hr>
    <span class="h4 text-info">Actions</span><br>
    <div class="text-center mt-2 mb-3">
        <?php if ($isDockwatch) { ?>
            <button class="btn btn-info" onclick="dockwatchWarning()">Manage dockwatch</button>
        <?php } else { ?>
            <div class="btn-group btn-group-sm mb-2" role="group">
                <button type="button" class="btn btn-outline-light popout-close" onclick="massApplyContainerTrigger(false, 4, '<?= $_POST['hash'] ?>')"><i class="fas fa-cloud-download-alt fa-xs me-1"></i> Pull</button>
                <button type="button" class="btn btn-outline-light popout-close" onclick="massApplyContainerTrigger(false, 2, '<?= $_POST['hash'] ?>')"><i class="fas fa-sync-alt fa-xs me-1"></i> Restart</button>
                <button type="button" class="btn btn-outline-light popout-close" onclick="massApplyContainerTrigger(false, 1, '<?= $_POST['hash'] ?>')"><i class="fas fa-play fa-xs me-1"></i> Start</button>
                <button type="button" class="btn btn-outline-light popout-close" onclick="massApplyContainerTrigger(false, 3, '<?= $_POST['hash'] ?>')"><i class="fas fa-power-off fa-xs me-1"></i> Stop</button>
                <button type="button" class="btn btn-outline-warning popout-close <?= $skipActions ? 'd-none' : '' ?>" onclick="massApplyContainerTrigger(false, 13, '<?= $_POST['hash'] ?>')"><i class="fas fa-skull-crossbones fa-xs me-1"></i> Kill</button>
            </div>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-info disabled">Updates:</button>
                <button type="button" class="btn btn-outline-info popout-close" onclick="massApplyContainerTrigger(false, 11, '<?= $_POST['hash'] ?>')">Check</button>
                <button type="button" class="btn btn-outline-info popout-close <?= $skipActions ? 'd-none' : '' ?>" onclick="massApplyContainerTrigger(false, 7, '<?= $_POST['hash'] ?>')">Apply</button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger popout-close <?= $skipActions ? 'd-none' : '' ?>" onclick="massApplyContainerTrigger(false, 9, '<?= $_POST['hash'] ?>')"><i class="far fa-trash-alt fa-xs me-1"></i> Remove</button>
        <?php } ?>
    </div>

    <span class="h4 text-info">Settings</span><br>
    <table class="table table-hover small-text">
        <tr>
            <td class="w-50">Internal compose</td>
            <td style="cursor:pointer;" onclick="initPage('compose')" class="popout-close"><i class="fab fa-octopus-deploy me-2 text-muted"></i> <?= isComposeContainer($process['Names']) ? 'Yes' : 'No' ?></td>
        </tr>
        <tr>
            <td>Updates</td>
            <td><?= $updates ?></td>
        </tr>
        <tr>
            <td>Frequency</td>
            <td><?= $containerSettings['frequency'] ?></td>
        </tr>
        <?php if ($health) { ?>
        <tr>
            <td>Restart unhealthy</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <input <?= $skipActions ? 'disabled' : '' ?> onclick="updateContainerOption('restartUnhealthy', '<?= $_POST['hash'] ?>', 1)" type="radio" class="btn-check" name="restartUnhealthyGroup" id="restartUnhealthyGroup-yes" autocomplete="off" <?= $containerSettings['restartUnhealthy'] ? 'checked' : '' ?>>
                    <label class="btn btn-outline-light" for="restartUnhealthyGroup-yes">Yes</label>

                    <input <?= $skipActions ? 'disabled' : '' ?> onclick="updateContainerOption('restartUnhealthy', '<?= $_POST['hash'] ?>', 0)" type="radio" class="btn-check" name="restartUnhealthyGroup" id="restartUnhealthyGroup-no" autocomplete="off" <?= !$containerSettings['restartUnhealthy'] ? 'checked' : '' ?>>
                    <label class="btn btn-outline-light" for="restartUnhealthyGroup-no">No</label>
                </div>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <td>Notifications</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <input onclick="updateContainerOption('disableNotifications', '<?= $_POST['hash'] ?>', 0)" type="radio" class="btn-check" name="notificationGroup" id="notifications-yes" autocomplete="off" <?= !$containerSettings['disableNotifications'] ? 'checked' : '' ?>>
                    <label class="btn btn-outline-light" for="notifications-yes">Yes</label>

                    <input onclick="updateContainerOption('disableNotifications', '<?= $_POST['hash'] ?>', 1)" type="radio" class="btn-check" name="notificationGroup" id="notifications-no" autocomplete="off" <?= $containerSettings['disableNotifications'] ? 'checked' : '' ?>>
                    <label class="btn btn-outline-light" for="notifications-no">No</label>
                </div>
            </td>
        </tr>
        <tr>
            <td>Shutdown delay</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <input onclick="updateContainerOption('shutdownDelay', '<?= $_POST['hash'] ?>', 1)" type="radio" class="btn-check" name="shutdownDelayGroup" id="shutdownDelay-yes" autocomplete="off" <?= $containerSettings['shutdownDelay'] ? 'checked' : '' ?>>
                    <label class="btn btn-outline-light" for="shutdownDelay-yes">Yes</label>

                    <input onclick="updateContainerOption('shutdownDelay', '<?= $_POST['hash'] ?>', 0)" type="radio" class="btn-check" name="shutdownDelayGroup" id="shutdownDelay-no" autocomplete="off" <?= !$containerSettings['shutdownDelay'] ? 'checked' : '' ?>>
                    <label class="btn btn-outline-light" for="shutdownDelay-no">No</label>
                </div>
            </td>
        </tr>
        <tr>
            <td>Shutdown wait</td>
            <td><input onchange="updateContainerOption('shutdownDelaySeconds', '<?= $_POST['hash'] ?>', -1)" type="number" class="form-control w-50 d-inline-block" id="shutdownDelaySeconds" value="<?= $containerSettings['shutdownDelaySeconds'] <= 5 ? 5 : $containerSettings['shutdownDelaySeconds'] ?>"> seconds</td>
        </tr>
        <tr>
            <td>Auto restart</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <input <?= $skipActions == SKIP_FORCE ? 'checked disabled' : '' ?> onclick="updateContainerOption('autoRestart', '<?= $_POST['hash'] ?>', 1)" type="radio" class="btn-check" name="autoRestartGroup" id="autoRestart-yes" autocomplete="off" <?= $containerSettings['autoRestart'] ? 'checked' : '' ?>>
                    <label class="btn btn-outline-light" for="autoRestart-yes">Yes</label>

                    <input <?= $skipActions == SKIP_FORCE ? 'disabled' : '' ?> onclick="updateContainerOption('autoRestart', '<?= $_POST['hash'] ?>', 0)" type="radio" class="btn-check" name="autoRestartGroup" id="autoRestart-no" autocomplete="off" <?= !$containerSettings['autoRestart'] ? 'checked' : '' ?>>
                    <label class="btn btn-outline-light" for="autoRestart-no">No</label>
                </div>
            </td>
        </tr>
        <tr>
            <td>Auto restart frequency</td>
            <td>
                <input type="text" class="form-control d-inline-block w-75" id="autoRestartFrequency" onchange="updateContainerOption('autoRestartFrequency', '<?= $_POST['hash'] ?>', -1)" onclick="frequencyCronEditor(this.value, 'autoRestart', 'autoRestart')" value="<?= $containerSettings['autoRestartFrequency'] ?? $settingsTable['updatesFrequency'] ?>" readonly> <i class="far fa-question-circle" style="cursor: pointer;" title="HELP!" onclick="containerFrequencyHelp()"></i>
            </td>
        </tr>
        <tr>
            <td>Blacklist<br>No state changes</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <input <?= $skipActions == SKIP_FORCE ? 'checked disabled' : '' ?> onclick="updateContainerOption('blacklist', '<?= $_POST['hash'] ?>', 1)" type="radio" class="btn-check" name="blacklistGroup" id="blacklist-yes" autocomplete="off" <?= $containerSettings['blacklist'] ? 'checked' : '' ?>>
                    <label class="btn btn-outline-light" for="blacklist-yes">Yes</label>

                    <input <?= $skipActions == SKIP_FORCE ? 'disabled' : '' ?> onclick="updateContainerOption('disableNotifications', '<?= $_POST['hash'] ?>', 0)" type="radio" class="btn-check" name="blacklistGroup" id="blacklist-no" autocomplete="off" <?= $skipActions != SKIP_FORCE && !$containerSettings['blacklist'] ? 'checked' : '' ?>>
                    <label class="btn btn-outline-light" for="blacklist-no">No</label>
                </div>
            </td>
        </tr>
    </table>
    <span class="h4 text-info">Container</span><br>
    <table class="table table-hover small-text">
        <tr>
            <td>Logs</td>
            <td><i class="far fa-file-alt" style="cursor:pointer;" title="Open logs" onclick="containerLogs('<?= $process['Names'] ?>')"></i></td>
        </tr>
        <tr>
            <td>Created</td>
            <td><?= $process['CreatedAt'] ?></td>
        </tr>
        <tr>
            <td>Image</td>
            <td><?= $process['Image'] ?></td>
        </tr>
        <tr>
            <td>Network</td>
            <td><?= empty($process['Networks']) ? 'container:' . explode(':', $process['inspect'][0]['Config']['Labels']['com.docker.compose.depends_on'])[0] : $process['Networks'] ?></td>
        </tr>
        <?php if ($networkDependencies || $labelDependencies) { ?>
            <tr>
                <td>Dependencies</td>
                <td><?= $networkDependencyList ?: $labelDependencies ?></td>
            </tr>
        <?php } ?>
    </table>
    <?php
}

if ($_POST['m'] == 'containerLogs') {
    $apiResult = apiRequest('docker-logs', ['name' => $_POST['container']]);
    logger(UI_LOG, 'dockerLogs:' . json_encode($apiResult, JSON_UNESCAPED_SLASHES));
    echo $apiResult['result'];
}

if ($_POST['m'] == 'massApplyContainerTrigger') {
    logger(UI_LOG, 'massApplyContainerTrigger ->');

    $dependencyFile = apiRequest('file-dependency')['result'];
    $container      = $docker->findContainer(['hash' => $_POST['hash'], 'data' => $stateFile]);
    $image          = $docker->isIO($container['inspect'][0]['Config']['Image']);
    $currentImageID = $container['ID'];

    logger(UI_LOG, 'trigger:' . $_POST['trigger']);
    logger(UI_LOG, 'findContainerFromHash:' . json_encode($container, JSON_UNESCAPED_SLASHES));
    logger(UI_LOG, 'image:' . $image);

    $dependencies = [];
    switch ($_POST['trigger']) {
        case '1': //-- START
            if (skipContainerActions($image, $skipContainerActions)) {
                logger(UI_LOG, 'skipping ' . $container['Names'] . ' start request');
                $result = 'Skipped ' . $container['Names'] . '<br>';
            } else {
                $apiRequest = apiRequest('docker-startContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'docker-startContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                $result = 'Started ' . $container['Names'] . '<br>';
            }
            break;
        case '2': //-- RESTART
            if (skipContainerActions($image, $skipContainerActions)) {
                logger(UI_LOG, 'skipping ' . $container['Names'] . ' restart request');
                $result = 'Skipped ' . $container['Names'] . '<br>';
            } else {
                $apiRequest = apiRequest('docker-stopContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'docker-stopContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                $apiRequest = apiRequest('docker-startContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'docker-startContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                $result = 'Restarted ' . $container['Names'] . '<br>';
                $dependencies = $dependencyFile[$container['Names']]['containers'];
            }
            break;
        case '3': //-- STOP
            if (skipContainerActions($image, $skipContainerActions)) {
                logger(UI_LOG, 'skipping ' . $container['Names'] . ' stop request');
                $result = 'Skipped ' . $container['Names'] . '<br>';
            } else {
                $apiRequest = apiRequest('docker-stopContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'docker-stopContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                $result = 'Stopped ' . $container['Names'] . '<br>';
                $dependencies = $dependencyFile[$container['Names']]['containers'];
            }
            break;
        case '4': //-- PULL
            $regctlDigest = trim(regctlCheck($image));

            $apiRequest = apiRequest('docker-pullContainer', [], ['name' => $image]);
            logger(UI_LOG, 'docker-pullContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));

            $apiRequest   = apiRequest('docker-inspect', ['name' => $image, 'useCache' => false, 'format' => true]);
            $apiRequest   = json_decode($apiRequest['result'], true);
            list($cr, $imageDigest) = explode('@', $apiRequest[0]['RepoDigests'][0]);
            logger(UI_LOG, 'dockerInspect:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));

            $pullsFile[md5($container['Names'])]    = [
                                                        'checked'       => time(),
                                                        'name'          => $container['Names'],
                                                        'regctlDigest'  => $regctlDigest,
                                                        'imageDigest'   => $imageDigest
                                                    ];

            apiRequest('file-pull', [], ['contents' => $pullsFile]);
            $result = 'Pulled ' . $container['Names'] . '<br>';
            break;
        case '5': //-- GERNERATE RUN
            $apiRequest = apiRequest('docker-autoRun', ['name' => $container['Names']]);
            logger(UI_LOG, 'docker-autoRun:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
            $autoRun    = $apiRequest['result'];
            $result     = '<pre>' . $apiRequest . '</pre>';
            break;
        case '6': //-- GENERATE COMPOSE
            $containerList  = '';
            $containers     = explode(',', $_POST['hash']);

            foreach ($containers as $selectedContainer) {
                $thisContainer  = $docker->findContainer(['hash' => $selectedContainer, 'data' => $stateFile]);
                $containerList .= $thisContainer['Names'] . ' ';
            }

            $apiRequest = apiRequest('docker-autoCompose', ['name' => trim($containerList)]);
            logger(UI_LOG, 'docker-autoCompose:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
            $apiRequest = $apiRequest['result'];
            $result     = '<pre>' . $apiRequest . '</pre>';
            break;
        case '7': //-- CHECK FOR UPDATES AND APPLY THEM
            if (skipContainerActions($image, $skipContainerActions)) {
                logger(UI_LOG, 'skipping ' . $container['Names'].' update');
                $updateResult = 'skipped';
            } else {
                $image = $container['inspect'][0]['Config']['Image'];
                logger(UI_LOG, 'image:' . $image);

                $apiResponse = apiRequest('docker-inspect', ['name' => $container['Names'], 'useCache' => false, 'format' => true]);
                logger(UI_LOG, 'docker-inspect:' . json_encode($apiResponse, JSON_UNESCAPED_SLASHES));
                $inspectImage = $apiResponse['result'];

                if ($inspectImage) {
                    $inspect = json_decode($inspectImage, true);

                    foreach ($inspect[0]['Config']['Labels'] as $label => $val) {
                        if (str_contains($label, 'image.version')) {
                            $preVersion = $val;
                            break;
                        }
                    }
                }

                $apiRequest = apiRequest('docker-pullContainer', [], ['name' => $image]);
                logger(UI_LOG, 'docker-pullContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));

                $apiRequest = apiRequest('docker-stopContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'docker-stopContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));

                $apiRequest = apiRequest('docker-removeContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'docker-removeContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));

                $apiRequest = apiRequest('docker-createContainer', [], ['inspect' => $inspectImage]);
                logger(UI_LOG, 'docker-createContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                $update         = $apiRequest['result'];
                $updateResult   = 'failed';

                if (strlen($update['Id']) == 64) {
                    // REMOVE THE IMAGE AFTER UPDATE
                    $apiRequest = apiRequest('docker-removeImage', [], ['image' => $currentImageID]);
                    logger(UI_LOG, 'docker-removeImage:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));

                    $inspectImage           = apiRequest('docker-inspect', ['name' => $image, 'useCache' => false, 'format' => true]);
                    $inspectImage           = json_decode($inspectImage['result'], true);
                    list($cr, $imageDigest) = explode('@', $inspectImage[0]['RepoDigests'][0]);

                    if ($inspectImage) {
                        foreach ($inspectImage[0]['Config']['Labels'] as $label => $val) {
                            if (str_contains($label, 'image.version')) {
                                $postVersion = $val;
                                break;
                            }
                        }
                    }

                    $updateResult = 'complete';
                    $pullsFile[$_POST['hash']]  = [
                                                    'checked'       => time(),
                                                    'name'          => $container['Names'],
                                                    'regctlDigest'  => $imageDigest,
                                                    'imageDigest'   => $imageDigest
                                                ];

                    apiRequest('file-pull', [], ['contents' => $pullsFile]);

                    if (str_contains($container['State'], 'running')) {
                        $apiRequest = apiRequest('docker-startContainer', [], ['name' => $container['Names']]);
                        logger(UI_LOG, 'docker-startContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                    } else {
                        logger(UI_LOG, 'container was not running, not starting it');
                    }

                    $dependencies = $dependencyFile[$container['Names']]['containers'];
                    if ($dependencies) {
                        updateDependencyParentId($container['Names'], $update['Id']);
                    }
                }
            }

            $result = 'Container ' . $container['Names'] . ' update: ' . $updateResult . ($preVersion && $postVersion && $updateResult == 'complete' ? ' from \'' . $preVersion . '\' to \'' . $postVersion . '\'' : '') . '<br>';
            break;
        case '8': //-- MOUNT COMPARE
            $result = $container['Names'] . '<br>';
            $result .= '<div class="ms-4">' . implode('<br>', $container['inspect'][0]['HostConfig']['Binds']) . '</div><br>';
            break;
        case '9': //-- REMOVE
            if (skipContainerActions($image, $skipContainerActions)) {
                logger(UI_LOG, 'skipping ' . $container['Names'].' remove request');
                $result = 'Skipped ' . $container['Names'] . '<br>';
            } else {
                $apiRequest = apiRequest('docke-stopContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'dockerStopContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                $apiRequest = apiRequest('docker-removeContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'docker-removeContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                $result = 'Removed ' . $container['Names'] . '<br>';
            }
            break;
        case '10': //-- GENERATE API CREATE
            $apiRequest = apiRequest('dockerAPI-createContainer', ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerAPI-createContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
            $apiRequest = json_decode($apiRequest['result'], true);

            $result = $container['Names'] . '<br>';
            $result .= 'Endpoint: <code>' . $apiRequest['endpoint'] . '</code><br>';
            $result .= '<pre>' . json_encode($apiRequest['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
            break;
        case '11': //-- CHECK FOR UPDATES
            $apiResponse = apiRequest('docker-inspect', ['name' => $image, 'useCache' => false]);
            logger(UI_LOG, 'docker-inspect:' . json_encode($apiResponse, JSON_UNESCAPED_SLASHES));
            $inspectImage = json_decode($apiResponse['result'], true);

            foreach ($inspectImage[0]['Config']['Labels'] as $label => $val) {
                if (str_contains($label, 'image.version')) {
                    $version = $val;
                    break;
                }
            }

            logger(UI_LOG, 'Getting registry digest: ' . $image);
            $regctlDigest = trim(regctlCheck($image));

            //-- LOOP ALL IMAGE DIGESTS, STOP AT A MATCH
            foreach ($inspectImage[0]['RepoDigests'] as $digest) {
                list($cr, $imageDigest) = explode('@', $digest);

                if ($imageDigest == $regctlDigest) {
                    break;
                }
            }

            if (str_contains($regctlDigest, 'Error')) {
                logger(UI_LOG, $regctlDigest, 'error');
                $result = 'Container ' . $container['Names'] . ': error fetching regctl<br>';
            } else {
                logger(UI_LOG, '|__ regctl \'' . truncateMiddle(str_replace('sha256:', '', $regctlDigest), 30) . '\' image \'' . truncateMiddle(str_replace('sha256:', '', $imageDigest), 30) .'\'');

                if ($regctlDigest != $imageDigest) {
                    $result = 'Container ' . $container['Names'] . ': update available' . ($version ? ' (Current version: ' . $version . ')' : '') . '<br>';
                } else {
                    $result = 'Container ' . $container['Names'] . ': up to date' . ($version ? ' (' . $version . ')' : '') . '<br>';
                }

                $pullsFile[md5($container['Names'])]    = [
                                                            'checked'       => time(),
                                                            'name'          => $container['Names'],
                                                            'regctlDigest'  => $regctlDigest,
                                                            'imageDigest'   => $imageDigest
                                                        ];

                apiRequest('file-pull', [], ['contents' => $pullsFile]);
            }
            break;
        case '12': //-- RE-CREATE
            if (skipContainerActions($image, $skipContainerActions)) {
                logger(UI_LOG, 'skipping ' . $container['Names'].' re-create request');
                $result = 'Skipped ' . $container['Names'] . '<br>';
            } else {
                $image = $container['inspect'][0]['Config']['Image'];
                logger(UI_LOG, 'image:' . $image);

                $apiRequest = apiRequest('docker-inspect', ['name' => $container['Names'], 'useCache' => false, 'format' => true]);
                logger(UI_LOG, 'docker-inspect:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                $inspectImage = $apiRequest['result'];

                $apiRequest = apiRequest('docker-stopContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'docker-stopContainer:' . json_encode($apiResult, JSON_UNESCAPED_SLASHES));

                $apiResult = apiRequest('docker-removeContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'docker-removeContainer:' . json_encode($apiResult, JSON_UNESCAPED_SLASHES));

                $apiRequest = apiRequest('docker-createContainer', [], ['inspect' => $inspectImage]);
                logger(UI_LOG, 'docker-createContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                $update         = $apiRequest['result'];
                $createResult   = 'failed';

                if (strlen($update['Id']) == 64) {
                    $createResult = 'complete';

                    $apiRequest = apiRequest('docker-startContainer', [], ['name' => $container['Names']]);
                    logger(UI_LOG, 'docker-startContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                    $dependencies = $dependencyFile[$container['Names']]['containers'];

                    if ($dependencies) {
                        updateDependencyParentId($container['Names'], $update['Id']);
                    }
                }

                $result = 'Container ' . $container['Names'] . ' re-create: ' . $createResult . '<br>';
            }
            break;
        case '13': //-- KILL
            if (skipContainerActions($image, $skipContainerActions)) {
                logger(UI_LOG, 'skipping ' . $container['Names'] . ' kill request');
                $result = 'Skipped ' . $container['Names'] . '<br>';
            } else {
                $apiRequest = apiRequest('docker-killContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'docker-killContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                $result = 'Killed ' . $container['Names'] . '<br>';
                $dependencies = $dependencyFile[$container['Names']]['containers'];
            }
            break;
    }

    $getExpandedProcessList = getExpandedProcessList(true, true, true);
    $processList            = $getExpandedProcessList['processList'];

    $return                 = renderContainerRow($_POST['hash'], 'json');
    $return['result']       = $result;
    $return['dependencies'] = $dependencies;
    logger(UI_LOG, 'massApplyContainerTrigger <-');
    echo json_encode($return);
}

if ($_POST['m'] == 'controlContainer') {
    $container = $docker->findContainer(['hash' => $_POST['hash'], 'data' => $stateFile]);

    if (str_equals_any($_POST['action'], ['stop', 'restart'])) {
        apiRequest('docker-stopContainer', [], ['name' => $container['Names']]);
    }
    if (str_equals_any($_POST['action'], ['start', 'restart'])) {
        apiRequest('docker-startContainer', [], ['name' => $container['Names']]);
    }

    $return = renderContainerRow($_POST['hash'], 'json');

    if (str_equals_any($_POST['action'], ['start', 'restart'])) {
        $return['length'] = 'Up 1 second';
    }

    echo json_encode($return);
}

if ($_POST['m'] == 'updateContainerRows') {
    $processList = apiRequest('docker-processList', ['format' => true]);
    $processList = json_decode($processList['result'], true);

    $update = [];
    foreach ($processList as $process) {
        $nameHash = md5($process['Names']);
        $update[] = ['hash' => $nameHash, 'row' => renderContainerRow($nameHash, 'json')];
    }

    echo json_encode($update);
}

if ($_POST['m'] == 'openContainerGroups') {
    $processList = apiRequest('docker-processList', ['format' => true]);
    $processList = json_decode($processList['result'], true);
    array_sort_by_key($processList, 'Names');

    $containersTable            = apiRequest('database-getContainers')['result'];
    $containerGroupTable        = apiRequest('database-getContainerGroups')['result'];
    $containerGroupLinksTable   = apiRequest('database-getContainerGroupLinks')['result'];

    ?>
    <div class="bg-secondary rounded h-100 p-4">
        <div class="row mb-2">
            <div class="col-sm-12 col-lg-6">
                <div class="row">
                    <div class="col-sm-6 col-lg-2">Group</div>
                    <div class="col-sm-6 col-lg-10">
                        <select class="form-select" id="groupSelection" onchange="loadContainerGroup()">
                            <option value="0">New Group</option>
                            <?php
                            if ($containerGroupTable) {
                                foreach ($containerGroupTable as $groupDetails) {
                                    ?><option value="<?= $groupDetails['id'] ?>"><?= $groupDetails['name'] ?></option><?php
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6">
                <div class="row">
                    <div class="col-sm-6 col-lg-2">Name</div>
                    <div class="col-sm-6 col-lg-10"><input id="groupName" type="text" class="form-control w-75 d-inline-block" placeholder="Group Name Here"></div>
                </div>
            </div>
            <div class="col-sm-12 mt-2" id="deleteGroupContainer" style="display:none;">
                <span class="text-danger pe-2">Delete</span> <input id="groupDelete" type="checkbox" class="form-check-input">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col"><input type="checkbox" class="form-check-input" onclick="$('.group-check').prop('checked', $(this).prop('checked'));"></th>
                        <th class="bg-primary ps-3" scope="col">Name</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col">Existing Group</th>
                    </tr>
                </thead>
                <tbody id="containerGroupRows">
                <?php
                foreach ($processList as $process) {
                    $nameHash       = md5($process['Names']);
                    $container      = apiRequest('database-getContainerFromHash', ['hash' => $nameHash])['result'];
                    $inGroup        = '';

                    if ($containerGroupTable) {
                        foreach ($containerGroupTable as $containerGroup) {
                            $containersInGroup = apiRequest('database-getGroupLinkContainersFromGroupId', ['group' => $containerGroup['id']])['result'];

                            foreach ($containersInGroup as $containerInGroup) {
                                if ($containerInGroup['hash'] == $nameHash) {
                                    $inGroup = $containerGroup['name'];
                                    break;
                                }
                            }
                        }
                    }
                    ?>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row"><?= $inGroup ? '' : '<input id="groupContainer-' . $container['id'] . '" type="checkbox" class="form-check-input group-check">' ?></td>
                        <td class="bg-secondary"><?= $process['Names'] ?></td>
                        <td class="bg-secondary"><?= $inGroup ?: '<span class="text-warning">Not assigned</span>' ?></td>
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

if ($_POST['m'] == 'loadContainerGroup') {
    $processList = apiRequest('docker-processList', ['format' => true]);
    $processList = json_decode($processList['result'], true);
    array_sort_by_key($processList, 'Names');

    $containersTable            = apiRequest('database-getContainers')['result'];
    $containerGroupTable        = apiRequest('database-getContainerGroups')['result'];
    $containerGroupLinksTable   = apiRequest('database-getContainerGroupLinks')['result'];

    foreach ($processList as $process) {
        $nameHash       = md5($process['Names']);
        $container      = apiRequest('database-getContainerFromHash', ['hash' => $nameHash])['result'];
        $inGroup        = '';
        $inThisGroup    = false;

        if ($containerGroupTable) {
            foreach ($containerGroupTable as $containerGroup) {
                $containersInGroup = apiRequest('database-getGroupLinkContainersFromGroupId', ['group' => $containerGroup['id']])['result'];

                foreach ($containersInGroup as $containerInGroup) {
                    if ($containerInGroup['hash'] == $nameHash) {
                        $inGroup = $containerGroup['name'];

                        if ($containerGroup['id'] == $_POST['groupId']) {
                            $inGroup        = '<span class="text-success">' . $containerGroup['name'] . '</span>';
                            $inThisGroup    = true;
                        }

                        break;
                    }
                }
            }
        }

        ?>
        <tr>
            <th scope="row"><?= $inGroup ? ($inThisGroup ? '<input id="groupContainer-' . $container['id'] . '" type="checkbox" checked class="form-check-input group-check">' : '') : '<input id="groupContainer-' . $container['id'] . '" type="checkbox" class="form-check-input group-check">' ?></th>
            <td><?= $process['Names'] ?></td>
            <td><?= $inGroup ?: '<span class="text-warning">Not assigned</span>' ?></td>
        </tr>
        <?php
    }
}

if ($_POST['m'] == 'saveContainerGroup') {
    $groupName  = trim($_POST['name']);
    $groupId    = intval($_POST['groupId']);
    $error      = '';

    $containersTable            = apiRequest('database-getContainers')['result'];
    $containerGroupTable        = apiRequest('database-getContainerGroups')['result'];
    $containerGroupLinksTable   = apiRequest('database-getContainerGroupLinks')['result'];

    if ($_POST['delete']) {
        apiRequest('database-deleteContainerGroup', [], ['id' => $groupId]);
    } else {
        if (!$groupId) {
            foreach ($containerGroupTable as $containerGroup) {
                if (str_compare('nocase', $containerGroup['name'], $groupName)) {
                    $error = 'A group with that name already exists';
                    break;
                }
            }

            if (!$error) {
                $groupId = apiRequest('database-addContainerGroup', [], ['name' => $groupName])['result'];

                if (!$groupId) {
                    $error = 'Error creating the new \'' . $groupName . '\' group: ' . $database->error();
                }
            }
        } else {
            foreach ($containerGroupTable as $containerGroup) {
                if ($containerGroup['id'] == $groupId) {
                    if ($containerGroup['name'] != $groupName) {
                        apiRequest('database-updateContainerGroup', [], ['id' => $groupId, 'name' => $groupName]);
                    }
                    break;
                }
            }
        }

        if (!$error) {
            foreach ($_POST as $key => $val) {
                if (!str_contains($key, 'groupContainer')) {
                    continue;
                }

                list($junk, $containerId) = explode('-', $key);

                $linkExists = false;
                foreach ($containerGroupLinksTable as $groupLink) {
                    if ($groupLink['group_id'] != $groupId) {
                        continue;
                    }

                    if ($groupLink['container_id'] == $containerId) {
                        $linkExists = true;
                        break;
                    }
                }

                if ($linkExists) {
                    if (!$val) {
                        apiRequest('database-removeContainerGroupLink', [], ['groupId' => $groupId, 'containerId' => $containerId]);
                    }
                } else {
                    if ($val) {
                        apiRequest('database-addContainerGroupLink', [], ['groupId' => $groupId, 'containerId' => $containerId]);
                    }
                }
            }
        }
    }

    echo $error;
}

if ($_POST['m'] == 'openUpdateOptions') {
    $containersTable    = apiRequest('database-getContainers')['result'];
    $processList        = apiRequest('docker-processList', ['format' => true]);
    $processList        = json_decode($processList['result'], true);
    array_sort_by_key($processList, 'Names');

    ?>
    <div class="bg-secondary rounded h-100 p-4">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col"><input type="checkbox" class="form-check-input" onclick="$('.container-update-checkbox').prop('checked', $(this).prop('checked'));"></th>
                        <th class="bg-primary ps-3" scope="col">Name</th>
                        <th class="bg-primary ps-3" scope="col">Update</th>
                        <th class="bg-primary ps-3" scope="col">Frequency</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col">Minimum Age (Days)</th>
                    </tr>
                </thead>
                <tbody id="containerUpdateRows">
                <?php
                foreach ($processList as $process) {
                    $nameHash = md5($process['Names']);
                    $container = apiRequest('database-getContainerFromHash', ['hash' => $nameHash])['result'];
                    ?>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">
                            <input id="container-update-<?= $nameHash ?>-checkbox" type="checkbox" class="form-check-input container-update-checkbox">
                        </td>
                        <td class="bg-secondary"><?= $process['Names'] ?></td>
                        <td class="bg-secondary">
                            <select id="container-update-<?= $nameHash ?>" class="form-select container-update">
                                <option <?= $container['updates'] == '-1' ? 'selected' : '' ?> value="-1">-- Select Option --</option>
                                <option <?= $container['updates'] == '0'  ? 'selected' : '' ?> value="0">Ignore</option>
                                <option <?= $container['updates'] == '1'  ? 'selected' : '' ?> value="1">Auto update</option>
                                <option <?= $container['updates'] == '2'  ? 'selected' : '' ?> value="2">Check for updates</option>
                            </select>
                        </td>
                        <td class="bg-secondary">
                            <input id="container-frequency-<?= $nameHash ?>" type="text" class="form-control container-frequency" onclick="frequencyCronEditor(this.value, '<?= $nameHash ?>', '<?= $process['Names'] ?>')" value="<?= $container['frequency'] ?>" readonly>
                        </td>
                        <td class="bg-secondary">
                            <input id="container-update-minage-<?= $nameHash ?>" type="number" class="form-control container-update-minage" value="<?= $container['minage'] ?? 0 ?>">
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-sm-12 col-lg-4 text-end mb-2 mt-2">
                <select id="container-update-all" class="form-select d-inline-block w-75">
                    <option value="-1">-- Select Option --</option>
                    <option value="0">Ignore</option>
                    <option value="1">Auto update</option>
                    <option value="2">Check for updates</option>
                </select>
                <i class="fas fa-angle-up ms-1 me-1" style="cursor: pointer;" onclick="massChangeContainerUpdates(1)" title="Apply to selected containers"></i>
                <i class="fas fa-angle-double-up" style="cursor: pointer;" onclick="massChangeContainerUpdates(2)" title="Apply to all containers"></i>
            </div>
            <div class="col-sm-12 col-lg-4 text-end mb-2 mt-2">
                <input id="container-frequency-all" type="text"  class="form-control d-inline-block w-75" onclick="frequencyCronEditor(this.value, 'all', 'all')" value="<?= DEFAULT_CRON ?>" readonly>
                <i class="fas fa-angle-up ms-1 me-1" style="cursor: pointer;" onclick="massChangeFrequency(1)" title="Apply to selected containers"></i>
                <i class="fas fa-angle-double-up" style="cursor: pointer;" onclick="massChangeFrequency(2)" title="Apply to all containers"></i>
            </div>
            <div class="col-sm-12 col-lg-4 text-end mb-2 mt-2">
                <input id="container-update-minage-all" type="number"  class="form-control d-inline-block w-75" value="0">
                <i class="fas fa-angle-up ms-1 me-1" style="cursor: pointer;" onclick="massChangeMinAge(1)" title="Apply to selected containers"></i>
                <i class="fas fa-angle-double-up" style="cursor: pointer;" onclick="massChangeMinAge(2)" title="Apply to all containers"></i>
            </div>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'saveUpdateOptions') {
    $containersTable = apiRequest('database-getContainers')['result'];

    foreach ($_POST as $key => $val) {
        if (!str_contains($key, 'container-frequency-')) {
            continue;
        }

        $hash = str_replace('container-frequency-', '', $key);
        if (!$hash || $hash == 'all') {
            continue;
        }

        list($minute, $hour, $dom, $month, $dow) = explode(' ', $val);
        $frequency  = $minute . ' ' . $hour . ' ' . $dom . ' ' . $month . ' ' . $dow;

        $updates    = intval($_POST['container-update-' . $hash]);

        $minage     = intval($_POST['container-update-minage-' . $hash]);

        try {
            $cron = Cron\CronExpression::factory($frequency);
        } catch (Exception $e) {
            $frequency = DEFAULT_CRON;
        }

        //-- ONLY UPDATE WHAT HAS CHANGED
        $container = apiRequest('database-getContainerFromHash', ['hash' => $hash])['result'];
        if ($container['updates'] != $updates || $container['frequency'] != $frequency || $container['minage'] != $minage) {
            apiRequest('database-updateContainer', [], ['hash' => $hash, 'updates' => $updates, 'frequency' => $database->prepare($frequency), 'minage' => $database->prepare($minage)]);
        }
    }
}

if ($_POST['m'] == 'openEditContainer') {
    $container      = $docker->findContainer(['hash' => $_POST['hash'], 'data' => $stateFile]);
    $inspectImage   = apiRequest('docker-inspect', ['name' => $container['Image'], 'useCache' => false, 'format' => true]);
    $inspectImage   = json_decode($inspectImag['result'], true);
    $inspectImage   = $inspectImage[0];

    ?>
    <div class="bg-secondary rounded h-100 p-4">
        <?= $container['Names'] ?> (<?= $container['stats']['Container'] ?>)<br>
        <span class="text-muted"><?= $container['Image'] ?></span><br>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="2"><h4 class="text-info">Container</h4></td>
                    </tr>
                    <tr>
                        <td width="20%">Name</td>
                        <td><input type="text" class="form-control" value="<?= $container['Names'] ?>"></td>
                    </tr>
                    <tr>
                        <td>Repository</td>
                        <td><input type="text" class="form-control" value="<?= $container['Image'] ?>"></td>
                    </tr>
                    <tr>
                        <td>Icon</td>
                        <td>
                            <input type="text" class="form-control" value="">
                            <span class="text-muted">This will create a dockwatch label, should be a valid URL (Ex: https://domain.com/image.png)</span>
                        </td>
                    </tr>
                    <tr>
                        <td>Web UI</td>
                        <td>
                            <input type="text" class="form-control" value="">
                            <span class="text-muted">This will create a dockwatch label, should be a valid URL (Ex: http://dockwatch or http://10.1.0.1:<?= APP_PORT ?>)</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div style="float: left;"><h4 class="text-info">Environment</h4></div>
                            <div style="float: right;"><i class="fas fa-plus-circle text-success"></i></div>
                        </td>
                    </tr>
                    <?php
                    if ($container['inspect'][0]['Config']['Env']) {
                        foreach ($container['inspect'][0]['Config']['Env'] as $env) {
                            list($name, $value) = explode('=', $env);
                            ?>
                            <tr>
                                <td>&nbsp;</td>
                                <td>
                                    <div>Name: <div style="float: right; width: 80%;"><input type="text" class="form-control d-inline-block" value="<?= $name ?>"></div></div><br>
                                    <div>Value: <div style="float: right; width: 80%;"><input type="text" class="form-control d-inline-block" value="<?= $value ?>"></div></div><br>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                    <tr>
                        <td colspan="2">
                            <div style="float: left;"><h4 class="text-info">Mounts</h4></div>
                            <div style="float: right;"><i class="fas fa-plus-circle text-success"></i></div>
                        </td>
                    </tr>
                    <?php
                    if ($container['inspect'][0]['Mounts']) {
                        foreach ($container['inspect'][0]['Mounts'] as $mount) {
                            if ($mount['Type'] != 'bind') {
                                continue;
                            }

                            ?>
                            <tr>
                                <td>&nbsp;</td>
                                <td>
                                    <div>Inside: <div style="float: right; width: 80%;"><input type="text" class="form-control d-inline-block" value="<?= $mount['Destination'] ?>"></div></div><br>
                                    <div>Outside: <div style="float: right; width: 80%;"><input type="text" class="form-control d-inline-block" value="<?= $mount['Source'] ?>"></div></div><br>
                                    <div>
                                        Mode:
                                        <div style="float: right; width: 80%;">
                                            <select class="form-select">
                                                <option <?= ($mount['Mode'] == 'rw' ? 'selected' : '') ?> value="rw">Read/Write</option>
                                                <option <?= ($mount['Mode'] == 'rw,slave' ? 'selected' : '') ?> value="rw,slave">Read/Write - Slave</option>
                                                <option <?= ($mount['Mode'] == 'rw,shared' ? 'selected' : '') ?> value="rw,shared">Read/Write - Shared</option>
                                                <option <?= ($mount['Mode'] == 'ro' ? 'selected' : '') ?> value="ro">Read Only</option>
                                                <option <?= ($mount['Mode'] == 'ro,slave' ? 'selected' : '') ?> value="ro,slave">Read Only - Slave</option>
                                                <option <?= ($mount['Mode'] == 'ro,shared' ? 'selected' : '') ?> value="ro,shared">Read Only - Shared</option>
                                            </select>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                    <tr>
                        <td colspan="2">
                            <div style="float: left;"><h4 class="text-info">Labels</h4></div>
                            <div style="float: right;"><i class="fas fa-plus-circle text-success"></i></div>
                        </td>
                    </tr>
                    <?php

                    if ($container['inspect'][0]['Config']['Labels']) {
                        foreach ($container['inspect'][0]['Config']['Labels'] as $name => $value) {
                            //-- SKIP SOME LABELS
                            $skip = false;
                            foreach ($inspectImage['Config']['Labels'] as $imageLabelName => $imageLabelValue) {
                                if ($imageLabelName == $name) {
                                    $skip = true;
                                    break;
                                }
                            }

                            if ($skip || str_contains_any($name, ['net.unraid.', 'org.opencontainers.'])) {
                                continue;
                            }
                            ?>
                            <tr>
                                <td>&nbsp;</td>
                                <td>
                                    <div>Name: <div style="float: right; width: 80%;"><input type="text" class="form-control d-inline-block" value="<?= $name ?>"></div></div><br>
                                    <div>Value: <div style="float: right; width: 80%;"><input type="text" class="form-control d-inline-block" value="<?= str_replace('"', '&quot;', $value) ?>"></div></div><br>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'updateContainerOption') {
    $containersTable    = apiRequest('database-getContainers')['result'];
    $container          = apiRequest('database-getContainerGroupFromHash', ['hash' => $_POST['hash']])['result'];

    apiRequest('database-updateContainer', [], ['hash' => $_POST['hash'], $_POST['option'] => $database->prepare($_POST['setting'])]);
}
