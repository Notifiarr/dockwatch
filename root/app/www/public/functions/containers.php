<?php

/*
----------------------------------
 ------  Created: 112923   ------
 ------  Austin Best	   ------
----------------------------------
*/

function renderContainerRow($nameHash, $return)
{
    global $pullsFile, $settingsFile, $processList, $dockerStats, $skipContainerUpdates, $groupHash;

    if (!$pullsFile) {
        $pullsFile = getServerFile('pull');
        if ($pullsFile['code'] != 200) {
            $apiError = $pullsFile['file'];
        }
        $pullsFile = $pullsFile['file'];
    }

    if (!$settingsFile) {
        $settingsFile = getServerFile('settings');
        if ($settingsFile['code'] != 200) {
            $apiError = $settingsFile['file'];
        }
        $settingsFile = $settingsFile['file'];
    }

    if (!$processList) {
        $processList = apiRequest('dockerProcessList', ['useCache' => false, 'format' => true]);
        logger(UI_LOG, 'dockerProcessList:' . json_encode($processList));
        $processList = json_decode($processList['response']['docker'], true);
    }

    if (!$dockerStats) {
        $dockerStats = apiRequest('dockerStats', ['useCache' => false]);
        logger(UI_LOG, 'dockerStats:' . json_encode($dockerStats));
        $dockerStats = json_decode($dockerStats['response']['docker'], true);
    }

    foreach ($processList as $process) {
        if (md5($process['Names']) == $nameHash) {
            break;
        }
    }

    $containerSettings  = $settingsFile['containers'][$nameHash];
    $logo               = getIcon($process['inspect']);
    $control            = $process['State'] == 'running' ? '<button type="button" class="btn btn-outline-success me-2" onclick="controlContainer(\'' . $nameHash . '\', \'restart\')">Restart</button> <button type="button" class="btn btn-outline-danger" onclick="controlContainer(\'' . $nameHash . '\', \'stop\')">Stop</button>' : '<button type="button" class="btn btn-outline-success" onclick="controlContainer(\'' . $nameHash . '\', \'start\')">Start</button>';

    $cpuUsage = floatval(str_replace('%', '', $process['stats']['CPUPerc']));
    if (intval($settingsFile['global']['cpuAmount']) > 0) {
        $cpuUsage = number_format(($cpuUsage / intval($settingsFile['global']['cpuAmount'])), 2) . '%';
    }

    $pullData = $pullsFile[$nameHash];
    $updateStatus = '<span class="text-danger">Unchecked</span>';
    if ($pullData) {
        $updateStatus = ($pullData['regctlDigest'] == $pullData['imageDigest']) ? '<span class="text-success">Up to date</span>' : '<span class="text-warning">Outdated</span>';
    }

    $health = 'Not setup';
    if (str_contains($process['Status'], 'healthy')) {
        $health = 'Healthy';
    }
    if (str_contains($process['Status'], 'unhealthy')) {
        $health = 'Unhealthy';
    }

    if ($return == 'json') {
        $return     = [
                        'control'   => $control,
                        'update'    => $updateStatus . '<br><span class="text-muted small-text" title="' . $pullData['imageDigest'] .'">' . truncateMiddle(str_replace('sha256:', '', $pullData['imageDigest']), 15) . '</span>',
                        'state'     => $process['State'],
                        'running'   => $process['RunningFor'],
                        'status'    => $process['Status'],
                        'cpu'       => $cpuUsage,
                        'cpuTitle'  => $process['CPUPerc'],
                        'mem'       => $process['MemPerc'],
                        'health'    => $health
                    ];

        return $return;
    } else {
        ?>
        <tr id="<?= $nameHash ?>" <?= ($groupHash ? 'class="' . $groupHash . '" style="display: none; background-color: #232833;"' : '' ) ?>>
            <td scope="row"><input id="massTrigger-<?= $nameHash ?>" data-name="<?= $process['Names'] ?>" type="checkbox" class="form-check-input containers-check <?= ($groupHash ? 'group-' . $groupHash . '-check' : '') ?>"></td>
            <td><?= ($logo ? '<img src="' . $logo . '" height="32" width="32" style="object-fit: contain; margin-top: 5px;">' : '') ?></td>
            <td><?= $process['Names'] ?><br><span class="text-muted small-text"><?= truncateMiddle(isDockerIO($process['inspect'][0]['Config']['Image']), 35) ?></span></td>
            <td id="<?= $nameHash ?>-control"><?= $control ?></td>
            <td id="<?= $nameHash ?>-update" title="Last pulled: <?= date('Y-m-d H:i:s', $pullData['checked']) ?>">
                <?= $updateStatus ?><br>
                <span class="text-muted small-text" title="<?= $pullData['imageDigest'] ?>"><?= truncateMiddle(str_replace('sha256:', '', $pullData['imageDigest']), 15) ?></span>
            </td>
            <td id="<?= $nameHash ?>-state"><?= $process['State'] ?></td>
            <td id="<?= $nameHash ?>-health"><?= $health ?></td>
            <td><span id="<?= $nameHash ?>-running"><?= $process['RunningFor'] ?></span><br><span id="<?= $nameHash ?>-status"><?= $process['Status'] ?></span></td>
            <td id="<?= $nameHash ?>-cpu" title="<?= $process['stats']['CPUPerc'] ?>"><?= $cpuUsage ?></td>
            <td id="<?= $nameHash ?>-mem"><?= $process['stats']['MemPerc'] ?></td>
            <td>
                <select id="containers-update-<?= $nameHash ?>" class="form-control container-updates">
                    <option <?= ($containerSettings['updates'] == 0 ? 'selected' : '') ?> value="0">Ignore</option>
                    <?php if (!skipContainerUpdates($process['inspect'][0]['Config']['Image'], $skipContainerUpdates)) { ?>
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
}

function skipContainerUpdates($container, $containers)
{
    foreach ($containers as $skip) {
        if (str_contains($container, $skip)) {
            return true;
        }
    }
    return false;
}