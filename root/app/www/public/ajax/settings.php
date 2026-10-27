<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

//-- COMPOSER: IP VALIDATOR
use Chialab\Ip;

if ($_POST['m'] == 'init') {
    $cpus = cpuTotal();
    if ($cpus == 0) {
        $cpus = '0 (Could not get cpu count from /proc/cpuinfo)';
    }

    $migrations = '';

    if (intval($settingsTable['migration']) == -1) {
        $migrations .= '<option selected value="-1">Fresh Start</option>';
    }

    $dir = opendir(MIGRATIONS_PATH);
    while ($migration = readdir($dir)) {
        if (str_contains($migration, '.php')) {
            $migrationId = substr($migration, 0, 3);
            $migrationNo = intval($migrationId);

            $migrationLabel = str_replace('.php', '', $migration);
            if ($migrationNo >= 1 && $migrationNo <= 22) {
                $migrationLabel .= ' [Legacy SQLite3]';
            }

            $selected    = $settingsTable['migration'] == $migrationId;
            $disabled    = $migrationNo >= 1 && $migrationNo <= 22;
            $migrations .= '<option ' . ($selected ? 'selected ' : '') . ($disabled ? 'disabled ' : '') . 'value="' . $migrationId . '">' . $migrationLabel . '</option>';
        }
    }
    closedir($dir);

    $serverTime = apiRequest('server/time')['result']['result'];

    $usersTable              = $_SESSION['activeServerId'] == APP_SERVER_ID ? (apiRequest('database/users')['result'] ?: []) : [];
    $mergedUsers             = mergeUserSources($usersTable);
    $hasLoginFile            = loginFileHasEntries();
    $databaseActiveUserCount = 0;

    foreach ($usersTable as $user) {
        if (!empty($user['active'])) {
            $databaseActiveUserCount++;
        }
    }

    $databaseToolsLocal = $_SESSION['activeServerId'] == APP_SERVER_ID;
    $backupTime         = normalizeBackupTime($settingsTable['backupTime'] ?? '03:00');
    $browseSummary      = $databaseToolsLocal ? $database->browseTablesSummary() : [];
    ?>
    <div class="bg-secondary rounded p-4">
        <ul class="nav nav-tabs settings-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" id="settings-instance-tab" data-bs-toggle="tab" data-bs-target="#settings-instance" type="button" role="tab">Instance</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="settings-ui-tab" data-bs-toggle="tab" data-bs-target="#settings-ui" type="button" role="tab">UI</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="settings-logins-tab" data-bs-toggle="tab" data-bs-target="#settings-logins" type="button" role="tab">Logins</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="settings-servers-tab" data-bs-toggle="tab" data-bs-target="#settings-servers" type="button" role="tab">Servers</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="settings-containers-tab" data-bs-toggle="tab" data-bs-target="#settings-containers" type="button" role="tab">Containers</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="settings-thresholds-tab" data-bs-toggle="tab" data-bs-target="#settings-thresholds" type="button" role="tab">Thresholds</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="settings-logging-tab" data-bs-toggle="tab" data-bs-target="#settings-logging" type="button" role="tab">Logging</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="settings-database-tab" data-bs-toggle="tab" data-bs-target="#settings-database" type="button" role="tab">Database</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="settings-development-tab" data-bs-toggle="tab" data-bs-target="#settings-development" type="button" role="tab">Development</button></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="settings-instance" role="tabpanel">
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
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
                            <input class="form-control" type="text" id="globalSetting-websocketUrl" value="<?= $settingsTable['websocketUrl'] ?: '' ?>" placeholder="<?= 'ws://' . $_SERVER['HTTP_HOST'] . '/ws' ?>">
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
            </div>
            <div class="tab-pane fade" id="settings-ui" role="tabpanel">
        <h5 class="text-info mb-2">Interface</h5>
        <div class="border border-secondary rounded p-3 mb-4">
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
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
                                $defaultPages = ['overview', 'containers', 'networks', 'compose', 'orphans', 'notification', 'settings', 'tasks', 'commands', 'logs', 'security'];

                                foreach ($defaultPages as $defaultPage) {
                                    ?>
                                    <option <?= $settingsTable['defaultPage'] == $defaultPage ? 'selected' : '' ?> value="<?= $defaultPage ?>"><?= ucfirst($defaultPage) ?></option><?php
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
        </div>
        <h5 class="text-info mb-2">SSE</h5>
        <div class="border border-secondary rounded p-3">
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
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
        </div>
            </div>
            <div class="tab-pane fade" id="settings-logins" role="tabpanel">
        <h5 class="text-info mb-2">Failures</h5>
        <div class="border border-secondary rounded p-3 mb-4">
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
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
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Whitelist</t>
                        <td class="bg-secondary">
                            <input class="form-control" type="text" id="globalSetting-loginWhitelist" placeholder="192.168.178.0/24,172.28.0.0/16" value="<?= $settingsTable['loginWhitelist'] ?>">
                        </td class="bg-secondary">
                        <td class="bg-secondary">Comma-separated list of allowed IP subnets to ignore in intrusion notifications.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>
        <h5 class="text-info mb-2">Users</h5>
        <div class="border border-secondary rounded p-3">
        <p class="text-muted small mb-3">No active users and an empty/missing login file will disable login auth.</p>
        <input type="hidden" id="settings-hasLoginFile" value="<?= $hasLoginFile ? 1 : 0 ?>">
        <input type="hidden" id="settings-databaseActiveUserCount" value="<?= $databaseActiveUserCount ?>">
        <input type="hidden" id="settings-userManagement" value="<?= $_SESSION['activeServerId'] == APP_SERVER_ID ? 1 : 0 ?>">
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col" width="25%">Username</th>
                        <th class="bg-primary ps-3" scope="col" width="25%">Password</th>
                        <th class="bg-primary ps-3" scope="col" width="10%">Active</th>
                        <th class="bg-primary ps-3" scope="col" width="20%">Source</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col" width="20%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($_SESSION['activeServerId'] != APP_SERVER_ID) {
                        ?>
                        <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                            <td class="bg-secondary" colspan="5">User management is only available on the local server.</td>
                        </tr>
                        <?php
                    } else {
                        foreach ($mergedUsers as $mergedUser) {
                            $inDatabase  = !empty($mergedUser['database']);
                            $inLoginFile = !empty($mergedUser['loginFile']);
                            $dbUser      = $mergedUser['database'];
                            $rowClass    = $inDatabase ? 'user-row' : 'login-file-user';
                            ?>
                            <tr class="<?= $rowClass ?> border border-dark border-top-0 border-start-0 border-end-0" <?= $inDatabase ? 'id="userRow-' . $dbUser['id'] . '"' : '' ?>>
                                <td class="bg-secondary">
                                    <?php if ($inDatabase) { ?>
                                        <input class="form-control" type="text" id="globalSetting-userList-username-<?= $dbUser['id'] ?>" value="<?= htmlspecialchars($mergedUser['username'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?php } else { ?>
                                        <input class="form-control" type="text" value="<?= htmlspecialchars($mergedUser['username'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                    <?php } ?>
                                </td>
                                <td class="bg-secondary">
                                    <?php if ($inDatabase) { ?>
                                        <input class="form-control" type="password" id="globalSetting-userList-password-<?= $dbUser['id'] ?>" placeholder="unchanged if blank">
                                    <?php } else { ?>
                                        <input class="form-control" type="password" value="" placeholder="***" disabled>
                                    <?php } ?>
                                </td>
                                <td class="bg-secondary text-center">
                                    <?php if ($inDatabase) { ?>
                                        <input class="form-check-input" type="checkbox" id="globalSetting-userList-active-<?= $dbUser['id'] ?>" <?= $dbUser['active'] ? 'checked' : '' ?>>
                                    <?php } else { ?>
                                        —
                                    <?php } ?>
                                </td>
                                <td class="bg-secondary">
                                    <?php if ($inDatabase) { ?><span class="badge bg-primary me-1">Database</span><?php } ?>
                                    <?php if ($inLoginFile) { ?><span class="badge bg-secondary">File</span><?php } ?>
                                </td>
                                <td class="bg-secondary">
                                    <?php if ($inDatabase) { ?>
                                        <i class="far fa-trash-alt text-warning" style="cursor: pointer;" title="Remove database user" onclick="removeUser('<?= $dbUser['id'] ?>')"></i>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                            <td class="bg-secondary"><input class="form-control" type="text" id="globalSetting-userList-username-new" placeholder="New username"></td>
                            <td class="bg-secondary"><input class="form-control" type="password" id="globalSetting-userList-password-new" placeholder="New password"></td>
                            <td class="bg-secondary text-center"><input class="form-check-input" type="checkbox" id="globalSetting-userList-active-new" checked></td>
                            <td class="bg-secondary"><span class="badge bg-primary">Database</span></td>
                            <td class="bg-secondary"></td>
                        </tr>
                        <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                            <td class="bg-secondary" colspan="5">
                                When at least one user is active or the login file has entries, the UI requires login.
                                File-only accounts are read-only here; edit <code><?= LOGIN_FILE ?></code> on the host to add, change, or remove them.
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        </div>
            </div>
            <div class="tab-pane fade" id="settings-servers" role="tabpanel">
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
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
            </div>
            <div class="tab-pane fade" id="settings-containers" role="tabpanel">
        <h5 class="text-info mb-2">Containers</h5>
        <div class="border border-secondary rounded p-3 mb-4">
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
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
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
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
        </div>
        <h5 class="text-info mb-2">Security</h5>
        <div class="border border-secondary rounded p-3 mb-4">
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col" width="15%">Name</th>
                        <th class="bg-primary ps-3" scope="col" width="30%">Setting</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col" width="55%">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Enabled<sup>3,5</sup></td>
                        <td class="bg-secondary">
                            <input class="form-check-input" type="checkbox" id="globalSetting-securityEnabled" <?= $settingsTable['securityEnabled'] ? 'checked' : '' ?>>
                        </td>
                        <td class="bg-secondary">Automatically scan for vulnerabilities in container images</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Scanner app</td>
                        <td class="bg-secondary">
                            <select class="form-select" id="globalSetting-securityScanner">
                                <?php
                                $scanners = [
                                    'trivy' => SecurityScanner::TRIVY_ID,
                                    'grype' => SecurityScanner::GRYPE_ID,
                                    'snyk'  => SecurityScanner::SNYK_ID,
                                ];

                                foreach ($scanners as $scanner => $id) {
                                    ?>
                                    <option <?= $settingsTable['securityScanner'] == $id ? 'selected' : '' ?> value="<?= $id ?>"><?= ucfirst($scanner) ?></option><?php
                                }
                                ?>
                                <script>
                                    $('#globalSetting-securityScanner').on('change', function () {
                                        if (this.value == '<?= SecurityScanner::SNYK_ID ?>') {
                                            $('#globalSetting-securitySnykAPIKey').attr('readonly', false);
                                        } else {
                                            $('#globalSetting-securitySnykAPIKey').attr('readonly', true);
                                        }
                                    });
                                </script>
                            </select>
                        </td>
                        <td class="bg-secondary">
                            Which scanner app should be used to scan containers for vulnerabilities
                            <ul style="padding: 0 18px;">
                                <li>Grype and Trivy are both free open-source apps</li>
                                <li>Snyk requires a (free) <a href="https://app.snyk.io" target="_blank">account</a> API key to work</li>
                                <li>
                                    <span class="text-warning">
                                        <i class="fas fa-exclamation-triangle dw-tooltip-icon" style="font-size: 12px !important; transform: translateY(-2px); color: yellow !important;"></i>
                                        Highly recommended to allocate atleast 4GB ram to Dockwatch to prevent the scanner from running out of memory (<a href="https://github.com/Notifiarr/dockwatch/issues/145" target="_blank">GH#145</a>)
                                    </span>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0" id="snyk-api-key">
                        <td class="bg-secondary" scope="row">Snyk API key</td>
                        <td class="bg-secondary">
                            <input class="form-control" type="password" id="globalSetting-securitySnykAPIKey" value="<?= $settingsTable['securitySnykAPIKey'] ?>" <?= $settingsTable['securityScanner'] == SecurityScanner::SNYK_ID ? '' : 'readonly' ?> placeholder="Snyk API key...">
                        </td>
                        <td class="bg-secondary">Required API key for Snyk CLI to function. You can generate one <a href="https://app.snyk.io/account/personal-access-tokens" target="_blank">here</a>.</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Hour</td>
                        <td class="bg-secondary">
                            <select class="form-select" id="globalSetting-securityScanHour">
                                <?php
                                $option = '';
                                for ($x = 0; $x <= 23; $x++) {
                                    $option .= '<option ' . ($x == intval($settingsTable['securityScanHour']) || !$settingsTable['securityScanHour'] && $x == 12 ? 'selected' : '') . ' value="' . $x . '">' . $x . '</option>';
                                }
                                echo $option;
                                ?>
                            </select>
                        </td>
                        <td class="bg-secondary">At which hour security should run (0-23)</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Scan retention</td>
                        <td class="bg-secondary">
                            <input class="form-control" type="number" id="globalSetting-securityScanLength" value="<?= $settingsTable['securityScanLength'] <= 2 ? 2 : $settingsTable['securityScanLength'] ?>">
                        </td>
                        <td class="bg-secondary">How long to store past scan files (min 2 days)</td>
                    </tr>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Skip stopped containers</td>
                        <td class="bg-secondary">
                            <input class="form-check-input" type="checkbox" id="globalSetting-securitySkipStopped" <?= $settingsTable['securitySkipStopped'] ? 'checked' : '' ?>>
                        </td>
                        <td class="bg-secondary">Do not automatically scan containers that are currently inactive</td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>
        <h5 class="text-info mb-2">Auto prune</h5>
        <div class="border border-secondary rounded p-3">
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
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
        </div>
            </div>
            <div class="tab-pane fade" id="settings-thresholds" role="tabpanel">
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
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
            </div>
            <div class="tab-pane fade" id="settings-logging" role="tabpanel">
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col" width="15%">Name</th>
                        <th class="bg-primary ps-3" scope="col" width="30%">Setting</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col" width="55%">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Log level</td>
                        <td class="bg-secondary">
                            <select class="form-select" id="globalSetting-logLevel">
                                <option value="<?= LOG_LEVEL_INFO ?>" <?= !$settingsTable['logLevel'] || $settingsTable['logLevel'] == LOG_LEVEL_INFO ? 'selected' : '' ?>>Info</option>
                                <option value="<?= LOG_LEVEL_DEBUG ?>" <?= $settingsTable['logLevel'] == LOG_LEVEL_DEBUG ? 'selected' : '' ?>>Debug</option>
                            </select>
                        </td>
                        <td class="bg-secondary">Debug includes full JSON payloads (Docker inspects, state snapshots, etc.)</td>
                    </tr>
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
            </div>
            <div class="tab-pane fade" id="settings-database" role="tabpanel">
                <h5 class="text-info mb-2">Migration</h5>
                <div class="border border-secondary rounded p-3 mb-4">
                    <div class="d-flex gap-2 align-items-center mb-2">
                        <select class="form-select" id="globalSetting-migration"><?= $migrations ?></select>
                        <button type="button" class="btn btn-outline-warning btn-sm text-nowrap" onclick="freshStartMigration()">Fresh Start <sup>5</sup></button>
                    </div>
                    <div class="small">The database migration this server is on. Changing it re-applies every later migration and resets settings those migrations alter. Save Changes stores it.</div>
                </div>
                <?php if ($databaseToolsLocal) { ?>
                    <h5 class="text-info mb-2">Backups</h5>
                    <div class="border border-secondary rounded p-3 mb-4">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="globalSetting-backupTime" class="form-label">Backup time</label>
                                <select class="form-select" id="globalSetting-backupTime">
                                    <?php foreach (range(0, 23) as $hour) { ?>
                                        <?php foreach ([0, 10, 20, 30, 40, 50] as $minute) {
                                            $option = sprintf('%02d:%02d', $hour, $minute);
                                            $label  = date('g:i A', mktime($hour, $minute, 0));
                                            ?>
                                            <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= $option == $backupTime ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php } ?>
                                    <?php } ?>
                                </select>
                                <div class="form-text">The dispatcher runs one automatic backup a day at this time.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="globalSetting-backupKeep" class="form-label">Days to keep</label>
                                <input class="form-control" type="number" min="1" id="globalSetting-backupKeep" value="<?= max(1, intval($settingsTable['backupKeep'] ?? APP_BACKUPS)) ?>">
                                <div class="form-text">Housekeeper removes backup folders older than this many days.</div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success" onclick="backupDatabase()">Run backup</button>
                        <div class="small mt-2">Each run dumps every table to its own <code>.sql</code> file under <code><?= BACKUP_PATH ?></code> and copies config JSON plus compose stacks into that folder. Backup time and retention are stored with Save Changes.</div>
                        <div id="backupList" class="mt-3"><?= backupListHtml() ?></div>
                    </div>
                    <h5 class="text-info mb-2">Browse</h5>
                    <div class="border border-secondary rounded p-3 mb-4">
                        <div id="browseDatabaseList">
                            <?php
                            $browseView = 'list';
                            require ABSOLUTE_PATH . 'includes/settings/browseDatabase.php';
                            ?>
                        </div>
                    </div>
                    <h5 class="text-info mb-2">Query</h5>
                    <div class="border border-secondary rounded p-3">
                        <textarea class="form-control font-monospace" id="browseDatabaseQuery" rows="5" placeholder="One SQL statement"></textarea>
                        <button type="button" class="btn btn-primary mt-2" onclick="runDatabaseQuery();">Run</button>
                        <div id="browseDatabaseQueryResult" class="mt-3"></div>
                    </div>
                <?php } else { ?>
                    <p class="mb-0">Browse, backup, restore, and query run on this Dockwatch instance only.</p>
                <?php } ?>
            </div>
            <div class="tab-pane fade" id="settings-development" role="tabpanel">
        <div class="table-responsive">
            <table class="table table-sm table-no-squish">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3" scope="col" width="15%">Name</th>
                        <th class="bg-primary ps-3" scope="col" width="30%">Setting</th>
                        <th class="rounded-top-right-1 bg-primary ps-3" scope="col" width="55%">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                        <td class="bg-secondary" scope="row">Containers clean up</td>
                        <td class="bg-secondary"><button class="btn btn-sm btn-warning" onclick="cleanupContainers()">Clean</button></td>
                        <td class="bg-secondary">Remove all containers from the database that no longer exist in Docker. Use with caution.</td>
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
                        <td class="bg-secondary">Allow manual updates of network containers (nginx, caddy, cloudflared, etc.) that are typically skipped. It's generally not recommended to enable this override.</td>
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
            </div>
        </div>
    </div>
    <div class="bg-secondary rounded p-4 mt-3">
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
    $activeServer = apiGetActiveServer();
    $newSettings  = [];
    $requireLogin = false;
    $authDisabled = false;

    foreach ($_POST as $key => $val) {
        if ($key == 'm' || str_contains($key, 'serverList') || str_contains($key, 'userList')) {
            continue;
        }

        if ($key == 'updatesFrequency') {
            try {
                $cron = Cron\CronExpression::factory($val);
            } catch (Exception $e) {
                $val = DEFAULT_CRON;
            }
        }

        if ($key == 'backupTime') {
            $val = normalizeBackupTime($val);
        }

        if ($key == 'backupKeep') {
            $val = max(1, $val);
        }

        $newSettings[$key] = trim($val);
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

    //-- SUBNET VALIDATION
    if (!empty($_POST['loginWhitelist'])) {
        $ipandmask = explode(',', $_POST['loginWhitelist']) ?: [];
        if (count($ipandmask) > 0) {
            foreach ($ipandmask as $ipmask) {
                $ipmask = trim($ipmask);
                try {
                    if (str_contains($ipmask, '/')) {
                        Ip\Subnet::parse($ipmask);
                    } else {
                        Ip\Address::parse($ipmask);
                    }
                } catch (\InvalidArgumentException $e) {
                    echo json_encode(['error' => $ipmask . ' is not a valid ip or subnet']);
                    exit;
                }
            }
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

        $userList = [];

        if ($_POST['userList-username-new'] && $_POST['userList-password-new']) {
            $userList[] = [
                'username' => $_POST['userList-username-new'],
                'password' => $_POST['userList-password-new'],
                'active'   => $_POST['userList-active-new'] ? 1 : 0,
            ];
        }

        foreach ($_POST as $key => $val) {
            if (!str_contains($key, 'userList-password')) {
                continue;
            }

            list($name, $field, $instanceId) = explode('-', $key);

            if (!is_numeric($instanceId)) {
                continue;
            }

            if ($_POST['userList-username-' . $instanceId]) {
                $userList[$instanceId] = [
                    'username' => $_POST['userList-username-' . $instanceId],
                    'active'   => $_POST['userList-active-' . $instanceId] ? 1 : 0,
                ];

                if ($_POST['userList-password-' . $instanceId]) {
                    $userList[$instanceId]['password'] = $_POST['userList-password-' . $instanceId];
                }
            }
        }

        if ($userList) {
            apiRequest('database/users', [], ['userList' => $userList]);

            $authChange   = applyUserAuthChange();
            $requireLogin = $authChange['requireLogin'];
            $authDisabled = $authChange['authDisabled'];
        }
    }

    if ($_POST['debugZipDatabase'] || $_POST['debugZipLogs'] || $_POST['debugZipJson']) {
        $zipfile = APP_DATA_PATH . 'dockwatch.zip';

        $zip = new ZipArchive();
        $zip->open($zipfile, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);

        if ($_POST['debugZipDatabase']) {
            $zip->addEmptyDir(str_replace(APP_DATA_PATH, '', DATABASE_PATH));
            $databaseBackupToday     = file_exists(DATABASE_PATH . date('Ymd') . '/' . DB_NAME . '.sql'); //-- CHECK FOR TODAY'S BACKUP
            $databaseBackupYesterday = file_exists(DATABASE_PATH . date('Ymd', strtotime('-1 day')) . '/' . DB_NAME . '.sql'); //-- CHECK FOR YESTERDAY'S BACKUP

            if ($databaseBackupToday) {
                $zip->addFile(DATABASE_PATH . date('Ymd') . '/' . DB_NAME . '.sql', str_replace(APP_DATA_PATH, '', DATABASE_PATH) . date('Ymd') . '/' . DB_NAME . '.sql');
            } elseif ($databaseBackupYesterday) {
                $zip->addFile(DATABASE_PATH . date('Ymd', strtotime('-1 day')) . '/' . DB_NAME . '.sql', str_replace(APP_DATA_PATH, '', DATABASE_PATH) . date('Ymd', strtotime('-1 day')) . '/' . DB_NAME . '.sql');
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

    echo json_encode([
        'error'        => $error ?? '',
        'server'       => ACTIVE_SERVER_NAME,
        'requireLogin' => !empty($requireLogin),
        'authDisabled' => !empty($authDisabled),
    ]);
}

//-- CALLED FROM THE NAV MENU SELECT
if ($_POST['m'] == 'updateActiveServer') {
    apiSetActiveServer(intval($_POST['id']));
}

if ($_POST['m'] == 'unlinkRemoteServer') {
    $serversTable[intval($_POST['id'])]['remove'] = true;
    apiRequest('database/servers', [], ['serverList' => $serversTable]);
}

if ($_POST['m'] == 'removeUser') {
    if ($_SESSION['activeServerId'] != APP_SERVER_ID) {
        echo json_encode(['error' => 'User management is only available on the local server']);
        exit;
    }

    $userId   = intval($_POST['id']);
    $userList = [$userId => ['remove' => true]];

    apiRequest('database/users', [], ['userList' => $userList]);

    $authChange = applyUserAuthChange();

    echo json_encode([
        'error'        => '',
        'requireLogin' => !empty($authChange['requireLogin']),
        'authDisabled' => !empty($authChange['authDisabled']),
    ]);
    exit;
}

if ($_POST['m'] == 'updateSetting') {
    $database->setSetting($_POST['setting'], $_POST['value']);
}

if ($_POST['m'] == 'cleanupContainers') {
    $containersTable      = apiRequest('database/containers')['result'];
    $containerGroupsTable = apiRequest('database/group/links')['result'];
    $removed              = 0;

    foreach ($containersTable as $container) {
        $containerState = $docker->findContainer(['hash' => $container['hash'], 'data' => $stateFile]);
        if (!$containerState) {
            apiRequest('database/container/delete', [], ['hash' => $container['hash']]);

            foreach ($containerGroupsTable as $containerGroup) {
                if ($containerGroup['container_id'] == $container['id']) {
                    apiRequest('database/group/container/link/remove', [], ['groupId' => $containerGroup['group_id'], 'containerId' => $container['id']]);
                }
            }

            $removed++;
        }
    }

    echo json_encode(['removed' => $removed]);
}

if ($_POST['m'] == 'previewCleanupContainers') {
    $containersTable   = apiRequest('database/containers')['result'];
    $cleanupContainers = [];

    foreach ($containersTable as $container) {
        $containerState = $docker->findContainer(['hash' => $container['hash'], 'data' => $stateFile]);
        if (!$containerState) {
            $cleanupContainers[] = $container['hash'];
        }
    }

    echo json_encode(['containers' => $cleanupContainers]);
}
