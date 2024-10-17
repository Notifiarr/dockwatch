<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $cpus = cpuTotal();
    if ($cpus == 0) {
        $cpus = '0 (Could not get cpu count from /proc/cpuinfo)';
    }

    $migrations = '<option value="000">000_fresh_start</option>';
    $dir = opendir(MIGRATIONS_PATH);
    while ($migration = readdir($dir)) {
        if (str_contains($migration, '.php')) {
            $migrations .= '<option ' . ($settingsTable['migration'] == substr($migration, 0, 3) ? 'selected ' : '') . 'value="' . substr($migration, 0, 3) . '">' . str_replace('.php', '', $migration) . '</option>';
        }
    }
    closedir($dir);

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
                                <input class="form-control" type="text" id="globalSetting-serverName" value="<?= $settingsTable['serverName'] ?>">
                            </td>
                            <td>The name of this server, also passed in the notification payload</td>
                        </tr>
                        <tr>
                            <th scope="row">Maintenance IP</th>
                            <td>
                                <input class="form-control" type="text" id="globalSetting-maintenanceIP" value="<?= $settingsTable['maintenanceIP'] ?>">
                            </td>
                            <td>This IP is used to do updates/restarts for Dockwatch. It will create another container <code>dockwatch-maintenance</code> with this IP and after it has updated/restarted Dockwatch it will be removed. This is only required if you do static IP assignment for your containers.</td>
                        </tr>
                        <tr>
                            <th scope="row">Maintenance port</th>
                            <td>
                                <input class="form-control" type="text" id="globalSetting-maintenancePort" value="<?= $settingsTable['maintenancePort'] ? $settingsTable['maintenancePort'] : APP_MAINTENANCE_PORT ?>">
                            </td>
                            <td>This port is used to do updates/restarts for Dockwatch. It will create another container <code>dockwatch-maintenance</code> with this port and after it has updated/restarted Dockwatch it will be removed.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h4>Login failures</h4>
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
            <h4><?= APP_NAME ?> servers</h4>
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
                    if ($_SESSION['activeServerId'] != APP_SERVER_ID) {
                        ?>
                        <tr>
                            <td colspan="3">Sorry, remote management of the server list is not allowed. Go to the <code><?= ACTIVE_SERVER_NAME ?></code> server to make those changes.</td>
                        </tr>
                        <?php
                    } else {
                        ?>
                        <tr>
                            <th scope="row"><input class="form-control" type="text" id="globalSetting-serverList-name-<?= APP_SERVER_ID ?>" value="<?= $serversTable[APP_SERVER_ID]['name'] ?>"></th>
                            <td><input class="form-control" type="text" id="globalSetting-serverList-url-<?= APP_SERVER_ID ?>" value="<?= $serversTable[APP_SERVER_ID]['url'] ?>"></td>
                            <td><?= $serversTable[APP_SERVER_ID]['apikey'] ?><input type="hidden" id="globalSetting-serverList-apikey-<?= APP_SERVER_ID ?>" value="<?= $serversTable[APP_SERVER_ID]['apikey'] ?>"></td>
                        </tr>
                        <?php
                        if (count($serversTable) > 1) {
                            foreach ($serversTable as $serverSettings) {
                                if ($serverSettings['id'] == APP_SERVER_ID) {
                                    continue;
                                }
                                ?>
                                <tr id="remoteServer-<?= $serverSettings['id'] ?>">
                                    <th scope="row">
                                        <i class="far fa-trash-alt text-danger d-inline-block" style="cursor: pointer;" title="Unlink remote server" onclick="unlinkRemoteServer('<?= $serverSettings['id'] ?>')"></i> 
                                        <input class="form-control d-inline-block" style="width: 90%;" type="text" id="globalSetting-serverList-name-<?= $serverSettings['id'] ?>" value="<?= $serverSettings['name'] ?>">
                                    </th>
                                    <td><input class="form-control" type="text" id="globalSetting-serverList-url-<?= $serverSettings['id'] ?>" value="<?= $serverSettings['url'] ?>"></td>
                                    <td><input class="form-control" type="text" id="globalSetting-serverList-apikey-<?= $serverSettings['id'] ?>" value="<?= $serverSettings['apikey'] ?>"></td>
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
                    <tr>
                        <th scope="row">Timeout length</th>
                        <td>
                            <input class="form-control" type="number" id="globalSetting-remoteServerTimeout" value="<?= $settingsTable['remoteServerTimeout'] ?: DEFAULT_REMOTE_SERVER_TIMEOUT ?>">
                        </td>
                        <td>How long to wait for a remote server to respond, keep in mind 60-90 seconds will throw apache/nginx/cloudflare timeouts</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <h4>New containers</h4>
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
                                    <option <?= $settingsTable['updates'] == 0 ? 'selected' : '' ?> value="0">Ignore</option>
                                    <option <?= $settingsTable['updates'] == 1 ? 'selected' : '' ?> value="1">Auto update</option>
                                    <option <?= $settingsTable['updates'] == 2 ? 'selected' : '' ?> value="2">Check for updates</option>
                                </select>
                                <input type="text" class="form-control d-inline-block w-25" id="globalSetting-updatesFrequency" onclick="frequencyCronEditor(this.value, 'global', 'global')" value="<?= $settingsTable['updatesFrequency'] ?>"> <i class="far fa-question-circle" style="cursor: pointer;" title="HELP!" onclick="containerFrequencyHelp()"></i>
                            </td>
                            <td>What settings to use for new containers that are added</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h4>Auto prune</h4>
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
                                <input class="form-check-input" type="checkbox" id="globalSetting-autoPruneImages" <?= $settingsTable['autoPruneImages'] ? 'checked' : '' ?>>
                            </td>
                            <td>Automatically try to prune all orphan images daily</td>
                        </tr>
                        <tr>
                            <th scope="row">Orphan volumes</th>
                            <td>
                                <input class="form-check-input" type="checkbox" id="globalSetting-autoPruneVolumes" <?= $settingsTable['autoPruneVolumes'] ? 'checked' : '' ?>>
                            </td>
                            <td>Automatically try to prune all orphan volumes daily</td>
                        </tr>
                        <tr>
                            <th scope="row">Orphan networks</th>
                            <td>
                                <input class="form-check-input" type="checkbox" id="globalSetting-autoPruneNetworks" <?= $settingsTable['autoPruneNetworks'] ? 'checked' : '' ?>>
                            </td>
                            <td>Automatically try to prune all orphan networks daily</td>
                        </tr>
                        <tr>
                            <th scope="row">Hour</th>
                            <td>
                                <select class="form-select" id="globalSetting-autoPruneHour">
                                    <?php
                                        $option = '';
                                        for ($x = 0; $x <= 23; $x++) {
                                            $option .= '<option ' . ($x == intval($settingsTable['autoPruneHour']) || !$settingsTable['autoPruneHour'] && $x == 12 ? 'selected' : '') . ' value="' . $x . '">' . $x . '</option>'; 
                                        }
                                        echo $option;
                                    ?>
                                </select>
                            </td>
                            <td>At which hour the auto prune should run (0-23)</td>
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
                                <input class="form-control" type="number" id="globalSetting-cpuThreshold" value="<?= $settingsTable['cpuThreshold'] ?>">
                            </td>
                            <td>If a container usage is above this number, send a notification (if notification is enabled)</td>
                        </tr>
                        <tr>
                            <th scope="row">CPUs</th>
                            <td>
                                <input class="form-control" type="number" id="globalSetting-cpuAmount" value="<?= $settingsTable['cpuAmount'] ?>">
                            </td>
                            <td>Detected count: <?= $cpus ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Memory<sup>1</sup></th>
                            <td>
                                <input class="form-control" type="number" id="globalSetting-memThreshold" value="<?= $settingsTable['memThreshold'] ?>">
                            </td>
                            <td>If a container usage is above this number, send a notification (if notification is enabled)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h4 class="mt-3">SSE</h4>
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
                                <input class="form-check-input" type="checkbox" id="globalSetting-sseEnabled" <?= $settingsTable['sseEnabled'] ? 'checked' : '' ?>>
                            </td>
                            <td>SSE will update the container list UI every minute with current status of Updates, State, Health, CPU and Memory</td>
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
                                <input class="form-control" type="number" id="globalSetting-cronLogLength" value="<?= $settingsTable['cronLogLength'] <= 1 ? 1 : $settingsTable['cronLogLength'] ?>">
                            </td>
                            <td>How long to store cron run log files (min 1 day)</td>
                        </tr>
                        <tr>
                            <th scope="row">Notifications</th>
                            <td>
                                <input class="form-control" type="number" id="globalSetting-notificationLogLength" value="<?= $settingsTable['notificationLogLength'] <= 1 ? 1 : $settingsTable['notificationLogLength'] ?>">
                            </td>
                            <td>How long to store logs generated when notifications are sent (min 1 day)</td>
                        </tr>
                        <tr>
                            <th scope="row">UI</th>
                            <td>
                                <input class="form-control" type="number" id="globalSetting-uiLogLength" value="<?= $settingsTable['uiLogLength'] <= 1 ? 1 : $settingsTable['uiLogLength'] ?>">
                            </td>
                            <td>How long to store logs generated from using the UI (min 1 day)</td>
                        </tr>
                        <tr>
                            <th scope="row">API</th>
                            <td>
                                <input class="form-control" type="number" id="globalSetting-apiLogLength" value="<?= $settingsTable['apiLogLength'] <= 1 ? 1 : $settingsTable['apiLogLength'] ?>">
                            </td>
                            <td>How long to store logs generated when api requests are made (min 1 day)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h4 class="mt-3"><i class="far fa-plus-square text-light development-settings" onclick="$('.development-settings').toggle()"></i> <i class="far fa-minus-square text-light development-settings" onclick="$('.development-settings').toggle()" style="display: none;"></i> Development</h4>
            <div class="table-responsive development-settings" style="display: none;">
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
                            <th scope="row">Migration</th>
                            <td><select class="form-select" id="globalSetting-migration"><?= $migrations ?></select></td>
                            <td>The database migration this server is on, changing this will re-apply all subsequent migrations and reset any settings they alter.</td>
                        </tr>
                        <tr>
                            <th scope="row">State cron time</th>
                            <td>
                                <select class="form-select" id="globalSetting-stateCronTime">
                                    <option <?= $settingsTable['stateCronTime'] == 1 ? 'selected' : '' ?> value="1">1m</option>
                                    <option <?= $settingsTable['stateCronTime'] == 2 ? 'selected' : '' ?> value="2">2m</option>
                                    <option <?= $settingsTable['stateCronTime'] == 3 ? 'selected' : '' ?> value="3">3m</option>
                                    <option <?= $settingsTable['stateCronTime'] == 4 ? 'selected' : '' ?> value="4">4m</option>
                                    <option <?= $settingsTable['stateCronTime'] == 5 ? 'selected' : '' ?> value="5">5m</option>
                                </select>
                            </td>
                            <td>This can impact performance on your server so keep that in mind!</td>
                        </tr>
                        <tr>
                            <th scope="row">Override blacklist</th>
                            <td><input class="form-check-input" type="checkbox" id="globalSetting-overrideBlacklist" <?= $settingsTable['overrideBlacklist'] ? 'checked' : '' ?>></td>
                            <td>Generally not recommended, it's at your own risk.</td>
                        </tr>
                        <tr>
                            <th scope="row">Environment</th>
                            <td>
                                <select class="form-select" id="globalSetting-environment">
                                    <option <?= $settingsTable['environment'] == 0 ? 'selected' : '' ?> value="0">Internal</option>
                                    <option <?= $settingsTable['environment'] == 1 ? 'selected' : '' ?> value="1">External</option>
                                </select>
                            </td>
                            <td>Location of webroot, requires a container restart after changing. Do not change this without working files externally!</td>
                        </tr>
                        <tr>
                            <th scope="row">Debug zip</th>
                            <td>
                                <input class="form-check-input" type="checkbox" id="globalSetting-debugZipDatabase"> Database<br>
                                <input class="form-check-input" type="checkbox" id="globalSetting-debugZipLogs"> Logs<br>
                                <input class="form-check-input" type="checkbox" id="globalSetting-debugZipJson"> json
                            </td>
                            <td>This does not save but triggers a zip file to be created (<code><?= APP_DATA_PATH . 'dockwatch.zip' ?></code>) when clicking save. Should only be needed when asked for by developers and includes (based on selection) database/*, logs/crons/*, logs/api/*, logs/system/*, settings, state, pull, health, stats & dependency json files</td>
                        </tr>
                        <tr>
                            <th scope="row">Telemetry</th>
                            <td><input class="form-check-input" type="checkbox" id="globalSetting-telemetry" <?= $settingsTable['telemetry'] ? 'checked' : '' ?>></td>
                            <td>Allow telemetry information to be collected. There is nothing personal or identifiable and what is sent can be seen in the Tasks menu or <a href="https://github.com/Notifiarr/dockwatch/blob/develop/root/app/www/public/functions/telemetry.php" target="_blank">here on github</a></td>
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
    $activeServer   = apiGetActiveServer();
    $newSettings    = [];

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
    if ($settingsTable['environment'] != $_POST['environment']) {
        if ($_POST['environment'] == 0) { //-- USE INTERNAL
            linkWebroot('internal');
        } else { //-- USE EXTERNAL
            linkWebroot('external');
        }
    }

    $settingsTable = apiRequest('database-setSettings', [], ['newSettings' => $newSettings])['result'];

    //-- ONLY MAKE SERVER CHANGES ON LOCAL
    if ($activeServer['id'] == APP_SERVER_ID) {
        $serverList = [];

        //-- ADD SERVER TO LIST
        if ($_POST['serverList-name-new'] && $_POST['serverList-url-new'] && $_POST['serverList-apikey-new']) {
            $serverList[] = ['name' => $_POST['serverList-name-new'], 'url' => rtrim($_POST['serverList-url-new'], '/'), 'apikey' => $_POST['serverList-apikey-new']];
        }

        //-- UPDATE SERVER LIST
        foreach ($_POST as $key => $val) {
            if (!str_contains($key, 'serverList-apikey')) {
                continue;
            }

            list($name, $field, $instanceId) = explode('-', $key);

            if (!is_numeric($instanceId)) {
                continue;
            }

            if ($_POST['serverList-name-' . $instanceId] && $_POST['serverList-url-' . $instanceId] && $_POST['serverList-apikey-' . $instanceId]) {
                $serverList[$instanceId] = ['name' => $_POST['serverList-name-' . $instanceId], 'url' => rtrim($_POST['serverList-url-' . $instanceId], '/'), 'apikey' => $_POST['serverList-apikey-' . $instanceId]];
            }
        }

        $serversTable = apiRequest('database-setServers', [], ['serverList' => $serverList])['result'];
    }

    if ($_POST['debugZipDatabase'] || $_POST['debugZipLogs'] || $_POST['debugZipJson']) {
        $zipfile = APP_DATA_PATH . 'dockwatch.zip';

        $zip = new ZipArchive();
        $zip->open($zipfile, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);

        if ($_POST['debugZipDatabase']) {
            $zip->addEmptyDir(str_replace(APP_DATA_PATH, '', DATABASE_PATH));
            if (file_exists(DATABASE_PATH . DATABASE_NAME)) {
                $zip->addFile(DATABASE_PATH . DATABASE_NAME, str_replace(APP_DATA_PATH, '', DATABASE_PATH) . DATABASE_NAME);
            }
        }

        if ($_POST['debugZipLogs']) {
            $zip->addEmptyDir(str_replace(APP_DATA_PATH, '', LOGS_PATH));
            
            $dir = opendir(LOGS_PATH);
            while ($logType = readdir($dir)) {
                if (str_equals_any($logType, ['api', 'crons', 'system'])) {
                    $zip->addEmptyDir(str_replace(APP_DATA_PATH, '', LOGS_PATH) . $logType);

                    $dir2 = opendir(LOGS_PATH . $logType);
                    while ($log = readdir($dir2)) {
                        if (str_contains($log, '.log')) {
                            $zip->addFile(LOGS_PATH . $logType . '/' . $log, str_replace(APP_DATA_PATH, '', LOGS_PATH) . $logType . '/' . $log);
                        }
                    }
                    closedir($dir2);
                }
            }
            closedir($dir);
        }

        if ($_POST['debugZipJson']) {
            if (file_exists(SETTINGS_FILE)) {
                $zip->addFile(SETTINGS_FILE, str_replace(APP_DATA_PATH, '', SETTINGS_FILE));
            }
            if (file_exists(STATE_FILE)) {
                $zip->addFile(STATE_FILE, str_replace(APP_DATA_PATH, '', STATE_FILE));
            }
            if (file_exists(PULL_FILE)) {
                $zip->addFile(PULL_FILE, str_replace(APP_DATA_PATH, '', PULL_FILE));
            }
            if (file_exists(HEALTH_FILE)) {
                $zip->addFile(HEALTH_FILE, str_replace(APP_DATA_PATH, '', HEALTH_FILE));
            }
            if (file_exists(STATS_FILE)) {
                $zip->addFile(STATS_FILE, str_replace(APP_DATA_PATH, '', STATS_FILE));
            }
            if (file_exists(DEPENDENCY_FILE)) {
                $zip->addFile(DEPENDENCY_FILE, str_replace(APP_DATA_PATH, '', DEPENDENCY_FILE));
            }
        }

        $zip->close();
    }

    echo json_encode(['error' => $error, 'server' => ACTIVE_SERVER_NAME, 'serverList' => getRemoteServerSelect()]);
}

//-- CALLED FROM THE NAV MENU SELECT
if ($_POST['m'] == 'updateActiveServer') {
    apiSetActiveServer(intval($_POST['id']));
    $_SESSION['serverList'] = '';
}

if ($_POST['m'] == 'unlinkRemoteServer') {
    $serversTable[intval($_POST['id'])]['remove'] = true;
    apiRequest('database-setServers', [], ['serverList' => $serversTable]);
}
