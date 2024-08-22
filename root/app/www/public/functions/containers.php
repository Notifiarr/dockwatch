<?php

/*
----------------------------------
 ------  Created: 112923   ------
 ------  Austin Best       ------
----------------------------------
*/

function renderContainerRow($nameHash, $return)
{
    global $docker, $pullsFile, $settingsTable, $processList, $skipContainerActions, $groupHash;

    $pullsFile = $pullsFile ?: apiRequest('file-pull')['result'];

    foreach ($processList as $thisProcess) {
        if (md5($thisProcess['Names']) == $nameHash) {
            $process = $thisProcess;
            break;
        }
    }

    $isDockwatch        = false;
    $dockwatchWarning   = '';
    if (isDockwatchContainer($process)) {
        $isDockwatch        = true;
        $dockwatchWarning   = ' <i class="fas fa-exclamation-circle text-danger" title="Dockwatch warning, click for more information" style="cursor: pointer;" onclick="dockwatchWarning()"></i>';
    }

    $skipActions        = skipContainerActions($process['inspect'][0]['Config']['Image'], $skipContainerActions);
    $containerSettings  = apiRequest('database-getContainerFromHash', ['hash' => $nameHash])['result'];
    $logo               = getIcon($process['inspect']);
    $notificationIcon   = '<i id="disableNotifications-icon-' . $nameHash . '" class="fas fa-bell-slash text-muted" title="Notifications disabled for this container" style="display: ' . ($containerSettings['disableNotifications'] ? 'inline-block' : 'none') . '"></i> ';

    if ($process['State'] == 'running') {
        $control = '<i style="' . ($skipActions ? 'display: none;' : '') . ' cursor: pointer;" id="restart-btn-' . $nameHash . '" class="fas fa-sync-alt text-success container-restart-btn" title="Restart" onclick="$(\'#massTrigger-' . $nameHash . '\').prop(\'checked\', true); $(\'#massContainerTrigger\').val(2); massApplyContainerTrigger();"></i><br>';
        $control .= '<i style="' . ($skipActions ? 'display: none;' : '') . ' cursor: pointer;" id="stop-btn-' . $nameHash . '" class="fas fa-power-off text-danger container-stop-btn" title="Stop" onclick="$(\'#massTrigger-' . $nameHash . '\').prop(\'checked\', true); $(\'#massContainerTrigger\').val(3); massApplyContainerTrigger();"></i>';
    } else {
        $control = '<i style="' . ($skipActions ? 'display: none;' : '') . ' cursor: pointer;" id="start-btn-' . $nameHash . '" class="fas fa-play text-success container-start-btn" title="Start" onclick="$(\'#massTrigger-' . $nameHash . '\').prop(\'checked\', true); $(\'#massContainerTrigger\').val(1); massApplyContainerTrigger();"></i>';
    }

    $cpuUsage = floatval(str_replace('%', '', $process['stats']['CPUPerc']));
    if (intval($settingsTable['cpuAmount']) > 0) {
        $cpuUsage = number_format(($cpuUsage / intval($settingsTable['cpuAmount'])), 2) . '%';
    }

    $pullData = $pullsFile[$nameHash];
    $updateStatus = '<span class="text-danger">Unchecked</span>';
    if ($pullData) {
        $updateStatus = $pullData['regctlDigest'] == $pullData['imageDigest'] ? '<span class="text-success">Up to date</span>' : '<span class="text-warning">Outdated</span>';
    }

    $restartUnhealthy       = $containerSettings['restartUnhealthy'];
    $healthyRestartClass    = 'text-success';
    $healthyRestartText     = 'Auto restart when unhealthy';

    if (!$restartUnhealthy) {
        $healthyRestartClass    = 'text-warning';
        $healthyRestartText     = 'Not set to auto restart when unhealthy';
    }

    $usesHealth = false;
    $health     = 'Not setup';

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

    $mountList = $previewMount = '';
    if ($process['inspect'][0]['Mounts']) {
        $mounts = [];

        foreach ($process['inspect'][0]['Mounts'] as $mount) {
            if ($mount['Type'] != 'bind') {
                continue;
            }

            $arrow = $mount['Mode'] == 'ro' ? '&larr;' : '&harr;';

            $mounts[] = $mount['Destination'] . ' ' . $arrow . ' ' . $mount['Source'] . ($mount['Mode'] ? ':' . $mount['Mode'] : '');
            if (!$previewMount) {
                 $previewMount = truncateEnd($mount['Destination'], 18) . ' ' . $arrow . ' ' . truncateEnd($mount['Source'], 18) . ($mount['Mode'] ? ':' . $mount['Mode'] : '');
            }
        }

        if ($mounts) {
            $mountList = '<i class="far fa-minus-square" style="cursor: pointer; display: none;" id="hide-mount-btn-' . $nameHash . '" onclick="hideContainerMounts(\'' . $nameHash . '\')"></i><i class="far fa-plus-square" style="cursor: pointer;" id="show-mount-btn-' . $nameHash . '" onclick="showContainerMounts(\'' . $nameHash . '\')"></i> ';
            $mountList .= '<span id="mount-list-preview-' . $nameHash . '">' . $previewMount . '</span><br>';
            $mountList .= '<div id="mount-list-full-' . $nameHash . '" style="display: none;">';
            $mountList .= implode('<br>', $mounts);
            $mountList .= '</div>';
        }
    }

    $portList = $previewPort = '';
    if ($process['inspect'][0]['HostConfig']['PortBindings']) {
        $ports = [];

        foreach ($process['inspect'][0]['HostConfig']['PortBindings'] as $internalBind => $portBinds) {
            foreach ($portBinds as $portBind) {
                if ($portBind['HostPort']) {
                    $ports[] = $internalBind . ' &rarr; ' . $portBind['HostPort'];
                }
            }
        }

        if ($ports) {
            if (!$previewPort) {
                $slicePorts     = array_slice($ports, 0, 2);
                $previewPort    = implode('<br>', $slicePorts);
            }

            if (count($ports) <= 2) {
                $portList = $previewPort;
            } else {
                $portList = '<i class="far fa-minus-square" style="cursor: pointer; display: none;" id="hide-port-btn-' . $nameHash . '" onclick="hideContainerPorts(\'' . $nameHash . '\')"></i><i class="far fa-plus-square" style="cursor: pointer;" id="show-port-btn-' . $nameHash . '" onclick="showContainerPorts(\'' . $nameHash . '\')"></i> ';
                $portList .= '<span id="port-list-preview-' . $nameHash . '">' . $previewPort . '</span><br>';
                $portList .= '<div id="port-list-full-' . $nameHash . '" style="display: none;">';
                $portList .= implode('<br>', $ports);
                $portList .= '</div>';
            }
        }
    }

    $envList = $previewEnv = '';
    if ($process['inspect'][0]['Config']['Env']) {
        $env = [];

        foreach ($process['inspect'][0]['Config']['Env'] as $envVar) {
            $envLabel   = explode('=', $envVar)[0] . ' &rarr; ' . explode('=', $envVar)[1];
            $env[]      = $envLabel;

            if (!$previewEnv) {
                $previewEnv = strlen(explode('=', $envVar)[0] . ' &rarr; ') > 30 ? explode('=', $envVar)[0] . '...' : truncateEnd($envLabel, 30);
            }
        }

        if ($env) {
            $envList = '<i class="far fa-minus-square" style="cursor: pointer; display: none;" id="hide-env-btn-' . $nameHash . '" onclick="hideContainerEnv(\'' . $nameHash . '\')"></i><i class="far fa-plus-square" style="cursor: pointer;" id="show-env-btn-' . $nameHash . '" onclick="showContainerEnv(\'' . $nameHash . '\')"></i> ';
            $envList .= '<span id="env-list-preview-' . $nameHash . '">' . $previewEnv . '</span><br>';
            $envList .= '<div id="env-list-full-' . $nameHash . '" style="display: none;">';
            $envList .= implode('<br>', $env);
            $envList .= '</div>';
        }
    }

    if ($return == 'json') {
        return [
                'control'   => $control,
                'update'    => $updateStatus . '<br><span class="text-muted small-text" title="' . $pullData['imageDigest'] .'">' . truncateMiddle(str_replace('sha256:', '', $pullData['imageDigest']), 15) . '</span>',
                'state'     => $process['State'],
                'mounts'    => $mountList,
                'ports'     => $portList,
                'env'       => $envList,
                'length'    => $length,
                'cpu'       => $cpuUsage,
                'cpuTitle'  => $process['stats']['CPUPerc'],
                'mem'       => $process['stats']['MemPerc'],
                'health'    => $health
            ];
    } else {
        $version = '';
        foreach ($process['inspect'][0]['Config']['Labels'] as $label => $val) {
            if (str_contains($label, 'image.version')) {
                $version = $val;
                break;
            }
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

        $network = $process['inspect'][0]['HostConfig']['NetworkMode'];
        if (str_contains($network, ':')) {
            list($null, $containerId) = explode(':', $network);
            $network = 'container:' . $docker->findContainer(['id' => $containerId, 'data' => $processList]);
        }

        ?>
        <tr id="<?= $nameHash ?>" <?= $groupHash ? 'class="' . $groupHash . ' container-group-row" style="display: none; background-color: #232833;"' : '' ?>>
            <!-- COLUMN: CHECKBOX -->
            <td><input <?= $isDockwatch ? 'attr-dockwatch="true"' : '' ?> id="massTrigger-<?= $nameHash ?>" data-name="<?= $process['Names'] ?>" data-id="<?= $containerSettings['id'] ?>" type="checkbox" class="form-check-input containers-check <?= $groupHash ? 'group-' . $groupHash . '-check' : '' ?>"></td>
            <!-- COLUMN: ICON -->
            <td><?= $logo ? '<img src="' . $logo . '" height="32" width="32" style="object-fit: contain; margin-top: 5px;">' : '' ?></td>
            <!-- COLUMN: STOP/START ICON, NAME, MENU, REPOSITORY -->
            <td>
                <div class="row m-0 p-0">
                    <!-- STOP/START ICONS -->
                    <div class="col-sm-1" id="<?= $nameHash ?>-control"><?= $control ?></div>
                    <!-- NAME, MENU, REPOSITORY -->
                    <div class="col-sm-10">
                        <!-- NAME -->
                        <span id="menu-<?= $nameHash ?>" style="cursor: pointer;" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><?= $notificationIcon . $process['Names'] ?></span>
                        <!-- CONTAINER MENU -->
                        <ul style="max-width: 200px" class="dropdown-menu dropdown-menu-dark p-2" role="menu" aria-labelledby="menu-<?= $nameHash ?>">
                            <li <?= $skipActions ? 'class="d-none"' : '' ?>><i class="fas fa-tools fa-fw text-muted me-1"></i> <a onclick="openEditContainer('<?= $nameHash ?>')" tabindex="-1" href="#" class="text-white">Edit</a></li>
                            <li <?= $skipActions ? 'class="d-none"' : '' ?>><hr class="dropdown-divider"></li>
                            <li class="dropdown-submenu">
                                <i class="fas fa-ellipsis-v fa-fw text-muted me-1"></i> <a tabindex="-1" href="#" class="text-white">Actions</a>
                                <ul class="dropdown-menu dropdown-menu-dark p-2" style="width: 250px;">
                                    <li><i class="far fa-file-alt fa-fw text-muted me-1"></i> <a onclick="containerLogs('<?= $process['Names'] ?>')" tabindex="-1" href="#" class="text-white">View logs</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><i class="fas fa-cloud-download-alt fa-fw text-muted me-1"></i> <a onclick="applyContainerAction('<?= $nameHash ?>', 4)" tabindex="-1" href="#" class="text-white">Pull</a></li>
                                    <li <?= $skipActions ? 'class="d-none"' : '' ?>><i class="fas fa-trash-alt fa-fw text-muted me-1"></i> <a onclick="applyContainerAction('<?= $nameHash ?>', 9)" tabindex="-1" href="#" class="text-white">Remove</a></li>
                                    <li <?= $skipActions ? 'class="d-none"' : '' ?>><i class="fas fa-cloud-upload-alt fa-fw text-muted me-1"></i> <a onclick="applyContainerAction('<?= $nameHash ?>', 7)" tabindex="-1" href="#" class="text-white">Update: Apply</a></li>
                                    <li><i class="fas fa-cloud fa-fw text-muted me-1"></i> <a onclick="applyContainerAction('<?= $nameHash ?>', 11)" tabindex="-1" href="#" class="text-white">Update: Check</a></li>
                                </ul>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-submenu">
                                <i class="fas fa-ellipsis-v fa-fw text-muted me-1"></i> <a tabindex="-1" href="#" class="text-white">Options</a>
                                <ul class="dropdown-menu dropdown-menu-dark p-2" style="width: 300px;">
                                    <li><input <?= $containerSettings['disableNotifications'] ? 'checked' : '' ?> type="checkbox" class="form-check-input" id="disableNotifications-<?= $nameHash ?>" onclick="updateContainerOption('disableNotifications', '<?= $nameHash ?>')"> Disable notifications</li>
                                    <li><input <?= $skipActions == SKIP_FORCE ? 'disabled checked' : ($containerSettings['blacklist'] ? 'checked' : '') ?> type="checkbox" class="form-check-input" id="blacklist-<?= $nameHash ?>" onclick="updateContainerOption('blacklist', '<?= $nameHash ?>')"> Blacklist (no state changes)</li>
                                    <?php if ($usesHealth) { ?>
                                    <li><input <?= $skipActions ? 'disabled' : ($containerSettings['restartUnhealthy'] ? 'checked' : '') ?> type="checkbox" class="form-check-input" id="restartUnhealthy-<?= $nameHash ?>" onclick="updateContainerOption('restartUnhealthy', '<?= $nameHash ?>')"> Restart when unhealthy</li>
                                    <?php } ?>
                                    <li><input <?= $containerSettings['shutdownDelay'] ? 'checked' : '' ?> type="checkbox" class="form-check-input" id="shutdownDelay-<?= $nameHash ?>" onclick="updateContainerOption('shutdownDelay', '<?= $nameHash ?>');"> Delay shutdown <input type="text" id="shutdownDelay-input-<?= $nameHash ?>" onfocusout="updateContainerOption('shutdownDelaySeconds', '<?= $nameHash ?>');" class="form-control d-inline-block" style="height: 24px; width: 20%;" value="<?= $containerSettings['shutdownDelaySeconds'] ?: '120' ?>" <?= !$containerSettings['shutdownDelay'] ? 'readonly' : '' ?>></li>
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
                        <!-- REPOSITORY -->
                        <br><span class="text-muted small-text" title="<?= $docker->isIO($process['inspect'][0]['Config']['Image']) ?>"><?= truncateMiddle($docker->isIO($process['inspect'][0]['Config']['Image']), 30) ?></span>
                    </div>
                </div>
            </td>
            <!-- COLUMN: UPDATES, HASH -->
            <td id="<?= $nameHash ?>-update" title="Last pulled: <?= date('Y-m-d H:i:s', $pullData['checked']) ?>">
                <?= $updateStatus ?><br>
                <span class="text-muted small-text" title="<?= $pullData['imageDigest'] ?>"><?= truncateMiddle(str_replace('sha256:', '', $pullData['imageDigest']), 15) ?></span>
            </td>
            <!-- COLUMN: STATE -->
            <td>
                <span id="<?= $nameHash ?>-state"><?= $process['State'] ?></span><br>
                <span class="text-muted small-text" id="<?= $nameHash ?>-length"><?= $length ?></span>
            </td>
            <!-- COLUMN: HEALTH -->
            <td id="<?= $nameHash ?>-health"><?= $health ?></td>
            <!-- COLUMN: MOUNTS -->
            <td id="<?= $nameHash ?>-mounts-td"><span id="<?= $nameHash ?>-mounts" class="small-text"><?= $mountList ?></span></td>
            <!-- COLUMN: ENVIRONMENTS -->
            <td id="<?= $nameHash ?>-env-td"><span id="<?= $nameHash ?>-env" class="small-text"><?= $envList ?></span></td>
            <!-- COLUMN: PORTS -->
            <td id="<?= $nameHash ?>-ports-td"><span id="<?= $nameHash ?>-ports" class="small-text"><?= $portList ?></span></td>
            <!-- COLUMN: MEMORT/CPU USAGE -->
            <td id="<?= $nameHash ?>-usage"><?= $cpuUsage ?><br><?= $process['stats']['MemPerc'] ?></td>
        </tr>
        <?php
    }
}

function skipContainerActions($container, $containers)
{
    global $docker, $settingsTable, $stateFile;

    $stateFile          = $stateFile ?: apiRequest('file-state')['result'];
    $containersTable    = apiRequest('database-getContainers')['result'];

    if ($containersTable) {
        foreach ($containersTable as $containerSettings) {
            $containerHash = $containerSettings['hash'];

            if ($containerSettings['blacklist']) {
                $containerState = $docker->findContainer(['hash' => $containerHash, 'data' => $stateFile]);

                if (str_contains($containerState['Image'], $container)) {
                    return SKIP_OPTIONAL;
                }
            }
        }
    }

    if ($settingsTable['overrideBlacklist'] == 0) {
        foreach ($containers as $skip) {
            if (str_contains($container, $skip)) {
                return SKIP_FORCE;
            }
        }
    }

    return SKIP_OFF;
}
