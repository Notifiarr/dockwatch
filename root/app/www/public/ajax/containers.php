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
                        foreach ($processList as $process) {
                            $nameHash           = md5($process['Names']);
                            $containerSettings  = $settings['containers'][$nameHash];
                            $logo               = $process['inspect'][0]['Config']['Labels']['net.unraid.docker.icon'];
                            $control            = $process['State'] == 'running' ? '<button type="button" class="btn btn-outline-success me-2" onclick="controlContainer(\'' . $nameHash . '\', \'restart\')">Restart</button> <button type="button" class="btn btn-outline-danger" onclick="controlContainer(\'' . $nameHash . '\', \'stop\')">Stop</button>' : '<button type="button" class="btn btn-outline-success" onclick="controlContainer(\'' . $nameHash . '\', \'start\')">Start</button>';

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
                                <td id="<?= $nameHash ?>-cpu"><?= $process['stats']['CPUPerc'] ?></td>
                                <td id="<?= $nameHash ?>-mem"><?= $process['stats']['MemPerc'] ?></td>
                                <td>
                                    <select id="containers-update-<?= $nameHash ?>" class="form-control container-updates">
                                        <option <?= ($containerSettings['updates'] == 0 ? 'selected' : '') ?> value="0">Ignore</option>
                                        <option <?= ($containerSettings['updates'] == 1 ? 'selected' : '') ?> value="1">Auto update</option>
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
                                    <option value="1">Start</option>
                                    <option value="2">Restart</option>
                                    <option value="3">Stop</option>
                                    <option value="4">Pull</option>
                                    <option value="5">Inspect -> Run</option>
                                    <option value="6">Inspect -> Compose</option>
                                </select>
                                <button type="button" class="btn btn-outline-info" onclick="massApplyContainerTrigger()">Apply</button>
                            </td>
                            <td colspan="7" align="right">
                                <button type="button" class="btn btn-info" onclick="saveContainerSettings()">Save Changes</button>
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
    $version    = '';

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
            $run        = dockerAutoCompose($container['Names']);
            $lines      = explode("\n", $run);
            $version    = $lines[count($lines) - 1];
            $run        = str_replace($version, '', $run);
            $result     = trim($run);
            break;
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
                    'result'    => $result,
                    'version'   => $version
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
