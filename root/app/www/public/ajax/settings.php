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

    $serverTime = apiRequest('server/time')['result']['result'];
    ?>
    <ol class="breadcrumb rounded p-1 ps-2">
        <li class="breadcrumb-item"><a href="#" onclick="initPage('overview')"><?= $_SESSION['activeServerName'] ?></a><span class="ms-2">↦</span></li>
        <li class="breadcrumb-item active" aria-current="page">Settings</li>
    </ol>
    <div class="bg-secondary rounded p-4">
        <h4 class="text-info">Instance</h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col" width="15%">Name</th>
                        <th class="bg-primary ps-3" scope="col" width="30%">Setting</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col" width="55%">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Server timezone</td>
                        <td class="bg-secondary">
                            <?= $serverTime['timezone'] ?>
                        </td>
                        <td class="bg-secondary">The current timezone being used for this instance. Datetime: <?= $serverTime['time'] ?></td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Server name</td>
                        <td class="bg-secondary">
                            <input class="form-control" type="text" id="globalSetting-serverName" value="<?= $settingsTable['serverName'] ?>">
                        </td>
                        <td class="bg-secondary">The name of this server, also passed in the notification payload</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Maintenance IP</td>
                        <td class="bg-secondary">
                            <input class="form-control" type="text" id="globalSetting-maintenanceIP" value="<?= $settingsTable['maintenanceIP'] ?>">
                        </td>
                        <td class="bg-secondary">This IP is used to do updates/restarts for Dockwatch. It will create another container <code>dockwatch-maintenance</code> with this IP and after it has updated/restarted Dockwatch it will be removed. This is only required if you do static IP assignment for your containers.</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">WebSocket URL<sup>5</sup></td>
                        <td class="bg-secondary">
                            <input class="form-control" type="text" id="globalSetting-websocketUrl" value="<?= $settingsTable['websocketUrl'] ?: '' ?>" placeholder="<?= 'ws://'.$_SERVER['HTTP_HOST'].'/ws'  ?>">
                        </td>
                        <td class="bg-secondary">Best to leave empty. You only need to change this if you're hosting Dockwatch behind a reverse proxy.<br>Example (https): <code class="mx-1">wss://my.cool.domain/ws</code></td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Base URL<sup>5</sup></td>
                        <td class="bg-secondary">
                            <input class="form-control" type="text" id="globalSetting-baseUrl" value="<?= $_SERVER['BASE_URL'] ?: '' ?>" placeholder="<?= $_SERVER['BASE_URL'] ?: '/' ?>">
                        </td>
                        <td class="bg-secondary">Default is empty. You only need to change this if you're hosting Dockwatch behind a reverse proxy.<br>Must start with a <code>/</code>. Nested paths are not allowed.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h4 class="text-info">UI</h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col" width="15%">Name</th>
                        <th class="bg-primary ps-3" scope="col" width="30%">Setting</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col" width="55%">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Default page</td>
                        <td class="bg-secondary">
                            <select class="form-select" id="globalSetting-defaultPage">
                            <?php
                            $defaultPages = ['overview', 'containers', 'networks', 'compose', 'orphans', 'notification', 'settings', 'tasks', 'commands', 'logs'];

                            foreach ($defaultPages as $defaultPage) {
                                ?><option <?= $settingsTable['defaultPage'] == $defaultPage ? 'selected' : '' ?> value="<?= $defaultPage ?>"><?= ucfirst($defaultPage) ?></option><?php
                            }
                            ?>
                            </select>
                        </td>
                        <td class="bg-secondary">Which page should be loaded when <?= APP_NAME ?> first opens</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Usage metrics retention<sup>4</sup></td>
                        <td class="bg-secondary">
                            <select class="form-select" id="globalSetting-usageMetricsRetention">
                                <option <?= $settingsTable['usageMetricsRetention'] == 0 ? 'selected' : '' ?> value="0">Off</option>
                                <option <?= $settingsTable['usageMetricsRetention'] == 1 ? 'selected' : '' ?> value="1">Daily</option>
                                <option <?= $settingsTable['usageMetricsRetention'] == 2 ? 'selected' : '' ?> value="2">Weekly</option>
                                <option <?= $settingsTable['usageMetricsRetention'] == 3 ? 'selected' : '' ?> value="3">Monthly</option>
                            </select>
                        </td>
                        <td class="bg-secondary">Specify how long past usage metrics should be kept before being cleared.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h4 class="text-info">Login failures</h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col" width="15%">Name</th>
                        <th class="bg-primary ps-3" scope="col" width="30%">Setting</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col" width="55%">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Allowed failures</td>
                        <td class="bg-secondary">
                            <input class="form-control" type="text" id="globalSetting-loginFailures" value="<?= LOGIN_FAILURE_LIMIT ?>">
                        </td>
                        <td class="bg-secondary">How many failures before blocking logins</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Timeout length</t>
                        <td class="bg-secondary">
                            <input class="form-control" type="number" id="globalSetting-loginTimeout" value="<?= LOGIN_FAILURE_TIMEOUT ?>">
                        </td class="bg-secondary">
                        <td class="bg-secondary">How long to block logins after the limit is reached (minutes)</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h4 class="text-info"><?= APP_NAME ?> servers</h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col" width="15%">Name</th>
                        <th class="bg-primary ps-3" scope="col" width="30%">URL</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col" width="55%">API Key</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($_SESSION['activeServerId'] != APP_SERVER_ID) {
                    ?>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" colspan="3">Sorry, remote management of the server list is not allowed. Go to the <code><?= ACTIVE_SERVER_NAME ?></code> server to make those changes.</td>
                    </tr>
                    <?php
                } else {
                    ?>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row"><input class="form-control" type="text" id="globalSetting-serverList-name-<?= APP_SERVER_ID ?>" value="<?= $serversTable[APP_SERVER_ID]['name'] ?>"></td>
                        <td class="bg-secondary"><input class="form-control" type="text" id="globalSetting-serverList-url-<?= APP_SERVER_ID ?>" value="<?= $serversTable[APP_SERVER_ID]['url'] ?>"></td>
                        <td class="bg-secondary"><?= $serversTable[APP_SERVER_ID]['apikey'] ?><input type="hidden" id="globalSetting-serverList-apikey-<?= APP_SERVER_ID ?>" value="<?= $serversTable[APP_SERVER_ID]['apikey'] ?>"></td>
                    </tr>
                    <?php
                    if (count($serversTable) > 1) {
                        foreach ($serversTable as $serverSettings) {
                            if ($serverSettings['id'] == APP_SERVER_ID) {
                                continue;
                            }
                            ?>
                            <tr id="remoteServer-<?= $serverSettings['id'] ?>">
                                <td class="bg-secondary" scope="row">
                                    <i class="far fa-trash-alt text-warning d-inline-block" style="cursor: pointer;" title="Unlink remote server" onclick="unlinkRemoteServer('<?= $serverSettings['id'] ?>')"></i>
                                    <input class="form-control d-inline-block" style="width: 90%;" type="text" id="globalSetting-serverList-name-<?= $serverSettings['id'] ?>" value="<?= $serverSettings['name'] ?>">
                                </td>
                                <td class="bg-secondary"><input class="form-control" type="text" id="globalSetting-serverList-url-<?= $serverSettings['id'] ?>" value="<?= $serverSettings['url'] ?>"></td>
                                <td class="bg-secondary"><input class="form-control" type="text" id="globalSetting-serverList-apikey-<?= $serverSettings['id'] ?>" value="<?= $serverSettings['apikey'] ?>"></td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row"><input class="form-control" type="text" id="globalSetting-serverList-name-new" value="" placeholder="New server name"></t>
                        <td class="bg-secondary"><input class="form-control" type="text" id="globalSetting-serverList-url-new" value="" placeholder="New server url"></td>
                        <td class="bg-secondary"><input class="form-control" type="text" id="globalSetting-serverList-apikey-new" value="" placeholder="New server apikey"></td>
                    </tr>
                    <?php
                }
                ?>
                <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                    <td class="bg-secondary" scope="row">Timeout length</td>
                    <td class="bg-secondary">
                        <input class="form-control" type="number" id="globalSetting-remoteServerTimeout" value="<?= $settingsTable['remoteServerTimeout'] ?: DEFAULT_REMOTE_SERVER_TIMEOUT ?>">
                    </td>
                    <td class="bg-secondary">How long to wait for a remote server to respond, keep in mind 60-90 seconds will throw apache/nginx/cloudflare timeouts</td>
                </tr>
                </tbody>
            </table>
        </div>
        <h4 class="text-info">Containers</h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col" width="15%">Name</th>
                        <th class="bg-primary ps-3" scope="col" width="30%">Setting</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col" width="55%">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Updates<sup>1</sup></td>
                        <td class="bg-secondary">
                            <select class="form-select d-inline-block w-50" id="globalSetting-updates">
                                <option <?= $settingsTable['updates'] == 0 ? 'selected' : '' ?> value="0">Ignore</option>
                                <option <?= $settingsTable['updates'] == 1 ? 'selected' : '' ?> value="1">Auto update</option>
                                <option <?= $settingsTable['updates'] == 2 ? 'selected' : '' ?> value="2">Check for updates</option>
                            </select>
                            <input type="text" class="form-control d-inline-block w-25" id="globalSetting-updatesFrequency" onclick="frequencyCronEditor(this.value, 'global', 'global')" value="<?= $settingsTable['updatesFrequency'] ?>"> <i class="far fa-question-circle" style="cursor: pointer;" title="HELP!" onclick="containerFrequencyHelp()"></i>
                        </td>
                        <td class="bg-secondary">What settings to use for new containers that are added</td>
                    </tr>
                    <tr>
                        <td class="bg-secondary" scope="row">Default GUI</td>
                        <td class="bg-secondary">
                            <select class="form-select d-inline-block" id="globalSetting-containerGui">
                                <option <?= $settingsTable['containerGui'] == 1 ? 'selected' : '' ?> value="1"><?= LOCAL_GUI ?> (Ex: http://10.1.0.1:9999)</option>
                                <option <?= $settingsTable['containerGui'] == 2 ? 'selected' : '' ?> value="2"><?= RP_SUB_GUI ?> (Ex: https://dockwatch.your-domain.com)</option>
                                <option <?= $settingsTable['containerGui'] == 3 ? 'selected' : '' ?> value="3"><?= RP_DIR_GUI ?> (Ex: https://your-domain.com/dockwatch)</option>
                            </select>
                        </td>
                        <td class="bg-secondary">How to build the GUI link for containers. By default it will use the current URL and add the tcp port to the end <?= LOCAL_GUI ?>, example: <code><?= $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] ?>:9999</code>. An alternative would be <?= RP_DIR_GUI ?> or <?= RP_SUB_GUI ?> ({container} is the containers hostname), example: <code>https://dockwatch.mysite.com</code>. If you need to adjust a specific container you can do so in its settings.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h4 class="text-info">Auto prune</h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col" width="15%">Name</th>
                        <th class="bg-primary ps-3" scope="col" width="30%">Setting</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col" width="55%">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Orphan images</td>
                        <td class="bg-secondary">
                            <input class="form-check-input" type="checkbox" id="globalSetting-autoPruneImages" <?= $settingsTable['autoPruneImages'] ? 'checked' : '' ?>>
                        </td>
                        <td class="bg-secondary">Automatically try to prune all orphan images daily</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Orphan volumes</t>
                        <td class="bg-secondary">
                            <input class="form-check-input" type="checkbox" id="globalSetting-autoPruneVolumes" <?= $settingsTable['autoPruneVolumes'] ? 'checked' : '' ?>>
                        </td>
                        <td class="bg-secondary">Automatically try to prune all orphan volumes daily</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Orphan networks</td>
                        <td class="bg-secondary">
                            <input class="form-check-input" type="checkbox" id="globalSetting-autoPruneNetworks" <?= $settingsTable['autoPruneNetworks'] ? 'checked' : '' ?>>
                        </td>
                        <td class="bg-secondary">Automatically try to prune all orphan networks daily</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Hour</td>
                        <td class="bg-secondary">
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
                        <td class="bg-secondary">At which hour the auto prune should run (0-23)</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h4 class="mt-3 text-info">Thresholds</h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col" width="15%">Name</th>
                        <th class="bg-primary ps-3" scope="col" width="30%">Setting</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col" width="55%">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">CPU<sup>1</sup></t>
                        <td class="bg-secondary">
                            <input class="form-control" type="number" id="globalSetting-cpuThreshold" value="<?= $settingsTable['cpuThreshold'] ?>">
                        </td>
                        <td class="bg-secondary">If a container usage is above this number, send a notification (if notification is enabled)</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">CPUs</td>
                        <td class="bg-secondary">
                            <input class="form-control" type="number" id="globalSetting-cpuAmount" value="<?= $settingsTable['cpuAmount'] ?>">
                        </td>
                        <td class="bg-secondary">Detected count: <?= $cpus ?></td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Memory<sup>1</sup></td>
                        <td class="bg-secondary">
                            <input class="form-control" type="number" id="globalSetting-memThreshold" value="<?= $settingsTable['memThreshold'] ?>">
                        </td>
                        <td class="bg-secondary">If a container usage is above this number, send a notification (if notification is enabled)</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h4 class="mt-3 text-info">SSE</h4>
        <div class="table-responsive mt-2">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col" width="15%">Name</th>
                        <th class="bg-primary ps-3" scope="col" width="30%">Setting</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col" width="55%">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Enabled<sup>2,3</sup></td>
                        <td class="bg-secondary">
                            <input class="form-check-input" type="checkbox" id="globalSetting-sseEnabled" <?= $settingsTable['sseEnabled'] ? 'checked' : '' ?>>
                        </td>
                        <td class="bg-secondary">SSE will update the container list UI every minute with current status of Updates, State, Health, CPU and Memory</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h4 class="mt-3 text-info">Logging</h4>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col" width="15%">Name</th>
                        <th class="bg-primary ps-3" scope="col" width="30%">Setting</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col" width="55%">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Crons</td>
                        <td class="bg-secondary">
                            <input class="form-control" type="number" id="globalSetting-cronLogLength" value="<?= $settingsTable['cronLogLength'] <= 1 ? 1 : $settingsTable['cronLogLength'] ?>">
                        </td>
                        <td class="bg-secondary">How long to store cron run log files (min 1 day)</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Notifications</td>
                        <td class="bg-secondary">
                            <input class="form-control" type="number" id="globalSetting-notificationLogLength" value="<?= $settingsTable['notificationLogLength'] <= 1 ? 1 : $settingsTable['notificationLogLength'] ?>">
                        </td>
                        <td class="bg-secondary">How long to store logs generated when notifications are sent (min 1 day)</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">UI</td>
                        <td class="bg-secondary">
                            <input class="form-control" type="number" id="globalSetting-uiLogLength" value="<?= $settingsTable['uiLogLength'] <= 1 ? 1 : $settingsTable['uiLogLength'] ?>">
                        </td>
                        <td class="bg-secondary">How long to store logs generated from using the UI (min 1 day)</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">API</td>
                        <td class="bg-secondary">
                            <input class="form-control" type="number" id="globalSetting-apiLogLength" value="<?= $settingsTable['apiLogLength'] <= 1 ? 1 : $settingsTable['apiLogLength'] ?>">
                        </td>
                        <td class="bg-secondary">How long to store logs generated when api requests are made (min 1 day)</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h4 class="mt-3"><i class="far fa-plus-square text-light development-settings" onclick="$('.development-settings').toggle()"></i> <i class="far fa-minus-square text-light development-settings" onclick="$('.development-settings').toggle()" style="display: none;"></i> <span class="text-info">Development</span></h4>
        <div class="table-responsive development-settings" style="display: none;">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col" width="15%">Name</th>
                        <th class="bg-primary ps-3" scope="col" width="30%">Setting</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col" width="55%">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Migration</td>
                        <td class="bg-secondary"><select class="form-select" id="globalSetting-migration"><?= $migrations ?></select></td>
                        <td class="bg-secondary">The database migration this server is on, changing this will re-apply all subsequent migrations and reset any settings they alter.</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Database</td>
                        <td class="bg-secondary"><button class="btn btn-sm btn-info" onclick="initPage('database')">Browse</button></td>
                        <td class="bg-secondary">Browse the <?= APP_NAME ?> database</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">State cron time</td>
                        <td class="bg-secondary">
                            <select class="form-select" id="globalSetting-stateCronTime">
                                <option <?= $settingsTable['stateCronTime'] == 1 ? 'selected' : '' ?> value="1">1m</option>
                                <option <?= $settingsTable['stateCronTime'] == 2 ? 'selected' : '' ?> value="2">2m</option>
                                <option <?= $settingsTable['stateCronTime'] == 3 ? 'selected' : '' ?> value="3">3m</option>
                                <option <?= $settingsTable['stateCronTime'] == 4 ? 'selected' : '' ?> value="4">4m</option>
                                <option <?= $settingsTable['stateCronTime'] == 5 ? 'selected' : '' ?> value="5">5m</option>
                            </select>
                        </td>
                        <td class="bg-secondary">This can impact performance on your server so keep that in mind!</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Override blacklist</td>
                        <td class="bg-secondary"><input class="form-check-input" type="checkbox" id="globalSetting-overrideBlacklist" <?= $settingsTable['overrideBlacklist'] ? 'checked' : '' ?>></td>
                        <td class="bg-secondary">Generally not recommended, it's at your own risk.</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Environment<sup>5</sup></td>
                        <td class="bg-secondary">
                            <select class="form-select" id="globalSetting-environment">
                                <option <?= $settingsTable['environment'] == 0 ? 'selected' : '' ?> value="0">Internal</option>
                                <option <?= $settingsTable['environment'] == 1 ? 'selected' : '' ?> value="1">External</option>
                            </select>
                        </td>
                        <td class="bg-secondary">Location of webroot, do not change this without working files externally!</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Debug zip</td>
                        <td class="bg-secondary">
                            <input class="form-check-input" type="checkbox" id="globalSetting-debugZipDatabase"> <label for="globalSetting-debugZipDatabase">Database</label>
                            <input class="form-check-input" type="checkbox" id="globalSetting-debugZipLogs"> <label for="globalSetting-debugZipLogs">Logs</label>
                            <input class="form-check-input" type="checkbox" id="globalSetting-debugZipJson"> <label for="globalSetting-debugZipJson">json</label>
                        </td>
                        <td class="bg-secondary">This does not save but triggers a zip file to be created (<code><?= APP_DATA_PATH . 'dockwatch.zip' ?></code>) when clicking save. Should only be needed when asked for by developers and includes (based on selection) database/*, logs/crons/*, logs/api/*, logs/system/*, settings, state, pull, health, stats & dependency json files</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Telemetry</td>
                        <td class="bg-secondary"><input class="form-check-input" type="checkbox" id="globalSetting-telemetry" <?= $settingsTable['telemetry'] ? 'checked' : '' ?>></td>
                        <td class="bg-secondary">Allow telemetry information to be collected. There is nothing personal or identifiable and what is sent can be seen in the Tasks menu or <a href="https://github.com/Notifiarr/dockwatch/blob/develop/root/app/www/public/functions/telemetry.php" target="_blank">here on github</a></td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Maintenance port</td>
                        <td class="bg-secondary">
                            <input class="form-control" type="text" id="globalSetting-maintenancePort" value="<?= $settingsTable['maintenancePort'] ?: APP_MAINTENANCE_PORT ?>">
                        </td>
                        <td class="bg-secondary">This port is used to do updates/restarts for Dockwatch. It will create another container <code>dockwatch-maintenance</code> with this port and after it has updated/restarted Dockwatch it will be removed.</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">WebSocket port<sup>5</sup></td>
                        <td class="bg-secondary">
                            <input class="form-control" type="text" id="globalSetting-websocketPort" value="<?= $settingsTable['websocketPort'] ?: APP_WEBSOCKET_PORT ?>">
                        </td>
                        <td class="bg-secondary">Best to leave default at <code>9910</code>. You only need to change this if you wish to route this through a different RP. <br>By changing the port, the internal RP will be disabled.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div align="center"><button type="button" class="btn btn-success m-2" onclick="saveGlobalSettings()">Save Changes</button></div>
        <sup>1</sup> Checked every 5 minutes<br>
        <sup>2</sup> Updates every minute<br>
        <sup>3</sup> Requires a page reload<br>
        <sup>4</sup> Only for Network IO and Disk Usage<br>
        <sup>5</sup> Requires a container restart
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

    //-- BASE URL VALIDATION
    $baseUrl = rtrim($_POST['baseUrl']);
    if (file_exists(BASE_URL_FILE) && empty($baseUrl)) {
        unlink(BASE_URL_FILE);
    } else {
        if (preg_match('#^/[\w-]*$#', $baseUrl) && $baseUrl !== '/') {
            file_put_contents(BASE_URL_FILE, $baseUrl);
        }
    }

    $settingsTable = apiRequest('database/settings', [], ['newSettings' => $newSettings])['result'];

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

        $serversTable = apiRequest('database/servers', [], ['serverList' => $serverList])['result'];
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

    echo json_encode(['error' => $error, 'server' => ACTIVE_SERVER_NAME]);
}

//-- CALLED FROM THE NAV MENU SELECT
if ($_POST['m'] == 'updateActiveServer') {
    apiSetActiveServer(intval($_POST['id']));
}

if ($_POST['m'] == 'unlinkRemoteServer') {
    $serversTable[intval($_POST['id'])]['remove'] = true;
    apiRequest('database/servers', [], ['serverList' => $serversTable]);
}

if ($_POST['m'] == 'updateSetting') {
    $database->setSetting($_POST['setting'], $_POST['value']);
}
