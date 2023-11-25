<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $pulls = is_array($pulls) ? $pulls : json_decode($pulls, true);
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
                            <th scope="col">Update</th>
                            <th scope="col">State</th>
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
                        if ($settings['containerGroups']) {
                            foreach ($settings['containerGroups'] as $groupHash => $containerGroup) {
                                $groupCPU = $groupMemory = 0;
                                foreach ($processList as $process) {
                                    $nameHash = md5($process['Names']);

                                    if (in_array($nameHash, $containerGroup['containers'])) {
                                        $memUsage = floatval(str_replace('%', '', $process['stats']['MemPerc']));
                                        $groupMemory += $memUsage;

                                        $cpuUsage = floatval(str_replace('%', '', $process['stats']['CPUPerc']));
                                        if (intval($settings['global']['cpuAmount']) > 0) {
                                            $cpuUsage = number_format(($cpuUsage / intval($settings['global']['cpuAmount'])), 2);
                                        }
                                        $groupCPU += $cpuUsage;
                                    }
                                }
                                ?>
                                <tr id="<?= $groupHash ?>">
                                    <th scope="row"><input type="checkbox" class="form-check-input containers-check" onclick="$('.group-<?= $groupHash ?>-check').prop('checked', $(this).prop('checked'));"></th>
                                    <td><img src="<?= ABSOLUTE_PATH ?>images/container-group.png" height="32" width="32"></td>
                                    <td><span class="text-info" style="cursor: pointer;" onclick="$('.<?= $groupHash ?>').toggle()"><?= $containerGroup['name'] ?></span><br><span class="text-muted small-text">Containers: <?= count($containerGroup['containers']) ?></span></td>
                                    <td colspan="4"></td>
                                    <td><?= $groupCPU ?>%</td>
                                    <td><?= $groupMemory ?>%</td>
                                    <td colspan="3"></td>
                                </tr>
                                <?php

                                foreach ($containerGroup['containers'] as $containerHash) {
                                    foreach ($processList as $process) {
                                        $nameHash = md5($process['Names']);
    
                                        if ($nameHash == $containerHash) {
                                            $containerSettings  = $settings['containers'][$nameHash];
                                            $logo               = getIcon($process['inspect']);
                                            $control            = $process['State'] == 'running' ? '<button type="button" class="btn btn-outline-success me-2" onclick="controlContainer(\'' . $nameHash . '\', \'restart\')">Restart</button> <button type="button" class="btn btn-outline-danger" onclick="controlContainer(\'' . $nameHash . '\', \'stop\')">Stop</button>' : '<button type="button" class="btn btn-outline-success" onclick="controlContainer(\'' . $nameHash . '\', \'start\')">Start</button>';
                
                                            $cpuUsage = floatval(str_replace('%', '', $process['stats']['CPUPerc']));
                                            if (intval($settings['global']['cpuAmount']) > 0) {
                                                $cpuUsage = number_format(($cpuUsage / intval($settings['global']['cpuAmount'])), 2) . '%';
                                            }
                                            $pullData = $pulls[$nameHash];
                                            $updateStatus = '<span class="text-danger">Unknown</span>';
                                            if ($pullData) {
                                                $updateStatus = ($pullData['image'] == $pullData['container']) ? '<span class="text-success">Updated</span>' : '<span class="text-warning">Outdated</span>';
                                            }
                                            ?>
                                            <tr id="<?= $nameHash ?>" class="<?= $groupHash ?>" style="display: none;">
                                                <th scope="row"><input id="massTrigger-<?= $nameHash ?>" type="checkbox" class="form-check-input containers-check group-<?= $groupHash ?>-check"></th>
                                                <td><?= ($logo ? '<img src="' . $logo . '" height="32" width="32">' : '') ?></td>
                                                <td><?= $process['Names'] ?><br><span class="text-muted small-text"><?= truncateMiddle($process['inspect'][0]['Config']['Image'], 35) ?></span></td>
                                                <td id="<?= $nameHash ?>-control"><?= $control ?></td>
                                                <td id="<?= $nameHash ?>-update" title="Last pulled: <?= date('Y-m-d H:i:s', $pullData['checked']) ?>"><?= $updateStatus ?></td>
                                                <td id="<?= $nameHash ?>-state"><?= $process['State'] ?></td>
                                                <td><span id="<?= $nameHash ?>-running"><?= $process['RunningFor'] ?></span><br><span id="<?= $nameHash ?>-status"><?= $process['Status'] ?></span></td>
                                                <td id="<?= $nameHash ?>-cpu" title="<?= $process['stats']['CPUPerc'] ?>"><?= $cpuUsage ?></td>
                                                <td id="<?= $nameHash ?>-mem"><?= $process['stats']['MemPerc'] ?></td>
                                                <td>
                                                    <select id="containers-update-<?= $nameHash ?>" class="form-control container-updates">
                                                        <option <?= ($containerSettings['updates'] == 0 ? 'selected' : '') ?> value="0">Ignore</option>
                                                        <?php if (strpos($process['inspect'][0]['Config']['Image'], 'dockwatch') === false) { ?>
                                                        <option <?= ($containerSettings['updates'] == 1 ? 'selected' : '') ?> value="1">Auto update</option>
                                                        <?php } ?>
                                                        <option <?= ($containerSettings['updates'] == 2 ? 'selected' : '') ?> value="2">Check for updates</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select id="containers-frequency-<?= $nameHash ?>" class="form-control container-frequency">
                                                        <option <?= ($containerSettings['frequency'] == '12h' ? 'selected' : '') ?> value="12h">12h</option>
                                                        <option <?= ($containerSettings['frequency'] == '1d' ? 'selected' : '') ?> value="1d">1d</option>
                                                        <option <?= ($containerSettings['frequency'] == '2d' ? 'selected' : '') ?> value="2d">2d</option>
                                                        <option <?= ($containerSettings['frequency'] == '3d' ? 'selected' : '') ?> value="3d">3d</option>
                                                        <option <?= ($containerSettings['frequency'] == '4d' ? 'selected' : '') ?> value="4d">4d</option>
                                                        <option <?= ($containerSettings['frequency'] == '5d' ? 'selected' : '') ?> value="5d">5d</option>
                                                        <option <?= ($containerSettings['frequency'] == '6d' ? 'selected' : '') ?> value="6d">6d</option>
                                                        <option <?= ($containerSettings['frequency'] == '1w' ? 'selected' : '') ?> value="1w">1w</option>
                                                        <option <?= ($containerSettings['frequency'] == '2w' ? 'selected' : '') ?> value="2w">2w</option>
                                                        <option <?= ($containerSettings['frequency'] == '3w' ? 'selected' : '') ?> value="3w">3w</option>
                                                        <option <?= ($containerSettings['frequency'] == '1m' ? 'selected' : '') ?> value="1m">1m</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select id="containers-hour-<?= $nameHash ?>" class="form-control container-hour">
                                                    <?php
                                                    for ($h = 0; $h <= 23; $h++) {
                                                        ?><option <?= ($containerSettings['hour'] == $h ? 'selected' : '') ?> value="<?= $h ?>"><?= $h ?></option><?php
                                                    }
                                                    ?>
                                                    </select>
                                                </td>
                                            </tr>
                                            <?php
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        //-- NON GROUPS
                        foreach ($processList as $process) {
                            $inGroup    = false;
                            $nameHash   = md5($process['Names']);
                            if ($settings['containerGroups']) {
                                foreach ($settings['containerGroups'] as $containerGroup) {
                                    if (in_array($nameHash, $containerGroup['containers'])) {
                                        $inGroup = true;
                                        break;
                                    }
                                }
                            }

                            if ($inGroup) {
                                continue;
                            }

                            $containerSettings  = $settings['containers'][$nameHash];
                            $logo               = getIcon($process['inspect']);
                            $control            = $process['State'] == 'running' ? '<button type="button" class="btn btn-outline-success me-2" onclick="controlContainer(\'' . $nameHash . '\', \'restart\')">Restart</button> <button type="button" class="btn btn-outline-danger" onclick="controlContainer(\'' . $nameHash . '\', \'stop\')">Stop</button>' : '<button type="button" class="btn btn-outline-success" onclick="controlContainer(\'' . $nameHash . '\', \'start\')">Start</button>';

                            $cpuUsage = floatval(str_replace('%', '', $process['stats']['CPUPerc']));
                            if (intval($settings['global']['cpuAmount']) > 0) {
                                $cpuUsage = number_format(($cpuUsage / intval($settings['global']['cpuAmount'])), 2) . '%';
                            }
                            $pullData = $pulls[$nameHash];
                            $updateStatus = '<span class="text-danger">Unknown</span>';
                            if ($pullData) {
                                $updateStatus = ($pullData['image'] == $pullData['container']) ? '<span class="text-success">Updated</span>' : '<span class="text-warning">Outdated</span>';
                            }
                            ?>
                            <tr id="<?= $nameHash ?>">
                                <th scope="row"><input id="massTrigger-<?= $nameHash ?>" type="checkbox" class="form-check-input containers-check"></th>
                                <td><?= ($logo ? '<img src="' . $logo . '" height="32" width="32">' : '') ?></td>
                                <td><?= $process['Names'] ?><br><span class="text-muted small-text"><?= truncateMiddle($process['inspect'][0]['Config']['Image'], 35) ?></span></td>
                                <td id="<?= $nameHash ?>-control"><?= $control ?></td>
                                <td id="<?= $nameHash ?>-update" title="Last pulled: <?= date('Y-m-d H:i:s', $pullData['checked']) ?>"><?= $updateStatus ?></td>
                                <td id="<?= $nameHash ?>-state"><?= $process['State'] ?></td>
                                <td><span id="<?= $nameHash ?>-running"><?= $process['RunningFor'] ?></span><br><span id="<?= $nameHash ?>-status"><?= $process['Status'] ?></span></td>
                                <td id="<?= $nameHash ?>-cpu" title="<?= $process['stats']['CPUPerc'] ?>"><?= $cpuUsage ?></td>
                                <td id="<?= $nameHash ?>-mem"><?= $process['stats']['MemPerc'] ?></td>
                                <td>
                                    <select id="containers-update-<?= $nameHash ?>" class="form-control container-updates">
                                        <option <?= ($containerSettings['updates'] == 0 ? 'selected' : '') ?> value="0">Ignore</option>
                                        <?php if (strpos($process['inspect'][0]['Config']['Image'], 'dockwatch') === false) { ?>
                                        <option <?= ($containerSettings['updates'] == 1 ? 'selected' : '') ?> value="1">Auto update</option>
                                        <?php } ?>
                                        <option <?= ($containerSettings['updates'] == 2 ? 'selected' : '') ?> value="2">Check for updates</option>
                                    </select>
                                </td>
                                <td>
                                    <select id="containers-frequency-<?= $nameHash ?>" class="form-control container-frequency">
                                        <option <?= ($containerSettings['frequency'] == '12h' ? 'selected' : '') ?> value="12h">12h</option>
                                        <option <?= ($containerSettings['frequency'] == '1d' ? 'selected' : '') ?> value="1d">1d</option>
                                        <option <?= ($containerSettings['frequency'] == '2d' ? 'selected' : '') ?> value="2d">2d</option>
                                        <option <?= ($containerSettings['frequency'] == '3d' ? 'selected' : '') ?> value="3d">3d</option>
                                        <option <?= ($containerSettings['frequency'] == '4d' ? 'selected' : '') ?> value="4d">4d</option>
                                        <option <?= ($containerSettings['frequency'] == '5d' ? 'selected' : '') ?> value="5d">5d</option>
                                        <option <?= ($containerSettings['frequency'] == '6d' ? 'selected' : '') ?> value="6d">6d</option>
                                        <option <?= ($containerSettings['frequency'] == '1w' ? 'selected' : '') ?> value="1w">1w</option>
                                        <option <?= ($containerSettings['frequency'] == '2w' ? 'selected' : '') ?> value="2w">2w</option>
                                        <option <?= ($containerSettings['frequency'] == '3w' ? 'selected' : '') ?> value="3w">3w</option>
                                        <option <?= ($containerSettings['frequency'] == '1m' ? 'selected' : '') ?> value="1m">1m</option>
                                    </select>
                                </td>
                                <td>
                                    <select id="containers-hour-<?= $nameHash ?>" class="form-control container-hour">
                                    <?php
                                    for ($h = 0; $h <= 23; $h++) {
                                        ?><option <?= ($containerSettings['hour'] == $h ? 'selected' : '') ?> value="<?= $h ?>"><?= $h ?></option><?php
                                    }
                                    ?>
                                    </select>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4">
                                With selected: 
                                <select id="massContainerTrigger" class="form-control d-inline-block w-50">
                                    <option value="0">-- Select option --</option>
                                    <optgroup label="Control">
                                        <option value="1">Start</option>
                                        <option value="2">Restart</option>
                                        <option value="3">Stop</option>
                                        <option value="4">Pull</option>
                                        <option value="7">Update</option>
                                    </optgroup>
                                    <optgroup label="Information">
                                        <option value="8">Mount compare</option>
                                        <option value="5">Generate docker run</option>
                                        <option value="6">Generate docker-compose</option>
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
        if (strpos($key, '-update-') === false) {
            continue;
        }

        $hash = str_replace('containers-update-', '', $key);
        $newSettings[$hash]    = [
                                    'updates'   => $val,
                                    'frequency' => $_POST['containers-frequency-' . $hash],
                                    'hour'      => $_POST['containers-hour-' . $hash]
                                ];
    }

    $settings['containers'] = $newSettings;
    setFile(SETTINGS_FILE, $settings);
}

if ($_POST['m'] == 'massApplyContainerTrigger') {
    $pulls      = getFile(PULL_FILE);
    $pulls      = is_array($pulls) ? $pulls : json_decode($pulls, true); 
    $container  = findContainerFromHash($_POST['hash']);

    switch ($_POST['trigger']) {
        case '1': //-- Start
            dockerStartContainer($container['Names']);
            $result = 'Started ' . $container['Names'] . '<br>';
            break;
        case '2': //-- Restart
            dockerStopContainer($container['Names']);
            dockerStartContainer($container['Names']);
            $result = 'Restarted ' . $container['Names'] . '<br>';
            break;
        case '3': //-- Stop
            dockerStopContainer($container['Names']);
            $result = 'Stopped ' . $container['Names'] . '<br>';
            break;
        case '4': //-- Pull
            $image              = $container['inspect'][0]['Config']['Image'];
            $pull               = dockerPullContainer($image);
            $inspectContainer   = dockerInspect($container['Names'], false);
            $inspectContainer   = json_decode($inspectContainer, true);
            $inspectImage       = dockerInspect($image, false);
            $inspectImage       = json_decode($inspectImage, true);

            $pulls[md5($container['Names'])]    = [
                                                    'checked'   => time(),
                                                    'name'      => $container['Names'],
                                                    'image'     => $inspectImage[0]['Id'],
                                                    'container' => $inspectContainer[0]['Image']
                                                ];

            setFile(PULL_FILE, $pulls);
            $result = 'Pulled ' . $container['Names'] . '<br>';
            break;
        case '5': //-- GERNERATE RUN
            $run = dockerAutoRun($container['Names']);
            $result = 'docker run ' . $container['Names'] . '<br><pre>' . $run . '</pre>';
            break;
        case '6': //-- GENERATE COMPOSE
            $containerList  = '';
            $containers     = explode(',', $_POST['hash']);

            foreach ($containers as $selectedContainer) {
                $thisContainer  = findContainerFromHash($selectedContainer);
                $containerList .= $thisContainer['Names'] . ' ';
            }

            $result = '<pre>' . dockerAutoCompose(trim($containerList)) . '</pre>';
            break;
        case '7': //-- UPDATE
            if (strpos($image, 'dockwatch') !== false) {
                $updateResult = 'skipped';
            } else {
                $runCommand     = dockerAutoRun($container['Names']);
                $lines = explode("\n", $runCommand);
                foreach ($lines as $line) {
                    $newRun .= trim(str_replace('\\', '', $line)) . ' ';
                }
                $runCommand = $newRun;
                $stop           = dockerStopContainer($container['Names']);
                $remove         = dockerRemoveContainer($container['ID']);
                $update         = trim(dockerUpdateContainer($runCommand));
                $updateResult   = 'failed';

                if (strlen($update) == 64) {
                    $updateResult = 'complete';
                    $pulls[$_POST['hash']]  = [
                                                'checked'   => time(),
                                                'name'      => $container['Names'],
                                                'image'     => $update,
                                                'container' => $update
                                            ];

                    setFile(PULL_FILE, $pulls);
                }
            }

            $result = 'Container ' . $container['Names'] . ' update: '. $updateResult;
            break;
        case '8': //-- MOUNT COMPARE
            $result = $container['Names'] . '<br>';
            $result .= '<div class="ms-4">' . implode('<br>', $container['inspect'][0]['HostConfig']['Binds']) . '</div><br>';
            break;
    }

    $processList    = dockerProcessList(false);
    $processList    = json_decode($processList, true);
    $dockerStats    = dockerStats(false);
    $dockerStats    = json_decode($dockerStats, true);
    $containerProcess = $containerStats = [];

    if (is_array($container)) {
        foreach ($processList as $process) {
            if (!is_array($process)) {
                continue;
            }

            if ($process['Names'] == $container['Names']) {
                $containerProcess = $process;
                break;
            }
        }

        foreach ($dockerStats as $dockerStat) {
            if ($dockerStat['Name'] == $container['Names']) {
                $containerStats = $dockerStat;
                break;
            }
        }
    }

    $control = $containerProcess['State'] == 'running' ? '<button type="button" class="btn btn-outline-success me-2" onclick="controlContainer(\'' . $_POST['hash'] . '\', \'restart\')">Restart</button> <button type="button" class="btn btn-outline-danger" onclick="controlContainer(\'' . $_POST['hash'] . '\', \'stop\')">Stop</button>' : '<button type="button" class="btn btn-outline-success" onclick="controlContainer(\'' . $_POST['hash'] . '\', \'start\')">Start</button>';

    $pullData = $pulls[$_POST['hash']];
    $updateStatus = '<span class="text-danger">Unknown</span>';
    if ($pullData) {
        $updateStatus = ($pullData['image'] == $pullData['container']) ? '<span class="text-success">Updated</span>' : '<span class="text-warning">Outdated</span>';
    }

    $cpuUsage = floatval(str_replace('%', '', $containerStats['CPUPerc']));
    if (intval($settings['global']['cpuAmount']) > 0) {
        $cpuUsage = number_format(($cpuUsage / intval($settings['global']['cpuAmount'])), 2) . '%';
    }

    $return     = [
                    'control'   => $control,
                    'update'    => $updateStatus,
                    'state'     => $containerProcess['State'],
                    'running'   => $containerProcess['RunningFor'],
                    'status'    => $containerProcess['Status'],
                    'cpu'       => $cpuUsage,
                    'cpuTitle'  => $containerStats['CPUPerc'],
                    'mem'       => $containerStats['MemPerc'],
                    'result'    => $result
                ];

    echo json_encode($return);
}

if ($_POST['m'] == 'controlContainer') {
    $container = findContainerFromHash($_POST['hash']);

    if ($_POST['action'] == 'stop' || $_POST['action'] == 'restart') {
        dockerStopContainer($container['Names']);
    }
    if ($_POST['action'] == 'start' || $_POST['action'] == 'restart') {
        dockerStartContainer($container['Names']);
    }

    $processList        = dockerProcessList(false);
    $processList        = json_decode($processList, true);
    $containerProcess   = [];
    foreach ($processList as $process) {
        if ($process['Names'] == $container['Names']) {
            $containerProcess = $process;
            break;
        }
    }

    $dockerStats    = dockerStats(false);
    $dockerStats    = json_decode($dockerStats, true);
    $containerStats = [];
    foreach ($dockerStats as $dockerStat) {
        if ($dockerStat['Name'] == $container['Names']) {
            $containerStats = $dockerStat;
            break;
        }
    }

    $control = $containerProcess['State'] == 'running' ? '<button type="button" class="btn btn-outline-success me-2" onclick="controlContainer(\'' . $_POST['hash'] . '\', \'restart\')">Restart</button> <button type="button" class="btn btn-outline-danger" onclick="controlContainer(\'' . $_POST['hash'] . '\', \'stop\')">Stop</button>' : '<button type="button" class="btn btn-outline-success" onclick="controlContainer(\'' . $_POST['hash'] . '\', \'start\')">Start</button>';

    $pullData = $pulls[$_POST['hash']];
    $updateStatus = '<span class="text-danger">Unknown</span>';
    if ($pullData) {
        $updateStatus = ($pullData['image'] == $pullData['container']) ? '<span class="text-success">Updated</span>' : '<span class="text-warning">Outdated</span>';
    }

    $return     = [
                    'control'   => $control,
                    'update'    => $updateStatus,
                    'state'     => $containerProcess['State'],
                    'running'   => $containerProcess['RunningFor'],
                    'status'    => $containerProcess['Status'],
                    'cpu'       => $containerStats['CPUPerc'],
                    'mem'       => $containerStats['MemPerc'],
                ];

    echo json_encode($return);
}

if ($_POST['m'] == 'openContainerGroups') {
    $settings       = getFile(SETTINGS_FILE);
    $processList    = dockerProcessList();
    $processList    = json_decode($processList, true);
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
                            if ($settings['containerGroups']) {
                                foreach ($settings['containerGroups'] as $groupHash => $groupDetails) {
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
                    if ($settings['containerGroups']) {
                        foreach ($settings['containerGroups'] as $groupContainers) {
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
    $settings       = getFile(SETTINGS_FILE);
    $processList    = dockerProcessList();
    $processList    = json_decode($processList, true);
    array_sort_by_key($processList, 'Names');

    foreach ($processList as $process) {
        $nameHash       = md5($process['Names']);
        $inGroup        = '';
        $inThisGroup    = false;
        if ($settings['containerGroups']) {
            foreach ($settings['containerGroups'] as $groupHash => $groupContainers) {
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
    $settings   = getFile(SETTINGS_FILE);
    $groupName  = trim($_POST['name']);
    $groupHash  = $_POST['selection'] == '1' ? md5($groupName) : $_POST['selection'];
    $error      = '';

    if ($_POST['delete']) {
        unset($settings['containerGroups'][$groupHash]);
    } else {
        if ($_POST['selection'] == '1' && is_array($settings['containerGroups'])) {
            foreach ($settings['containerGroups'] as $groupDetails) {
                if (strtolower($groupDetails['name']) == strtolower($groupName)) {
                    $error = 'A group with that name already exists';
                    break;
                }
            }
        }

        if (!$error) {
            $containers = [];
    
            foreach ($_POST as $key => $val) {
                if (strpos($key, 'groupContainer') === false) {
                    continue;
                }
        
                list($junk, $containerHash) = explode('-', $key);
                $containers[] = $containerHash;
            }
        
            $settings['containerGroups'][$groupHash] = ['name' => $groupName, 'containers' => $containers];
        }
    }

    if (!$error) {
        setFile(SETTINGS_FILE, $settings);
    }

    echo $error;
}