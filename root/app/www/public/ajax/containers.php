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
                            <th scope="col">Updates</th>
                            <th scope="col">State</th>
                            <th scope="col">Health</th>
                            <th scope="col">Mounts</th>
                            <th scope="col">CPU</th>
                            <th scope="col">Memory</th>
                            <th scope="col">
                                <span onclick="$('#container-updates-all').toggle()" class="text-info" style="cursor: pointer;">Updates</span>
                                <select id="container-updates-all" style="display: none;" class="form-select" onchange="$('.container-updates').val($(this).val())">
                                    <option value="0">Ignore</option>
                                    <option value="1">Auto update</option>
                                    <option value="2">Check for updates</option>
                                </select>
                            </th>
                            <th scope="col" width="10%">
                                <span onclick="$('#frequency-all-div').toggle()" class="text-info" style="cursor: pointer;">Frequency</span>
                                <i class="far fa-question-circle" style="cursor: pointer;" title="HELP!" onclick="containerFrequencyHelp()"></i>
                                <div id="frequency-all-div" class="m-0 p-0" style="display: none;">
                                    <input id="container-frequency-all" type="text" class="form-control d-inline-block w-75" value="<?= DEFAULT_CRON ?>">
                                    <i class="fas fa-angle-double-down" style="cursor: pointer;" onclick="$('.container-frequency').val($('#container-frequency-all').val())"></i>
                                </div>
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
                                    <td><input type="checkbox" class="form-check-input containers-check" onclick="$('.group-<?= $groupHash ?>-check').prop('checked', $(this).prop('checked'));"></td>
                                    <td><img src="<?= ABSOLUTE_PATH ?>images/container-group.png" height="32" width="32"></td>
                                    <td>
                                        <span class="text-info" style="cursor: pointer;" onclick="$('.<?= $groupHash ?>').toggle()"><?= $containerGroup['name'] ?></span><br>
                                        <span class="text-muted small-text">Containers: <?= count($containerGroup['containers']) ?></span>
                                    </td>
                                    <td colspan="4"></td>
                                    <td><?= $groupCPU ?>%</td>
                                    <td><?= $groupMemory ?>%</td>
                                    <td colspan="2"></td>
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
                                        <option value="1">Start</option>
                                        <option value="3">Stop</option>
                                        <option value="2">Restart</option>
                                        <option value="9">Remove</option>
                                        <option value="4">Pull</option>
                                        <option value="7">Update: Apply</option>
                                        <option value="11">Update: Check</option>
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

        list($minute, $hour, $dom, $month, $dow) = explode(' ', $_POST['containers-frequency-' . $hash]);
        $frequency = '0 ' . $hour . ' ' . $dom . ' ' . $month . ' ' . $dow;

        try {
            $cron = Cron\CronExpression::factory($frequency);
        } catch (Exception $e) {
            $frequency = DEFAULT_CRON;
        }

        $newSettings[$hash]    = [
                                    'updates'   => $val,
                                    'frequency' => $frequency
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
            logger(UI_LOG, 'dockerStartContainer:' . json_encode($apiResult, JSON_UNESCAPED_SLASHES));
            $result = 'Started ' . $container['Names'] . '<br>';
            break;
        case '2': //-- RESTART
            $apiResult = apiRequest('dockerStopContainer', [], ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerStopContainer:' . json_encode($apiResult));
            $apiResult = apiRequest('dockerStartContainer', [], ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerStartContainer:' . json_encode($apiResult, JSON_UNESCAPED_SLASHES));
            $result = 'Restarted ' . $container['Names'] . '<br>';
            break;
        case '3': //-- STOP
            $apiResult = apiRequest('dockerStopContainer', [], ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerStopContainer:' . json_encode($apiResult, JSON_UNESCAPED_SLASHES));
            $result = 'Stopped ' . $container['Names'] . '<br>';
            break;
        case '4': //-- PULL
            $image = isDockerIO($container['inspect'][0]['Config']['Image']);
            logger(UI_LOG, 'image:' . $image);

            $regctlDigest = trim(regctlCheck($image));

            $pull = apiRequest('dockerPullContainer', [], ['name' => $image]);
            logger(UI_LOG, 'dockerPullContainer:' . json_encode($pull, JSON_UNESCAPED_SLASHES));

            $inspectImage   = apiRequest('dockerInspect', ['name' => $image, 'useCache' => false, 'format' => true]);
            $inspectImage   = json_decode($inspectImage['response']['docker'], true);
            list($cr, $imageDigest) = explode('@', $inspectImage[0]['RepoDigests'][0]);
            logger(UI_LOG, 'dockerInspect:' . json_encode($inspectImage, JSON_UNESCAPED_SLASHES));

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
            logger(UI_LOG, 'dockerAutoRun:' . json_encode($autoRun, JSON_UNESCAPED_SLASHES));
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
            logger(UI_LOG, 'dockerAutoCompose:' . json_encode($autoCompose, JSON_UNESCAPED_SLASHES));
            $autoCompose    = $autoCompose['response']['docker'];
            $result         = '<pre>' . $autoCompose . '</pre>';
            break;
        case '7': //-- CHECK FOR UPDATES AND APPLY THEM
            $image = $container['inspect'][0]['Config']['Image'];
            if(skipContainerUpdates($image, $skipContainerUpdates)) {
                logger(UI_LOG, 'skipping ' . $container['Names'].' update');
                $updateResult = 'skipped';
            } else {
                $image = $container['inspect'][0]['Config']['Image'];
                logger(UI_LOG, 'image:' . $image);

                $apiResponse = apiRequest('dockerInspect', ['name' => $container['Names'], 'useCache' => false, 'format' => true]);
                logger(UI_LOG, 'dockerInspect:' . json_encode($apiResponse, JSON_UNESCAPED_SLASHES));
                $inspectImage = $apiResponse['response']['docker'];

                $apiResponse = apiRequest('dockerPullContainer', [], ['name' => $image]);
                logger(UI_LOG, 'dockerPullContainer:' . json_encode($apiResponse, JSON_UNESCAPED_SLASHES));

                $apiResponse = apiRequest('dockerStopContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'dockerStopContainer:' . json_encode($apiResponse, JSON_UNESCAPED_SLASHES));

                $apiResponse = apiRequest('dockerRemoveContainer', [], ['name' => $container['Names']]);
                logger(UI_LOG, 'dockerRemoveContainer:' . json_encode($apiResponse, JSON_UNESCAPED_SLASHES));

                $apiResponse = apiRequest('dockerUpdateContainer', [], ['inspect' => $inspectImage]);
                logger(UI_LOG, 'dockerUpdateContainer:' . json_encode($apiResponse, JSON_UNESCAPED_SLASHES));
                $update         = $apiResponse['response']['docker'];
                $updateResult   = 'failed';

                if (strlen($update['Id']) == 64) {
                    $inspectImage           = apiRequest('dockerInspect', ['name' => $image, 'useCache' => false, 'format' => true]);
                    $inspectImage           = json_decode($inspectImage['response']['docker'], true);
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
                    logger(UI_LOG, 'dockerStartContainer:' . json_encode($apiResponse, JSON_UNESCAPED_SLASHES));
                }
            }

            $result = 'Container ' . $container['Names'] . ' update: ' . $updateResult . '<br>';
            break;
        case '8': //-- MOUNT COMPARE
            $result = $container['Names'] . '<br>';
            $result .= '<div class="ms-4">' . implode('<br>', $container['inspect'][0]['HostConfig']['Binds']) . '</div><br>';
            break;
        case '9': //-- REMOVE
            $apiResult = apiRequest('dockerStopContainer', [], ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerStopContainer:' . json_encode($apiResult, JSON_UNESCAPED_SLASHES));
            $apiResult = apiRequest('dockerRemoveContainer', [], ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerRemoveContainer:' . json_encode($apiResult, JSON_UNESCAPED_SLASHES));
            $result = 'Removed ' . $container['Names'] . '<br>';
            break;
        case '10': //-- GENERATE API CREATE
            $apiResult = apiRequest('dockerContainerCreateAPI', ['name' => $container['Names']]);
            logger(UI_LOG, 'dockerContainerCreateAPI:' . json_encode($apiResult, JSON_UNESCAPED_SLASHES));
            $apiResult = json_decode($apiResult['response']['docker'], true);

            $result = $container['Names'] . '<br>';
            $result .= 'Endpoint: <code>' . $apiResult['endpoint'] . '</code><br>';
            $result .= '<pre>' . json_encode($apiResult['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
            break;
        case '11': //-- CHECK FOR UPDATES
            $image = $container['inspect'][0]['Config']['Image'];
            logger(UI_LOG, 'image:' . $image);

            $apiResponse = apiRequest('dockerInspect', ['name' => $image, 'useCache' => false]);
            logger(UI_LOG, 'dockerInspect:' . json_encode($apiResponse, JSON_UNESCAPED_SLASHES));
            $inspectImage = json_decode($apiResponse['response']['docker'], true);
            list($cr, $imageDigest) = explode('@', $inspectImage[0]['RepoDigests'][0]);

            $msg = 'Getting registry digest: ' . $image;
            logger(CRON_PULLS_LOG, $msg);
            $regctlDigest = trim(regctlCheck($image));

            if (str_contains($regctlDigest, 'Error')) {
                logger(CRON_PULLS_LOG, $regctlDigest, 'error');
                $result = 'Container ' . $container['Names'] . ': error fetching regctl<br>';
            } else {
                if ($regctlDigest != $imageDigest) {
                    $result = 'Container ' . $container['Names'] . ': update available<br>';

                    $pullsFile[md5($container['Names'])]    = [
                        'checked'       => time(),
                        'name'          => $container['Names'],
                        'regctlDigest'  => $regctlDigest,
                        'imageDigest'   => $imageDigest
                    ];

                    setServerFile('pull', $pullsFile);
                } else {
                    $result = 'Container ' . $container['Names'] . ': up to date<br>';
                }
            }
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

    if ($_POST['action'] == 'start' || $_POST['action'] == 'restart') {
        $return['length'] = 'Up 1 second';
    }

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

if ($_POST['m'] == 'openEditContainer') {
    $container      = findContainerFromHash($_POST['hash']);
    $inspectImage   = apiRequest('dockerInspect', ['name' => $container['Image'], 'useCache' => false, 'format' => true]);
    $inspectImage   = json_decode($inspectImage['response']['docker'], true);
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
                        <td colspan="2"><h4 class="text-primary">Container</h4></td>
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
                            <span class="text-muted">This will create a dockwatch label, should be a valid URL (Ex: http://dockwatch or http://10.1.0.1:9999)</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div style="float: left;"><h4 class="text-primary">Environment</h4></div>
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
                            <div style="float: left;"><h4 class="text-primary">Mounts</h4></div>
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
                            <div style="float: left;"><h4 class="text-primary">Labels</h4></div>
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
                                    <div>Value: <div style="float: right; width: 80%;"><input type="text" class="form-control d-inline-block" value="<?= $value ?>"></div></div><br>
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
