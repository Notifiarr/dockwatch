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
    } elseif (str_contains($process['Status'], 'unhealthy')) {
        $health = 'Unhealthy';
    } elseif (str_contains($process['Status'], 'health:')) {
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
                        <span id="menu-<?= $nameHash ?>" style="cursor: pointer;" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><?= $process['Names'] ?></span>
                        <ul class="dropdown-menu dropdown-menu-dark p-2" role="menu" aria-labelledby="menu-<?= $nameHash ?>">
                            <li><i class="fas fa-tools fa-fw text-muted me-1"></i> <a onclick="openEditContainer('<?= $nameHash ?>')" tabindex="-1" href="#" class="text-white">Edit</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-submenu">
                                <i class="fas fa-ellipsis-v fa-fw text-muted me-1"></i> <a tabindex="-1" href="#" class="text-white">Actions</a>
                                <ul class="dropdown-menu dropdown-menu-dark p-2">
                                    <li><i class="fas fa-cloud-download-alt fa-fw text-muted me-1"></i> <a onclick="applyContainerAction('<?= $nameHash ?>', 4)" tabindex="-1" href="#" class="text-white">Pull</a></li>
                                    <li><i class="fas fa-trash-alt fa-fw text-muted me-1"></i> <a onclick="applyContainerAction('<?= $nameHash ?>', 9)" tabindex="-1" href="#" class="text-white">Remove</a></li>
                                    <li><i class="fas fa-cloud-upload-alt fa-fw text-muted me-1"></i> <a onclick="applyContainerAction('<?= $nameHash ?>', 7)" tabindex="-1" href="#" class="text-white">Update: Apply</a></li>
                                    <li><i class="fas fa-cloud fa-fw text-muted me-1"></i> <a onclick="applyContainerAction('<?= $nameHash ?>', 11)" tabindex="-1" href="#" class="text-white">Update: Check</a></li>
                                </ul>
                            </li>
                        </ul>
                        <br><span class="text-muted small-text" title="<?= isDockerIO($process['inspect'][0]['Config']['Image']) ?>"><?= truncateMiddle(isDockerIO($process['inspect'][0]['Config']['Image']), 25) ?></span>
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
                <select id="containers-update-<?= $nameHash ?>" class="form-select container-updates">
                    <option <?= ($containerSettings['updates'] == 0 ? 'selected' : '') ?> value="0">Ignore</option>
                    <?php if (!skipContainerUpdates($process['inspect'][0]['Config']['Image'], $skipContainerUpdates)) { ?>
                    <option <?= ($containerSettings['updates'] == 1 ? 'selected' : '') ?> value="1">Auto update</option>
                    <?php } ?>
                    <option <?= ($containerSettings['updates'] == 2 ? 'selected' : '') ?> value="2">Check for updates</option>
                </select>
            </td>
            <td id="<?= $nameHash ?>-frequency-td">
                <?php
                ?>
                <input type="text" class="form-control container-frequency" id="containers-frequency-<?= $nameHash ?>" value="<?= $containerSettings['frequency'] ?>">
                <?php
                //-- OLD FREQUENCY SETTINGS
                if (strlen($containerSettings['frequency']) > 3) {
                    try {
                        $cron = Cron\CronExpression::factory($containerSettings['frequency']);
                        $display = $cron->getNextRunDate()->format('Y-m-d H:i:s');
                    } catch (Exception $e) {
                        $display = 'Invalid cron syntax';
                    }

                    echo '<span class="text-muted small-text">' . $display . '</span>';
                }
                ?>
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