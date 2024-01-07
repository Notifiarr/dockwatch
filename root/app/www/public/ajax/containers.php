<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $pulls = is_array($pullsFile) ? $pullsFile : json_decode($pullsFile, true);
    array_sort_by_key($processList, 'Names');

    ?>
    <div class="container-fluid pt-4 px-4 mb-5">
        <div class="bg-secondary rounded h-100 p-4">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><input type="checkbox" class="form-check-input" onclick="$('.containers-check').prop('checked', $(this).prop('checked'));"></th>
                            <th scope="col"></th>
                            <th scope="col">Name</th>
                            <th scope="col"></th>
                            <th scope="col">Updates</th>
                            <th scope="col">State</th>
                            <th scope="col">Health</th>
                            <th scope="col">Added</th>
                            <th scope="col">CPU</th>
                            <th scope="col">Memory</th>
                            <th scope="col">
                                <span onclick="$('#container-updates-all').toggle()" class="text-info" style="cursor: pointer;">Updates</span>
                                <select id="container-updates-all" style="display: none;" class="form-control" onchange="$('.container-updates').val($(this).val())">
                                    <option value="0">Ignore</option>
                                    <option value="1">Auto update</option>
                                    <option value="2">Check for updates</option>
                                </select>
                            </th>
                            <th scope="col">
                                <span onclick="$('#container-frequency-all').toggle()" class="text-info" style="cursor: pointer;">Frequency</span>
                                <select id="container-frequency-all" style="display: none;" class="form-control" onchange="$('.container-frequency').val($(this).val())">
                                    <option value="6h">6h</option>
                                    <option value="12h">12h</option>
                                    <option value="1d">1d</option>
                                    <option value="2d">2d</option>
                                    <option value="3d">3d</option>
                                    <option value="4d">4d</option>
                                    <option value="5d">5d</option>
                                    <option value="6d">6d</option>
                                    <option value="1w">1w</option>
                                    <option value="2w">2w</option>
                                    <option value="3w">3w</option>
                                    <option value="1m">1m</option>
                                </select>
                            </th>
                            <th>
                                <span onclick="$('#container-hour-all').toggle()" class="text-info" style="cursor: pointer;">Hour</span>
                                <select id="container-hour-all" style="display: none;" class="form-control container-hour" onchange="$('.container-hour').val($(this).val())">
                                <?php
                                for ($h = 0; $h <= 23; $h++) {
                                    ?><option value="<?= $h ?>"><?= str_pad($h, 2, 0, STR_PAD_LEFT) ?></option><?php
                                }
                                ?>
                                </select>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        //-- GROUPS
                        if ($settingsFile['containerGroups']) {
                            foreach ($settingsFile['containerGroups'] as $groupHash => $containerGroup) {
                                $groupCPU = $groupMemory = 0;
                                foreach ($processList as $process) {
                                    $nameHash = md5($process['Names']);

                                    if (in_array($nameHash, $containerGroup['containers'])) {
                                        $memUsage = floatval(str_replace('%', '', $process['stats']['MemPerc']));
                                        $groupMemory += $memUsage;

                                        $cpuUsage = floatval(str_replace('%', '', $process['stats']['CPUPerc']));
                                        if (intval($settingsFile['global']['cpuAmount']) > 0) {
                                            $cpuUsage = number_format(($cpuUsage / intval($settingsFile['global']['cpuAmount'])), 2);
                                        }
                                        $groupCPU += $cpuUsage;
                                    }
                                }
                                ?>
                                <tr id="<?= $groupHash ?>" style="background-color: #1c2029;">
                                    <th scope="row"><input type="checkbox" class="form-check-input containers-check" onclick="$('.group-<?= $groupHash ?>-check').prop('checked', $(this).prop('checked'));"></th>
                                    <td><img src="<?= ABSOLUTE_PATH ?>images/container-group.png" height="32" width="32"></td>
                                    <td><span class="text-info" style="cursor: pointer;" onclick="$('.<?= $groupHash ?>').toggle()"><?= $containerGroup['name'] ?></span><br><span class="text-muted small-text">Containers: <?= count($containerGroup['containers']) ?></span></td>
                                    <td colspan="5"></td>
                                    <td><?= $groupCPU ?>%</td>
                                    <td><?= $groupMemory ?>%</td>
                                    <td colspan="3"></td>
                                </tr>
                                <?php

                                foreach ($containerGroup['containers'] as $containerHash) {
                                    foreach ($processList as $process) {
                                        $nameHash = md5($process['Names']);
    
                                        if ($nameHash == $containerHash) {
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
                            $inGroup    = false;
                            $nameHash   = md5($process['Names']);
                            if ($settingsFile['containerGroups']) {
                                foreach ($settingsFile['containerGroups'] as $containerGroup) {
                                    if (in_array($nameHash, $containerGroup['containers'])) {
                                        $inGroup = true;
                                        break;
                                    }
                                }
                            }

                            if ($inGroup) {
                                continue;
                            }

                            renderContainerRow($nameHash, 'html');
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                With selected: 
                                <select id="massContainerTrigger" class="form-control d-inline-block w-50">
                                    <option value="0">-- Select option --</option>
                                    <optgroup label="Control">
                                        <option value="4">Pull</option>
                                        <option value="9">Remove</option>
                                        <option value="2">Restart</option>
                                        <option value="1">Start</option>
                                        <option value="3">Stop</option>
                                        <option value="7">Update</option>
                                    </optgroup>
                                    <optgroup label="Information">
                                        <option value="8">Mount compare</option>
                                        <option value="10">Generate docker api create</option>
                                        <option value="6">Generate docker-compose</option>
                                        <option value="5">Generate docker run</option>
                                    </optgroup>
                                </select>
                                <button type="button" class="btn btn-outline-info" onclick="massApplyContainerTrigger()">Apply</button>
                            </td>
                            <td colspan="7" align="right">
                                <button type="button" class="btn btn-info" onclick="openContainerGroups()">Groups</button>
                                <button type="button" class="btn btn-success" onclick="saveContainerSettings()">Save Changes</button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'saveContainerSettings') {
    $newSettings = [];

    foreach ($_POST as $key => $val) {
        if (!str_contains($key, '-update-')) {
            continue;
        }

        $hash = str_replace('containers-update-', '', $key);
        $newSettings[$hash]    = [
                                    'updates'   => $val,
                                    'frequency' => $_POST['containers-frequency-' . $hash],
                                    'hour'      => $_POST['containers-hour-' . $hash]
                                ];
    }

    $settingsFile['containers'] = $newSettings;
    setServerFile('settings', $settingsFile);
}

if ($_POST['m'] == 'massApplyContainerTrigger') {
    logger(UI_LOG, 'massApplyContainerTrigger ->');
    $container  = findContainerFromHash($_POST['hash']);

    logger(UI_LOG, 'trigger:' . $_POST['trigger']);
    logger(UI_LOG, 'findContainerFromHash:' . json_encode($container));

    switch ($_POST['trigger']) {
        case '1': //-- START
            $apiResult = apiRequest('dockerStartContainer', [], ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerStartContainer:' . json_encode($apiResult));
            $result = 'Started ' . $container['Names'] . '<br>';
            break;
        case '2': //-- RESTART
            $apiResult = apiRequest('dockerStopContainer', [], ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerStopContainer:' . json_encode($apiResult));
            $apiResult = apiRequest('dockerStartContainer', [], ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerStartContainer:' . json_encode($apiResult));
            $result = 'Restarted ' . $container['Names'] . '<br>';
            break;
        case '3': //-- STOP
            $apiResult = apiRequest('dockerStopContainer', [], ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerStopContainer:' . json_encode($apiResult));
            $result = 'Stopped ' . $container['Names'] . '<br>';
            break;
        case '4': //-- PULL
            $image = isDockerIO($container['inspect'][0]['Config']['Image']);
            logger(UI_LOG, 'image:' . $image);

            $regctlDigest = trim(regctlCheck($image));

            $pull = apiRequest('dockerPullContainer', [], ['name' => $image]);
            logger(UI_LOG, 'dockerPullContainer:' . json_encode($pull));

            $inspectImage   = apiRequest('dockerInspect', ['name' => $image, 'useCache' => false, 'format' => true]);
            $inspectImage   = json_decode($inspectImage['response']['docker'], true);
            list($cr, $imageDigest) = explode('@', $inspectImage[0]['RepoDigests'][0]);
            logger(UI_LOG, 'dockerInspect:' . json_encode($inspectImage));

            $pullsFile[md5($container['Names'])]    = [
                                                        'checked'       => time(),
                                                        'name'          => $container['Names'],
                                                        'regctlDigest'  => $regctlDigest,
                                                        'imageDigest'   => $imageDigest
                                                    ];

            setServerFile('pull', $pullsFile);
            $result = 'Pulled ' . $container['Names'] . '<br>';
            break;
        case '5': //-- GERNERATE RUN
            $autoRun    = apiRequest('dockerAutoRun', ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerAutoRun:' . json_encode($autoRun));
            $autoRun    = $autoRun['response']['docker'];
            $result     = '<pre>' . $autoRun . '</pre>';
            break;
        case '6': //-- GENERATE COMPOSE
            $containerList  = '';
            $containers     = explode(',', $_POST['hash']);

            foreach ($containers as $selectedContainer) {
                $thisContainer  = findContainerFromHash($selectedContainer);
                $containerList .= $thisContainer['Names'] . ' ';
            }

            $autoCompose    = apiRequest('dockerAutoCompose', ['name' => trim($containerList)]);
            logger(UI_LOG, 'dockerAutoCompose:' . json_encode($autoCompose));
            $autoCompose    = $autoCompose['response']['docker'];
            $result         = '<pre>' . $autoCompose . '</pre>';
            break;
        case '7': //-- UPDATE
            $image              = $container['inspect'][0]['Config']['Image'];
            if(skipContainerUpdates($image, $skipContainerUpdates)) {
                logger(UI_LOG, 'skipping ' . $container['Names'].' update');
                $updateResult = 'skipped';
            } else {
                $image = $container['inspect'][0]['Config']['Image'];
                logger(UI_LOG, 'image:' . $image);

                $apiResponse = apiRequest('dockerInspect', ['name' => $container['Names'], 'useCache' => false, 'format' => true]);
                logger(UI_LOG, 'dockerInspect:' . json_encode($apiResponse));
                $inspectImage = $apiResponse['response']['docker'];

                $apiResponse = apiRequest('dockerPullContainer', [], ['name' => $image]);
                logger(UI_LOG, 'dockerPullContainer:' . json_encode($apiResponse));

                $apiResponse = apiRequest('dockerStopContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'dockerStopContainer:' . json_encode($apiResponse));

                $apiResponse = apiRequest('dockerRemoveContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'dockerRemoveContainer:' . json_encode($apiResponse));

                $apiResponse = apiRequest('dockerUpdateContainer', [], ['inspect' => $inspectImage]);
                logger(UI_LOG, 'dockerUpdateContainer:' . json_encode($apiResponse));
                $update         = $apiResponse['response']['docker'];
                $updateResult   = 'failed';

                if (strlen($update['Id']) == 64) {
                    $inspectImage   = apiRequest('dockerInspect', ['name' => $image, 'useCache' => false, 'format' => true]);
                    $inspectImage   = json_decode($inspectImage['response']['docker'], true);
                    list($cr, $imageDigest) = explode('@', $inspectImage[0]['RepoDigests'][0]);

                    $updateResult = 'complete';
                    $pullsFile[$_POST['hash']]  = [
                                                    'checked'       => time(),
                                                    'name'          => $container['Names'],
                                                    'regctlDigest'  => $imageDigest,
                                                    'imageDigest'   => $imageDigest
                                                ];

                    setServerFile('pull', $pullsFile);

                    $apiResponse = apiRequest('dockerStartContainer', [], ['name' => $container['Names']]);
                    logger(UI_LOG, 'dockerStartContainer:' . json_encode($apiResponse));
                }
            }

            $result = 'Container ' . $container['Names'] . ' update: '. $updateResult;
            break;
        case '8': //-- MOUNT COMPARE
            $result = $container['Names'] . '<br>';
            $result .= '<div class="ms-4">' . implode('<br>', $container['inspect'][0]['HostConfig']['Binds']) . '</div><br>';
            break;
        case '9': //-- REMOVE
            $apiResult = apiRequest('dockerStopContainer', [], ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerStopContainer:' . json_encode($apiResult));
            $apiResult = apiRequest('dockerRemoveContainer', [], ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerRemoveContainer:' . json_encode($apiResult));
            $result = 'Removed ' . $container['Names'] . '<br>';
            break;
        case '10': //-- GENERATE API CREATE
            $apiResult = apiRequest('dockerContainerCreateAPI', ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerContainerCreateAPI:' . json_encode($apiResult));
            $apiResult = json_decode($apiResult['response']['docker'], true);

            $result = $container['Names'] . '<br>';
            $result .= 'Endpoint: <code>' . $apiResult['endpoint'] . '</code><br>';
            $result .= '<pre>' . json_encode($apiResult['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
            break;
    }

    $return = renderContainerRow($_POST['hash'], 'json');
    $return['result'] = $result;

    logger(UI_LOG, 'massApplyContainerTrigger <-');
    echo json_encode($return);
}

if ($_POST['m'] == 'controlContainer') {
    $container = findContainerFromHash($_POST['hash']);

    if ($_POST['action'] == 'stop' || $_POST['action'] == 'restart') {
        apiRequest('dockerStopContainer', [], ['name' => $container['Names']]);
    }
    if ($_POST['action'] == 'start' || $_POST['action'] == 'restart') {
        apiRequest('dockerStartContainer', [], ['name' => $container['Names']]);
    }

    $return = renderContainerRow($_POST['hash'], 'json');

    echo json_encode($return);
}

if ($_POST['m'] == 'updateContainerRows') {
    $processList = apiRequest('dockerProcessList', ['format' => true]);
    $processList = json_decode($processList['response']['docker'], true);

    $update = [];
    foreach ($processList as $process) {
        $nameHash = md5($process['Names']);
        $update[] = ['hash' => $nameHash, 'row' => renderContainerRow($nameHash, 'json')];
    }

    echo json_encode($update);
}

if ($_POST['m'] == 'openContainerGroups') {
    $processList = apiRequest('dockerProcessList', ['format' => true]);
    $processList = json_decode($processList['response']['docker'], true);
    array_sort_by_key($processList, 'Names');

    ?>
    <div class="bg-secondary rounded h-100 p-4">
        <div class="table-responsive">
            <table class="table">
                <tr>
                    <td>Group</td>
                    <td>
                        <select class="form-select" id="groupSelection" onchange="loadContainerGroup()">
                            <option value="1">New Group</option>
                            <?php 
                            if ($settingsFile['containerGroups']) {
                                foreach ($settingsFile['containerGroups'] as $groupHash => $groupDetails) {
                                    ?><option value="<?= $groupHash ?>"><?= $groupDetails['name'] ?></option><?php
                                }
                            } 
                            ?>
                        </select>
                    </td>
                    <td>Name: <input id="groupName" type="text" class="form-control w-75 d-inline-block" placeholder="Group Name Here"></td>
                    <td style="display: none;" id="deleteGroupContainer">Delete: <input id="groupDelete" type="checkbox" class="form-check-input"></td>
                </tr>
            </table>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col"><input type="checkbox" class="form-check-input" onclick="$('.group-check').prop('checked', $(this).prop('checked'));"></th>
                        <th scope="col">Name</th>
                        <th scope="col">Existing Group</th>
                    </tr>
                </thead>
                <tbody id="containerGroupRows">
                <?php
                foreach ($processList as $process) {
                    $nameHash   = md5($process['Names']);
                    $inGroup    = '';
                    if ($settingsFile['containerGroups']) {
                        foreach ($settingsFile['containerGroups'] as $groupContainers) {
                            if (in_array($nameHash, $groupContainers['containers'])) {
                                $inGroup = $groupContainers['name'];
                                break;
                            }
                        }
                    }
                    ?>
                    <tr>
                        <th scope="row"><?= ($inGroup ? '' : '<input id="groupContainer-' . $nameHash . '" type="checkbox" class="form-check-input group-check">') ?></th>
                        <td><?= $process['Names'] ?></td>
                        <td><?= ($inGroup ? $inGroup : 'Not assigned') ?></td>
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
    $processList = apiRequest('dockerProcessList', ['format' => true]);
    $processList = json_decode($processList['response']['docker'], true);
    array_sort_by_key($processList, 'Names');

    foreach ($processList as $process) {
        $nameHash       = md5($process['Names']);
        $inGroup        = '';
        $inThisGroup    = false;
        if ($settingsFile['containerGroups']) {
            foreach ($settingsFile['containerGroups'] as $groupHash => $groupContainers) {
                if (in_array($nameHash, $groupContainers['containers'])) {
                    $inGroup = $groupContainers['name'];

                    if ($groupHash == $_POST['groupHash']) {
                        $inThisGroup = true;
                    }
                }
            }
        }
        ?>
        <tr>
            <th scope="row"><?= ($inGroup ? ($inThisGroup ? '<input id="groupContainer-' . $nameHash . '" type="checkbox" checked class="form-check-input group-check">' : '') : '<input id="groupContainer-' . $nameHash . '" type="checkbox" class="form-check-input group-check">') ?></th>
            <td><?= $process['Names'] ?></td>
            <td><?= ($inGroup ? $inGroup : 'Not assigned') ?></td>
        </tr>
        <?php
    }
}

if ($_POST['m'] == 'saveContainerGroup') {
    $groupName  = trim($_POST['name']);
    $groupHash  = $_POST['selection'] == '1' ? md5($groupName) : $_POST['selection'];
    $error      = '';

    if ($_POST['delete']) {
        unset($settingsFile['containerGroups'][$groupHash]);
    } else {
        if ($_POST['selection'] == '1' && is_array($settingsFile['containerGroups'])) {
            foreach ($settingsFile['containerGroups'] as $groupDetails) {
                if (strtolower($groupDetails['name']) == strtolower($groupName)) {
                    $error = 'A group with that name already exists';
                    break;
                }
            }
        }

        if (!$error) {
            $containers = [];
    
            foreach ($_POST as $key => $val) {
                if (!str_contains($key, 'groupContainer')) {
                    continue;
                }
        
                list($junk, $containerHash) = explode('-', $key);
                $containers[] = $containerHash;
            }
        
            $settingsFile['containerGroups'][$groupHash] = ['name' => $groupName, 'containers' => $containers];
        }
    }

    if (!$error) {
        setServerFile('settings', $settingsFile);
    }

    echo $error;
}