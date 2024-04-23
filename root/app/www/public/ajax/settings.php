<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $globalSettings = $settingsFile['global'];
    $cpus = cpuTotal();
    if ($cpus == 0) {
        $cpus = '0 (Could not get cpu count from /proc/cpuinfo)';
    }
    ?>
    <div class="container-fluid pt-4 px-4">
        <div class="bg-secondary rounded h-100 p-4">
            <h4>General</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" width="15%">Name</th>
                            <th scope="col" width="30%">Setting</th>
                            <th scope="col" width="55%">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Server name</th>
                            <td>
                                <input class="form-control" type="text" id="globalSetting-serverName" value="<?= $globalSettings['serverName'] ?>">
                            </td>
                            <td>The name of this server, also passed in the notification payload</td>
                        </tr>
                        <tr>
                            <th scope="row">Maintenance IP</th>
                            <td>
                                <input class="form-control" type="text" id="globalSetting-maintenanceIP" value="<?= $globalSettings['maintenanceIP'] ?>">
                            </td>
                            <td>This IP is used to do updates/restarts for Dockwatch. It will create another container <code>dockwatch-maintenance</code> with this IP and after it has updated/restarted Dockwatch it will be removed. This is only required if you do static IP assignment for your containers.</td>
                        </tr>
                        <tr>
                            <th scope="row">Maintenance port</th>
                            <td>
                                <input class="form-control" type="text" id="globalSetting-maintenancePort" value="<?= ($globalSettings['maintenancePort'] ? $globalSettings['maintenancePort'] : 9998) ?>">
                            </td>
                            <td>This port is used to do updates/restarts for Dockwatch. It will create another container <code>dockwatch-maintenance</code> with this port and after it has updated/restarted Dockwatch it will be removed.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h4>Login Failures</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" width="15%">Name</th>
                            <th scope="col" width="30%">Setting</th>
                            <th scope="col" width="55%">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Allowed failures</th>
                            <td>
                                <input class="form-control" type="text" id="globalSetting-loginFailures" value="<?= LOGIN_FAILURE_LIMIT ?>">
                            </td>
                            <td>How many failures before blocking logins</td>
                        </tr>
                        <tr>
                            <th scope="row">Timeout length</th>
                            <td>
                                <input class="form-control" type="number" id="globalSetting-loginTimeout" value="<?= LOGIN_FAILURE_TIMEOUT ?>">
                            </td>
                            <td>How long to block logins after the limit is reached (minutes)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h4>Server list</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" width="15%">Name</th>
                            <th scope="col" width="30%">URL</th>
                            <th scope="col" width="55%">API Key</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($_SESSION['serverIndex'] != 0) {
                        ?>
                        <tr>
                            <td colspan="3">Sorry, remote management of server access is not allowed. Go to the <?= ACTIVE_SERVER_NAME ?> server to make those changes.</td>
                        </tr>
                        <?php
                    } else {
                        ?>
                        <tr>
                            <th scope="row"><input class="form-control" type="text" id="globalSetting-serverList-name-0" value="<?= $serversFile[0]['name'] ?>"></th>
                            <td><input class="form-control" type="text" id="globalSetting-serverList-url-0" value="<?= $serversFile[0]['url'] ?>"></td>
                            <td><?= $serversFile[0]['apikey'] ?><input type="hidden" id="globalSetting-serverList-apikey-0" value="<?= $serversFile[0]['apikey'] ?>"></td>
                        </tr>
                        <?php
                        if (count($serversFile) > 1) {
                            foreach ($serversFile as $serverIndex => $serverSettings) {
                                if ($serverIndex == 0) {
                                    continue;
                                }
                                ?>
                                <tr>
                                    <th scope="row"><input class="form-control" type="text" id="globalSetting-serverList-name-<?= $serverIndex ?>" value="<?= $serverSettings['name'] ?>"></th>
                                    <td><input class="form-control" type="text" id="globalSetting-serverList-url-<?= $serverIndex ?>" value="<?= $serverSettings['url'] ?>"></td>
                                    <td><input class="form-control" type="text" id="globalSetting-serverList-apikey-<?= $serverIndex ?>" value="<?= $serverSettings['apikey'] ?>"></td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                        <tr>
                            <th scope="row"><input class="form-control" type="text" id="globalSetting-serverList-name-new" value="" placeholder="New server name"></th>
                            <td><input class="form-control" type="text" id="globalSetting-serverList-url-new" value="" placeholder="New server url"></td>
                            <td><input class="form-control" type="text" id="globalSetting-serverList-apikey-new" value="" placeholder="New server apikey"></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <h4>New Containers</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" width="15%">Name</th>
                            <th scope="col" width="30%">Setting</th>
                            <th scope="col" width="55%">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Updates<sup>1</sup></th>
                            <td>
                                <select class="form-select d-inline-block w-50" id="globalSetting-updates">
                                    <option <?= ($globalSettings['updates'] == 0 ? 'selected' : '') ?> value="0">Ignore</option>
                                    <option <?= ($globalSettings['updates'] == 1 ? 'selected' : '') ?> value="1">Auto update</option>
                                    <option <?= ($globalSettings['updates'] == 2 ? 'selected' : '') ?> value="2">Check for updates</option>
                                </select>
                                <input type="text" class="form-control d-inline-block w-25" id="globalSetting-updatesFrequency" onclick="frequencyCronEditor(this.value, 'global', 'global')" value="<?= $globalSettings['updatesFrequency'] ?>"> <i class="far fa-question-circle" style="cursor: pointer;" title="HELP!" onclick="containerFrequencyHelp()"></i>
                            </td>
                            <td>What settings to use for new containers that are added</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h4>Auto Prune</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" width="15%">Name</th>
                            <th scope="col" width="30%">Setting</th>
                            <th scope="col" width="55%">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Orphan images</th>
                            <td>
                                <input class="form-check-input" type="checkbox" id="globalSetting-autoPruneImages" <?= ($globalSettings['autoPruneImages'] ? 'checked' : '') ?>>
                            </td>
                            <td>Automatically try to prune all orphan images daily</td>
                        </tr>
                        <tr>
                            <th scope="row">Orphan volumes</th>
                            <td>
                                <input class="form-check-input" type="checkbox" id="globalSetting-autoPruneVolumes" <?= ($globalSettings['autoPruneVolumes'] ? 'checked' : '') ?>>
                            </td>
                            <td>Automatically try to prune all orphan volumes daily</td>
                        </tr>
                        <tr>
                            <th scope="row">Orphan networks</th>
                            <td>
                                <input class="form-check-input" type="checkbox" id="globalSetting-autoPruneNetworks" <?= ($globalSettings['autoPruneNetworks'] ? 'checked' : '') ?>>
                            </td>
                            <td>Automatically try to prune all orphan networks daily</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h4 class="mt-3">Thresholds</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" width="15%">Name</th>
                            <th scope="col" width="30%">Setting</th>
                            <th scope="col" width="55%">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">CPU<sup>1</sup></th>
                            <td>
                                <input class="form-control" type="number" id="globalSetting-cpuThreshold" value="<?= $globalSettings['cpuThreshold'] ?>">
                            </td>
                            <td>If a container usage is above this number, send a notification (if notification is enabled)</td>
                        </tr>
                        <tr>
                            <th scope="row">CPUs</th>
                            <td>
                                <input class="form-control" type="number" id="globalSetting-cpuAmount" value="<?= $globalSettings['cpuAmount'] ?>">
                            </td>
                            <td>Detected count: <?= $cpus ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Memory<sup>1</sup></th>
                            <td>
                                <input class="form-control" type="number" id="globalSetting-memThreshold" value="<?= $globalSettings['memThreshold'] ?>">
                            </td>
                            <td>If a container usage is above this number, send a notification (if notification is enabled)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h4 class="mt-3">Sockets</h4>
            <div class="table-responsive mt-2">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" width="15%">Name</th>
                            <th scope="col" width="30%">Setting</th>
                            <th scope="col" width="55%">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Enabled<sup>2,3</sup></th>
                            <td>
                                <input class="form-check-input" type="checkbox" id="globalSetting-socketEnabled" <?= ($globalSettings['socketEnabled'] ? 'checked' : '') ?> disabled>
                            </td>
                            <td>Websocket will update the container list UI every minute with current status of Updates, State, Health, Added, CPU and Memory</td>
                        </tr>
                        <tr>
                            <th scope="row">Socket host</th>
                            <td>
                                <input class="form-control" type="text" id="globalSetting-socketHost" value="<?= ($globalSettings['socketHost'] ? $globalSettings['socketHost'] : SOCKET_HOST) ?>">
                            </td>
                            <td>The host for the socket to connect to (container host)</td>
                        </tr>
                        <tr>
                            <th scope="row">Socket port</th>
                            <td>
                                <input class="form-control" type="text" id="globalSetting-socketPort" value="<?= ($globalSettings['socketPort'] ? $globalSettings['socketPort'] : SOCKET_PORT) ?>">
                            </td>
                            <td>The port used for the socket (9998 is default unless changed)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h4 class="mt-3">Logging</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" width="15%">Name</th>
                            <th scope="col" width="30%">Setting</th>
                            <th scope="col" width="55%">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Crons</th>
                            <td>
                                <input class="form-control" type="number" id="globalSetting-cronLogLength" value="<?= ($globalSettings['cronLogLength'] <= 1 ? 1 : $globalSettings['cronLogLength']) ?>">
                            </td>
                            <td>How long to store cron run log files (min 1 day)</td>
                        </tr>
                        <tr>
                            <th scope="row">Notifications</th>
                            <td>
                                <input class="form-control" type="number" id="globalSetting-notificationLogLength" value="<?= ($globalSettings['notificationLogLength'] <= 1 ? 1 : $globalSettings['notificationLogLength']) ?>">
                            </td>
                            <td>How long to store logs generated when notifications are sent (min 1 day)</td>
                        </tr>
                        <tr>
                            <th scope="row">UI</th>
                            <td>
                                <input class="form-control" type="number" id="globalSetting-uiLogLength" value="<?= ($globalSettings['uiLogLength'] <= 1 ? 1 : $globalSettings['uiLogLength']) ?>">
                            </td>
                            <td>How long to store logs generated from using the UI (min 1 day)</td>
                        </tr>
                        <tr>
                            <th scope="row">API</th>
                            <td>
                                <input class="form-control" type="number" id="globalSetting-apiLogLength" value="<?= ($globalSettings['apiLogLength'] <= 1 ? 1 : $globalSettings['apiLogLength']) ?>">
                            </td>
                            <td>How long to store logs generated when api requests are made (min 1 day)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h4 class="mt-3">Development</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" width="15%">Name</th>
                            <th scope="col" width="30%">Setting</th>
                            <th scope="col" width="55%">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Environment</th>
                            <td>
                                <select class="form-select" id="globalSetting-environment">
                                    <option <?= ($globalSettings['environment'] == 0 ? 'selected' : '') ?> value="0">Internal</option>
                                    <option <?= ($globalSettings['environment'] == 1 ? 'selected' : '') ?> value="1">External</option>
                                </select>
                            </td>
                            <td>Location of webroot, requires a container restart after changing. Do not change this without working files externally!</td>
                        </tr>
                        <tr>
                            <th scope="row">Override Blacklist</th>
                            <td>
                                <input class="form-check-input" type="checkbox" id="globalSetting-overrideBlacklist" <?= ($globalSettings['overrideBlacklist'] ? 'checked' : '') ?>>
                            </td>
                            <td>Generally not recommended, it's at your own risk.</td>
                        </tr>
                        <tr>
                            <th scope="row">Page Loading<sup>3</sup></th>
                            <td>
                                <select class="form-select" id="globalSetting-externalLoading">
                                    <option <?= ($globalSettings['externalLoading'] == 0 ? 'selected' : '') ?> value="0">Internal</option>
                                    <option <?= ($globalSettings['externalLoading'] == 1 ? 'selected' : '') ?> value="1">External</option>
                                </select>
                            </td>
                            <td>Internal: On a full page refresh you will go back to the overview. (state lost)<br>External: On a full page refresh you will stay on this current page. (state saved in URL)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div align="center"><button type="button" class="btn btn-success m-2" onclick="saveGlobalSettings()">Save Changes</button></div>
            <sup>1</sup> Checked every 5 minutes<br>
            <sup>2</sup> Updates every minute<br>
            <sup>3</sup> Requires a page reload (F5) to take effect<br>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'saveGlobalSettings') {
    $currentSettings = getServerFile('settings');
    $newSettings = [];

    foreach ($_POST as $key => $val) {
        if ($key == 'm' || str_contains($key, 'serverList')) {
            continue;
        }

        if ($key == 'updatesFrequency') {
            try {
                $cron = Cron\CronExpression::factory($val);
            } catch (Exception $e) {
                $val = DEFAULT_CRON;
            }
        }

        $newSettings[$key] = trim($val);
    }

    //-- ENVIRONMENT SWITCHING
    if ($currentSettings['global']['environment'] != $_POST['environment']) {
        if ($_POST['environment'] == 0) { //-- USE INTERNAL
            linkWebroot('internal');
        } else { //-- USE EXTERNAL
            linkWebroot('external');
        }
    }

    $settingsFile['global'] = $newSettings;
    $saveSettings = setServerFile('settings', $settingsFile);

    if ($saveSettings['code'] != 200) {
        $error = 'Error saving global settings on server ' . ACTIVE_SERVER_NAME;
    }

    //-- ONLY MAKE SERVER CHANGES ON LOCAL
    if ($_SESSION['serverIndex'] == 0) {
        //-- ADD SERVER TO LIST
        if ($_POST['serverList-name-new'] && $_POST['serverList-url-new'] && $_POST['serverList-apikey-new']) {
            $serversFile[] = ['name' => $_POST['serverList-name-new'], 'url' => rtrim($_POST['serverList-url-new'], '/'), 'apikey' => $_POST['serverList-apikey-new']];
        }

        //-- UPDATE SERVER LIST
        foreach ($_POST as $key => $val) {
            if (!str_contains($key, 'serverList-apikey')) {
                continue;
            }

            list($name, $field, $index) = explode('-', $key);

            if (!is_numeric($index)) {
                continue;
            }

            if ($_POST['serverList-name-' . $index] && $_POST['serverList-url-' . $index] && $_POST['serverList-apikey-' . $index]) {
                $serversFile[$index] = ['name' => $_POST['serverList-name-' . $index], 'url' => rtrim($_POST['serverList-url-' . $index], '/'), 'apikey' => $_POST['serverList-apikey-' . $index]];
            }
        }

        //-- REMOVE SERVER FROM LIST
        foreach ($_POST as $key => $val) {
            if (!str_contains($key, 'serverList-apikey')) {
                continue;
            }

            list($name, $field, $index) = explode('-', $key);

            if (!is_numeric($index)) {
                continue;
            }

            if (!$_POST['serverList-name-' . $index] && !$_POST['serverList-url-' . $index] && !$_POST['serverList-apikey-' . $index]) {
                unset($serversFile[$index]);
            }
        }

        $saveServers = setServerFile('servers', $serversFile);

        if ($saveServers['code'] != 200) {
            $error = 'Error saving server list on server ' . ACTIVE_SERVER_NAME;
        }
    } else {
        $serversFile = getFile(SERVERS_FILE);
    }

    $serverList = '';
    foreach ($serversFile as $serverIndex => $serverDetails) {
        $ping = curl($serverDetails['url'] . '/api/?request=ping', ['x-api-key: ' . $serverDetails['apikey']]);
        $disabled = '';
        if ($ping['code'] != 200) {
            $disabled = ' [HTTP: ' . $ping['code'] . ']';
        }
        $serverList .= '<option ' . ($disabled ? 'disabled ' : '') . ($_SESSION['serverIndex'] == $serverIndex ? 'selected' : '') . ' value="' . $serverIndex . '">' . $serverDetails['name'] . $disabled . '</option>';
    }

    echo json_encode(['error' => $error, 'server' => ACTIVE_SERVER_NAME, 'serverList' => $serverList]);
}

//-- CALLED FROM THE NAV MENU SELECT
if ($_POST['m'] == 'updateServerIndex') {
    $_SESSION['serverIndex'] = intval($_POST['index']);
}