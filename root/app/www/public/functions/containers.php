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

    $isDockwatch        = isDockwatchContainer($process) ? true : false;
    $skipActions        = skipContainerActions($process['inspect'][0]['Config']['Image'], $skipContainerActions);
    $containerSettings  = apiRequest('database-getContainerFromHash', ['hash' => $nameHash])['result'];
    $logo               = getIcon($process['inspect']);
    $notificationIcon   = '<i id="disableNotifications-icon-' . $nameHash . '" class="fas fa-bell-slash text-muted" title="Notifications disabled for this container" style="display: ' . ($containerSettings['disableNotifications'] ? 'inline-block' : 'none') . '"></i> ';
    $isRunning          = $process['State'] == 'running';
    $start = $stop = $restart = '';

    if (!$skipActions) {
        if ($isRunning) {
            $restart    = '<a class="dropdown-item" href="javascript:void;" title="Restart" onclick="$(\'#massTrigger-' . $nameHash . '\').prop(\'checked\', true); $(\'#massContainerTrigger\').val(2); massApplyContainerTrigger();"><i class="fas fa-sync-alt fa-xs text-success container-restart-btn me-1"></i> Restart</a>';
            $stop       = '<a class="dropdown-item" href="javascript:void;" title="Stop" onclick="$(\'#massTrigger-' . $nameHash . '\').prop(\'checked\', true); $(\'#massContainerTrigger\').val(3); massApplyContainerTrigger();"><i class="fas fa-power-off fa-xs text-danger container-stop-btn me-1"></i> Stop</a>';
        } else {
            $start      = '<a class="dropdown-item" href="javascript:void;" title="Start" onclick="$(\'#massTrigger-' . $nameHash . '\').prop(\'checked\', true); $(\'#massContainerTrigger\').val(1); massApplyContainerTrigger();"><i class="fas fa-play fa-xs text-success container-start-btn me-1"></i> Start</a>';
        }
    }

    $cpuUsage = floatval(str_replace('%', '', $process['stats']['CPUPerc']));
    if (intval($settingsTable['cpuAmount']) > 0) {
        $cpuUsage = number_format(($cpuUsage / intval($settingsTable['cpuAmount'])), 2) . '%';
    }

    $pullData = $pullsFile[$nameHash];
    $updateStatus = '<span class="badge bg-gray w-75">Unchecked</span>';
    if ($pullData) {
        $updateStatus = $pullData['regctlDigest'] == $pullData['imageDigest'] ? '<span class="badge bg-success w-75">Updated</span>' : '<span class="badge bg-warning w-75">Outdated</span>';

        if (!$containerSettings['updates']) {
            $updateStatus = '<span class="badge bg-gray w-75">Ignored</span>';
        }
    }

    switch (true) {
        case str_contains($process['Status'], 'health:'):
            $health = '<span class="badge bg-gray">Waiting</span>';
            break;
        case str_contains($process['Status'], 'unhealthy'):
            $health = '<span class="badge bg-warning">Unhealthy</span>';
            break;
        case str_contains($process['Status'], 'healthy'):
            $health = '<span class="badge bg-success">Healthy</span>';
            break;
        default:
            $health = '';
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

    $portList = $previewPort = $tcpPort = '';
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

    $containerLink = buildContainerGuiLink($process['inspect'][0], $containerSettings);

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

    //-- TODO: This is likely not to work in every scenario (other networks, reverse proxies, etc)
    $gui            = $isRunning && $containerLink && !$isDockwatch ? '<a class="dropdown-item" href="javascript:void;" style="cursor:pointer;" title="Open container" onclick="window.open(\'' . $containerLink . '\')"><i class="text-primary fas fa-external-link-alt fa-xs me-1"></i> GUI</a>' : '';
    $onlineClass    = $isRunning ? 'text-success' : 'text-danger';

    if ($return == 'json') {
        return [
                'update'        => $updateStatus . '<br><span class="text-muted small-text" title="' . $pullData['imageDigest'] .'">' . truncateMiddle(str_replace('sha256:', '', $pullData['imageDigest']), 20) . '</span>',
                'state'         => $process['State'],
                'mounts'        => $mountList,
                'ports'         => $portList,
                'env'           => $envList,
                'length'        => $length,
                'cpu'           => $cpuUsage,
                'cpuTitle'      => $process['stats']['CPUPerc'],
                'mem'           => $process['stats']['MemPerc'],
                'health'        => $health,
                'gui'           => $gui,
                'onlineClass'   => $onlineClass,
                'start'         => $start,
                'stop'          => $stop,
                'restart'       => $restart
            ];
    } else {
        $network = $process['inspect'][0]['HostConfig']['NetworkMode'];
        if (str_contains($network, ':')) {
            list($null, $containerId) = explode(':', $network);
            $network = 'container:' . $docker->findContainer(['id' => $containerId, 'data' => $processList]);
        }

        ?>
        <tr id="<?= $nameHash ?>" <?= $groupHash ? 'class="' . $groupHash . ' container-group-row bg-primary" style="display: none;"' : '' ?>>
            <!-- COLUMN: CHECKBOX -->
            <td class="container-table-row bg-secondary"><input <?= $isDockwatch ? 'attr-dockwatch="true"' : '' ?> id="massTrigger-<?= $nameHash ?>" data-name="<?= $process['Names'] ?>" data-id="<?= $containerSettings['id'] ?>" type="checkbox" class="form-check-input containers-check <?= $groupHash ? 'group-' . $groupHash . '-check' : '' ?>"></td>
            <!-- COLUMN: ICON -->
            <td class="container-table-row bg-secondary"><?= $logo ? '<img src="' . $logo . '" height="32" width="32" style="object-fit: contain; margin-top: 5px;">' : '' ?></td>
            <!-- COLUMN: STOP/START ICON, NAME, MENU, REPOSITORY -->
            <td class="container-table-row bg-secondary">
                <div class="row m-0 p-0">
                    <div class="dropdown">
                        <span class="dropdown-toggle" id="containerMenu-<?= $nameHash ?>" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer;">
                            <?= $notificationIcon . '<span id="' . $nameHash . '-onlineIcon" class="' . $onlineClass . '">⚈</span> ' . $process['Names'] ?>
                        </span>
                        <ul class="dropdown-menu" aria-labelledby="containerMenu-<?= $nameHash ?>">
                            <?php if ($isRunning) { ?>
                            <li><a class="dropdown-item" href="javascript:void" onclick="containerShell('<?= $process['Names'] ?>')" style="cursor:pointer;"><i class="text-primary fas fa-terminal fa-xs me-1"></i> Shell</a></li>
                            <?php } ?>
                            <li id="<?= $nameHash ?>-menu-start" style="display:<?= !$start ? 'none' : '' ?>;"><?= $start ?></li>
                            <li id="<?= $nameHash ?>-menu-stop" style="display:<?= !$stop ? 'none' : '' ?>;"><?= $stop ?></li>
                            <li id="<?= $nameHash ?>-menu-restart" style="display:<?= !$restart ? 'none' : '' ?>;"><?= $restart ?></li>
                            <li id="<?= $nameHash ?>-menu-gui" style="display:<?= !$gui ? 'none' : '' ?>;"><?= $gui ?></li>
                            <li><a class="dropdown-item" href="javascript:void" onclick="containerInfo('<?= $nameHash ?>')" style="cursor:pointer;"><i class="text-primary fas fa-cogs fa-xs me-1"></i> Settings</a></li>
                            <li><a class="dropdown-item" href="javascript:void" onclick="containerLogs('<?= $process['Names'] ?>')" style="cursor:pointer;"><i class="text-primary fas fa-file-alt fa-xs me-1"></i> Logs</a></li>
                        </ul>
                    </div>
                    <span class="text-muted small-text hide-mobile" title="<?= $docker->isIO($process['inspect'][0]['Config']['Image']) ?>"><?= truncateMiddle($docker->isIO($process['inspect'][0]['Config']['Image']), 40) ?></span>
                </div>
            </td>
            <!-- COLUMN: UPDATES, HASH -->
            <td class="container-table-row bg-secondary hide-mobile" id="<?= $nameHash ?>-update" style="cursor: help;" title="Last pulled: <?= date('Y-m-d H:i:s', $pullData['checked']) ?>">
                <?= $updateStatus ?><br>
                <span class="text-muted small-text" title="<?= $pullData['imageDigest'] ?>"><?= truncateMiddle(str_replace('sha256:', '', $pullData['imageDigest']), 20) ?></span>
            </td>
            <!-- COLUMN: STATE -->
            <td class="container-table-row bg-secondary hide-mobile">
                <span id="<?= $nameHash ?>-state"><?= $process['State'] ?></span><br>
                <span class="text-muted small-text" id="<?= $nameHash ?>-length"><?= $length ?></span>
            </td>
            <!-- COLUMN: HEALTH -->
            <td class="container-table-row bg-secondary hide-mobile" id="<?= $nameHash ?>-health"><?= $health ?></td>
            <!-- COLUMN: MOUNTS -->
            <td class="container-table-row bg-secondary hide-mobile" id="<?= $nameHash ?>-mounts-td"><span id="<?= $nameHash ?>-mounts" class="small-text"><?= $mountList ?></span></td>
            <!-- COLUMN: ENVIRONMENTS -->
            <td class="container-table-row bg-secondary hide-mobile" id="<?= $nameHash ?>-env-td"><span id="<?= $nameHash ?>-env" class="small-text"><?= $envList ?></span></td>
            <!-- COLUMN: PORTS -->
            <td class="container-table-row bg-secondary hide-mobile" id="<?= $nameHash ?>-ports-td"><span id="<?= $nameHash ?>-ports" class="small-text"><?= $portList ?></span></td>
            <!-- COLUMN: MEMORT/CPU USAGE -->
            <td class="container-table-row bg-secondary hide-mobile" id="<?= $nameHash ?>-usage"><?= $cpuUsage ?><br><?= $process['stats']['MemPerc'] ?></td>

            <!-- COLUMN: STATUS (MOBILE) -->
            <td class="container-table-row bg-secondary hide-desktop" id="<?= $nameHash ?>-health"><?= $updateStatus ?><br><?= $health ?></td>
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

function buildContainerGuiLink($containerInspect, $containerDb, $templateOnly = false)
{
    global $settingsTable;

    if (!$settingsTable || !$containerInspect || !$containerDb || !$_SERVER) {
        return;
    }

    $containerGuiLink = $port = '';

    //-- NO CUSTOM SETTING, USE DEFAULTS
    if (!$guiSetting = $containerDb['containerGui']) {
        switch ($settingsTable['containerGui']) {
            case 2:
                $guiSetting = RP_SUB_GUI;
                break;
            case 3:
                $guiSetting = RP_DIR_GUI;
                break;
            default:
                $guiSetting = LOCAL_GUI;
                break;
        }
    }

    if ($templateOnly) {
        return $guiSetting;
    }

    foreach ($containerInspect['HostConfig']['PortBindings'] as $internalBind => $portBinds) {
        foreach ($portBinds as $portBind) {
            if ($portBind['HostPort'] && !$port) {
                //-- TODO: This is likely not the best way to find the right port when multiple ports are used
                if (str_contains($internalBind, 'tcp')) {
                    $port = $portBind['HostPort'];
                }
            }
        }
    }

    $find               = ['{protocol}', '{url}', '{port}', '{container}'];
    $replace            = [$_SERVER['REQUEST_SCHEME'], $_SERVER['SERVER_NAME'], $port, $containerInspect['Config']['Hostname']];
    $containerGuiLink   = str_replace($find, $replace, $guiSetting);

    return $containerGuiLink;
}
