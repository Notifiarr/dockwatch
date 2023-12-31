<?php

/*
----------------------------------
 ------  Created: 112923   ------
 ------  Austin Best	   ------
----------------------------------
*/

function renderContainerRow($nameHash, $return)
{
    global $pullsFile, $settingsFile, $processList, $skipContainerUpdates, $groupHash;

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

    foreach ($processList as $process) {
        if (md5($process['Names']) == $nameHash) {
            break;
        }
    }

    $containerSettings  = $settingsFile['containers'][$nameHash];
    $logo               = getIcon($process['inspect']);

    if ($process['State'] == 'running') {
        $control = '<i class="fas fa-sync-alt text-success container-restart-btn" title="Restart" style="cursor: pointer;" onclick="controlContainer(\'' . $nameHash . '\', \'restart\')"></i><br>';
        $control .= '<i class="fas fa-power-off text-danger container-stop-btn" title="Stop" style="cursor: pointer;" onclick="controlContainer(\'' . $nameHash . '\', \'stop\')"></i>';
    } else {
        $control = '<i class="fas fa-play text-success container-start-btn" title="Start" style="cursor: pointer;" onclick="controlContainer(\'' . $nameHash . '\', \'start\')"></i>';
    }
 
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
    if (str_contains($process['Status'], 'health:')) {
        $health = 'Waiting';
    }

    if (str_contains($process['Status'], 'Exit')) {
        list($exitMsg, $time) = explode(')', $process['Status']);
    } else {
        list($time, $healthMsg) = explode('(', $process['Status']);
    }
    $time   = str_replace('ago', '', $time);
    $parts  = explode(' ', $time);

    $length = [];
    foreach ($parts as $timePart) {
        if (is_numeric($timePart) && empty($length)) {
            continue;
        }
        $length[] = trim($timePart);
    }
    $length = implode(' ', $length);

    $mountList = '';
    if ($process['inspect'][0]['Mounts']) {
        $mounts = [];

        foreach ($process['inspect'][0]['Mounts'] as $mount) {
            if ($mount['Type'] != 'bind') {
                continue;
            }

            $arrow = '&harr;';
            if ($mount['Mode'] == 'ro') {
                $arrow = '&larr;';
            }

            $mounts[] = $mount['Destination'] . ' ' . $arrow . ' ' . $mount['Source'] . ($mount['Mode'] ? ':' . $mount['Mode'] : '');
        }

        if ($mounts) {
            $mountList = '<i class="far fa-minus-square" style="cursor: pointer; display: none;" id="hide-mount-btn-' . $nameHash . '" onclick="hideContainerMounts(\'' . $nameHash . '\')"></i><i class="far fa-plus-square" style="cursor: pointer;" id="show-mount-btn-' . $nameHash . '" onclick="showContainerMounts(\'' . $nameHash . '\')"></i> ';
            $mountList .= '<span id="mount-list-preview-' . $nameHash . '">' . truncateMiddle($mounts[0], 40) . '</span><br>';
            $mountList .= '<div id="mount-list-full-' . $nameHash . '" style="display: none;">';
            $mountList .= implode('<br>', $mounts);
            $mountList .= '</div>';
        }
    }

    if ($return == 'json') {
        $return     = [
                        'control'   => $control,
                        'update'    => $updateStatus . '<br><span class="text-muted small-text" title="' . $pullData['imageDigest'] .'">' . truncateMiddle(str_replace('sha256:', '', $pullData['imageDigest']), 15) . '</span>',
                        'state'     => $process['State'],
                        'mounts'    => $mountList,
                        'length'    => $length,
                        'cpu'       => $cpuUsage,
                        'cpuTitle'  => $process['stats']['CPUPerc'],
                        'mem'       => $process['stats']['MemPerc'],
                        'health'    => $health
                    ];

        return $return;
    } else {
        ?>
        <tr id="<?= $nameHash ?>" <?= ($groupHash ? 'class="' . $groupHash . '" style="display: none; background-color: #232833;"' : '' ) ?>>
            <td scope="row"><input id="massTrigger-<?= $nameHash ?>" data-name="<?= $process['Names'] ?>" type="checkbox" class="form-check-input containers-check <?= ($groupHash ? 'group-' . $groupHash . '-check' : '') ?>"></td>
            <td><?= ($logo ? '<img src="' . $logo . '" height="32" width="32" style="object-fit: contain; margin-top: 5px;">' : '') ?></td>
            <td>
                <div class="row m-0 p-0">
                    <div class="col-sm-1" id="<?= $nameHash ?>-control"><?= $control ?></div>
                    <div class="col-sm-10">
                        <?= $process['Names'] ?><br>
                        <span class="text-muted small-text" title="<?= isDockerIO($process['inspect'][0]['Config']['Image']) ?>"><?= truncateMiddle(isDockerIO($process['inspect'][0]['Config']['Image']), 25) ?></span>
                    </div>
                </div>
            </td>
            <td id="<?= $nameHash ?>-update" title="Last pulled: <?= date('Y-m-d H:i:s', $pullData['checked']) ?>">
                <?= $updateStatus ?><br>
                <span class="text-muted small-text" title="<?= $pullData['imageDigest'] ?>"><?= truncateMiddle(str_replace('sha256:', '', $pullData['imageDigest']), 15) ?></span>
            </td>
            <td>
                <span id="<?= $nameHash ?>-state"><?= $process['State'] ?></span><br>
                <span class="text-muted small-text" id="<?= $nameHash ?>-length"><?= $length ?></span>
            </td>
            <td id="<?= $nameHash ?>-health"><?= $health ?></td>
            <td id="<?= $nameHash ?>-mounts-td">
                <span id="<?= $nameHash ?>-mounts" class="small-text"><?= $mountList ?></span>
            </td>
            <td id="<?= $nameHash ?>-cpu" title="<?= $process['stats']['CPUPerc'] ?>"><?= $cpuUsage ?></td>
            <td id="<?= $nameHash ?>-mem"><?= $process['stats']['MemPerc'] ?></td>
            <td id="<?= $nameHash ?>-update-td">
                <select id="containers-update-<?= $nameHash ?>" class="form-control container-updates">
                    <option <?= ($containerSettings['updates'] == 0 ? 'selected' : '') ?> value="0">Ignore</option>
                    <?php if (!skipContainerUpdates($process['inspect'][0]['Config']['Image'], $skipContainerUpdates)) { ?>
                    <option <?= ($containerSettings['updates'] == 1 ? 'selected' : '') ?> value="1">Auto update</option>
                    <?php } ?>
                    <option <?= ($containerSettings['updates'] == 2 ? 'selected' : '') ?> value="2">Check for updates</option>
                </select>
            </td>
            <td id="<?= $nameHash ?>-frequency-td">
                <select id="containers-frequency-<?= $nameHash ?>" class="form-control container-frequency">
                    <option <?= ($containerSettings['frequency'] == '6h' ? 'selected' : '') ?> value="6h">6h</option>
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
            <td id="<?= $nameHash ?>-hour-td">
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