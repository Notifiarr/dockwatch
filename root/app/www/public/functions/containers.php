<?php

/*
----------------------------------
 ------  Created: 112923   ------
 ------  Austin Best	   ------
----------------------------------
*/

function renderContainerRow($nameHash, $return)
{
    global $pullsFile, $settingsFile, $processList, $skipContainerActions, $groupHash;

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

    $isDockwatch = false;
    $dockwatchWarning = '';
    if (isDockwatchContainer($process)) {
        $isDockwatch        = true;
        $dockwatchWarning   = ' <i class="fas fa-exclamation-circle text-danger" title="Dockwatch warning, click for more information" style="cursor: pointer;" onclick="dockwatchWarning()"></i>';
    }

    $skipActions = skipContainerActions($process['inspect'][0]['Config']['Image'], $skipContainerActions);

    $containerSettings  = $settingsFile['containers'][$nameHash];
    $logo               = getIcon($process['inspect']);

    $notificationIcon = $containerSettings['disableNotifications'] ? '<i id="disableNotifications-icon-' . $nameHash . '" class="fas fa-bell-slash text-muted" title="Notifications disabled for this container"></i> ' : '';

    if ($process['State'] == 'running') {
        $control = '<i style="'. ($skipActions ? 'display: none;' : '') .' cursor: pointer;" id="restart-btn-' . $nameHash . '" class="fas fa-sync-alt text-success container-restart-btn" title="Restart" onclick="$(\'#massTrigger-' . $nameHash . '\').prop(\'checked\', true); $(\'#massContainerTrigger\').val(2); massApplyContainerTrigger();"></i><br>';
        $control .= '<i style="'. ($skipActions ? 'display: none;' : '') .' cursor: pointer;" id="stop-btn-' . $nameHash . '" class="fas fa-power-off text-danger container-stop-btn" title="Stop" onclick="$(\'#massTrigger-' . $nameHash . '\').prop(\'checked\', true); $(\'#massContainerTrigger\').val(3); massApplyContainerTrigger();"></i>';
    } else {
        $control = '<i style="'. ($skipActions ? 'display: none;' : '') .' cursor: pointer;" id="start-btn-' . $nameHash . '" class="fas fa-play text-success container-start-btn" title="Start" onclick="$(\'#massTrigger-' . $nameHash . '\').prop(\'checked\', true); $(\'#massContainerTrigger\').val(1); massApplyContainerTrigger();"></i>';
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

    $restartUnhealthy       = $settingsFile['containers'][$nameHash]['restartUnhealthy'];
    $healthyRestartClass    = 'text-success';
    $healthyRestartText     = 'Auto restart when unhealthy';

    if (!$restartUnhealthy) {
        $healthyRestartClass    = 'text-warning';
        $healthyRestartText     = 'Not set to auto restart when unhealthy';
    }
    $usesHealth = false;
    $health = 'Not setup';
    if (str_contains($process['Status'], 'healthy')) {
        $usesHealth = true;
        $health     = 'Healthy <i class="fas fa-sync-alt ' . $healthyRestartClass . ' restartUnhealthy-icon-' . $nameHash . '" title="' . $healthyRestartText . '"></i>';
    } elseif (str_contains($process['Status'], 'unhealthy')) {
        $usesHealth = true;
        $health     = 'Unhealthy <i class="fas fa-sync-alt ' . $healthyRestartClass . ' restartUnhealthy-icon-' . $nameHash . '" title="' . $healthyRestartText . '"></i>';
    } elseif (str_contains($process['Status'], 'health:')) {
        $usesHealth = true;
        $health     = 'Waiting <i class="fas fa-sync-alt ' . $healthyRestartClass . ' restartUnhealthy-icon-' . $nameHash . '" title="' . $healthyRestartText . '"></i>';
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
        $version = '';
        foreach ($process['inspect'][0]['Config']['Labels'] as $label => $val) {
            if (str_contains($label, 'image.version')) {
                $version = $val;
                break;
            }
        }

        $networkDependencies = dockerContainerNetworkDependenices($process['ID'], $processList);
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
            $labelDependencies = dockerContainerLabelDependencies($process['Names'], $processList);
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

        $network = $process['inspect'][0]['HostConfig']['NetworkMode'];
        if (str_contains($network, ':')) {
            list($null, $containerId) = explode(':', $network);
            $network = 'container:' . findContainerFromId($containerId);
        }

        ?>
        <tr id="<?= $nameHash ?>" <?= ($groupHash ? 'class="' . $groupHash . ' container-group-row" style="display: none; background-color: #232833;"' : '' ) ?>>
            <td><input <?= ($isDockwatch ? 'attr-dockwatch="true"' : '') ?> id="massTrigger-<?= $nameHash ?>" data-name="<?= $process['Names'] ?>" type="checkbox" class="form-check-input containers-check <?= ($groupHash ? 'group-' . $groupHash . '-check' : '') ?>"></td>
            <td><?= ($logo ? '<img src="' . $logo . '" height="32" width="32" style="object-fit: contain; margin-top: 5px;">' : '') ?></td>
            <td>
                <div class="row m-0 p-0">
                    <div class="col-sm-1" id="<?= $nameHash ?>-control"><?= $control ?></div>
                    <div class="col-sm-10">
                        <span id="menu-<?= $nameHash ?>" style="cursor: pointer;" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><?= $notificationIcon . $process['Names'] ?></span>
                        <ul style="max-width: 200px" class="dropdown-menu dropdown-menu-dark p-2" role="menu" aria-labelledby="menu-<?= $nameHash ?>">
                            <li <?= ($skipActions ? 'class="d-none"' : '') ?>><i class="fas fa-tools fa-fw text-muted me-1"></i> <a onclick="openEditContainer('<?= $nameHash ?>')" tabindex="-1" href="#" class="text-white">Edit</a></li>

                            <li <?= ($skipActions ? 'class="d-none"' : '') ?>><hr class="dropdown-divider"></li>
                            <li class="dropdown-submenu">
                                <i class="fas fa-ellipsis-v fa-fw text-muted me-1"></i> <a tabindex="-1" href="#" class="text-white">Actions</a>
                                <ul class="dropdown-menu dropdown-menu-dark p-2" style="width: 250px;">
                                    <li><i class="far fa-file-alt fa-fw text-muted me-1"></i> <a onclick="containerLogs('<?= $process['Names'] ?>')" tabindex="-1" href="#" class="text-white">View logs</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><i class="fas fa-cloud-download-alt fa-fw text-muted me-1"></i> <a onclick="applyContainerAction('<?= $nameHash ?>', 4)" tabindex="-1" href="#" class="text-white">Pull</a></li>
                                    <li <?= ($skipActions ? 'class="d-none"' : '') ?>><i class="fas fa-trash-alt fa-fw text-muted me-1"></i> <a onclick="applyContainerAction('<?= $nameHash ?>', 9)" tabindex="-1" href="#" class="text-white">Remove</a></li>
                                    <li <?= ($skipActions ? 'class="d-none"' : '') ?>><i class="fas fa-cloud-upload-alt fa-fw text-muted me-1"></i> <a onclick="applyContainerAction('<?= $nameHash ?>', 7)" tabindex="-1" href="#" class="text-white">Update: Apply</a></li>
                                    <li><i class="fas fa-cloud fa-fw text-muted me-1"></i> <a onclick="applyContainerAction('<?= $nameHash ?>', 11)" tabindex="-1" href="#" class="text-white">Update: Check</a></li>
                                </ul>
                            </li>

                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-submenu">
                                <i class="fas fa-ellipsis-v fa-fw text-muted me-1"></i> <a tabindex="-1" href="#" class="text-white">Options</a>
                                <ul class="dropdown-menu dropdown-menu-dark p-2" style="width: 300px;">
                                <li><input <?= ($settingsFile['containers'][$nameHash]['disableNotifications'] ? 'checked' : '') ?> type="checkbox" class="form-check-input" id="disableNotifications-<?= $nameHash ?>" onclick="updateContainerOption('disableNotifications', '<?= $nameHash ?>')"> Disable notifications</li>
                                    <li><input <?= ($skipActions == SKIP_FORCE ? 'disabled checked' : ($settingsFile['containers'][$nameHash]['blacklist'] ? 'checked' : '')) ?> type="checkbox" class="form-check-input" id="blacklist-<?= $nameHash ?>" onclick="updateContainerOption('blacklist', '<?= $nameHash ?>')"> Blacklist (no state changes)</li>
                                    <?php if ($usesHealth) { ?>
                                    <li><input <?= ($skipActions ? 'disabled' : ($settingsFile['containers'][$nameHash]['restartUnhealthy'] ? 'checked' : '')) ?> type="checkbox" class="form-check-input" id="restartUnhealthy-<?= $nameHash ?>" onclick="updateContainerOption('restartUnhealthy', '<?= $nameHash ?>')"> Restart when unhealthy</li>
                                    <?php } ?>
                                </ul>
                            </li>

                            <li><hr class="dropdown-divider"></li>
                            <li class="text-center mb-1">Info</li>
                            <?php if ($version) { ?>
                            <li class="ms-1 small-text"><span class="small-text">Version:</span> <?= $version ?></li>
                            <?php } ?>
                            <li class="ms-1 small-text"><span class="small-text">Size:</span> <?= $process['size'] ?></li>
                            <li class="ms-1 small-text"><span class="small-text">Network:</span> <?= $network ?></li>
                            <?php if ($networkDependencies) { ?>
                            <li class="ms-1 small-text"><span class="small-text">Dependencies:</span> <?= $networkDependencyList ?></li>
                            <?php } elseif ($labelDependencies) { ?>
                                <li class="ms-1 small-text"><span class="small-text">Dependencies:</span> <?= $labelDependencyList ?></li>
                            <?php } ?>
                        </ul>
                        <?= $dockwatchWarning ?>
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
                <select id="containers-update-<?= $nameHash ?>" class="form-select container-updates" style="min-width: 150px;">
                    <option <?= ($containerSettings['updates'] == 0 ? 'selected' : '') ?> value="0">Ignore</option>
                    <?php if ($isDockwatch || !skipContainerActions($process['inspect'][0]['Config']['Image'], $skipContainerActions)) { ?>
                    <option <?= ($containerSettings['updates'] == 1 ? 'selected' : '') ?> value="1">Auto update</option>
                    <?php } ?>
                    <option <?= ($containerSettings['updates'] == 2 ? 'selected' : '') ?> value="2">Check for updates</option>
                </select>
            </td>
            <td id="<?= $nameHash ?>-frequency-td" style="width: 135px;">
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

function skipContainerActions($container, $containers)
{
    global $settingsFile;

    $settingsFile = $settingsFile ? $settingsFile : getServerFile('settings');

    if ($settingsFile['containers']) {
        foreach ($settingsFile['containers'] as $containerHash => $containerSettings) {
            if ($containerSettings['blacklist']) {
                $containerState = findContainerFromHash($containerHash);

                if (str_contains($containerState['Image'], $container)) {
                    return SKIP_OPTIONAL;
                }
            }
        }
    }

    foreach ($containers as $skip) {
        if (str_contains($container, $skip)) {
            return SKIP_FORCE;
        }
    }

    return SKIP_OFF;
}