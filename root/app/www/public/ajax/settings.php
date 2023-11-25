<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $globalSettings = $settings['global'];
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
                                <select class="form-control d-inline-block w-25" id="globalSetting-updates">
                                    <option <?= ($globalSettings['updates'] == 0 ? 'selected' : '') ?> value="0">Ignore</option>
                                    <option <?= ($globalSettings['updates'] == 1 ? 'selected' : '') ?> value="1">Auto update</option>
                                    <option <?= ($globalSettings['updates'] == 2 ? 'selected' : '') ?> value="2">Check for updates</option>
                                </select>
                                <select class="form-control d-inline-block w-25" id="globalSetting-updatesFrequency">
                                    <option <?= ($globalSettings['updatesFrequency'] == '12h' ? 'selected' : '') ?> value="12h">12h</option>
                                    <option <?= ($globalSettings['updatesFrequency'] == '1d' ? 'selected' : '') ?> value="1d">1d</option>
                                    <option <?= ($globalSettings['updatesFrequency'] == '2d' ? 'selected' : '') ?> value="2d">2d</option>
                                    <option <?= ($globalSettings['updatesFrequency'] == '3d' ? 'selected' : '') ?> value="3d">3d</option>
                                    <option <?= ($globalSettings['updatesFrequency'] == '4d' ? 'selected' : '') ?> value="4d">4d</option>
                                    <option <?= ($globalSettings['updatesFrequency'] == '5d' ? 'selected' : '') ?> value="5d">5d</option>
                                    <option <?= ($globalSettings['updatesFrequency'] == '6d' ? 'selected' : '') ?> value="6d">6d</option>
                                    <option <?= ($globalSettings['updatesFrequency'] == '1w' ? 'selected' : '') ?> value="1w">1w</option>
                                    <option <?= ($globalSettings['updatesFrequency'] == '2w' ? 'selected' : '') ?> value="2w">2w</option>
                                    <option <?= ($globalSettings['updatesFrequency'] == '3w' ? 'selected' : '') ?> value="3w">3w</option>
                                    <option <?= ($globalSettings['updatesFrequency'] == '1m' ? 'selected' : '') ?> value="1m">1m</option>
                                </select>
                                <select class="form-control d-inline-block w-25" id="globalSetting-updatesHour">
                                    <?php
                                    for ($h = 0; $h <= 23; $h++) {
                                        ?><option <?= ($globalSettings['updatesHour'] == $h ? 'selected' : '') ?> value="<?= $h ?>"><?= $h ?></option><?php
                                    }
                                    ?>
                                </select>
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
            <h4 class="mt-3">Memcached</h4>
            Optionally memcached can be used to speed things up in the UI while navigating
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
                            <th scope="row">Enabled</th>
                            <td><?= ($memcache ? 'Yes' : 'No') ?></td>
                            <td>Is memcached connected and being used</td>
                        </tr>
                        <tr>
                            <th scope="row">Prefix</th>
                            <td><?= MEMCACHE_PREFIX ?></td>
                            <td>Everything in memcached will be prefixed with this key to ensure no collisions</td>
                        </tr>
                        <tr>
                            <th scope="row">Server</th>
                            <td>
                                <input class="form-control" type="text" id="globalSetting-memcachedServer" value="<?= $globalSettings['memcachedServer'] ?>">
                            </td>
                            <td>The container, ip, hostname of where memcached is installed</td>
                        </tr>
                        <tr>
                            <th scope="row">Port</th>
                            <td>
                                <input class="form-control" type="number" id="globalSetting-memcachedPort" value="<?= $globalSettings['memcachedPort'] ?>">
                            </td>
                            <td>The memcached port (default is 11211)</td>
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
                                <select class="form-control" id="globalSetting-environment">
                                    <option <?= ($globalSettings['environment'] == 0 ? 'selected' : '') ?> value="0">Internal</option>
                                    <option <?= ($globalSettings['environment'] == 1 ? 'selected' : '') ?> value="1">External</option>
                                </select>
                            </td>
                            <td>Location of webroot, requires a container restart after changing. Do not change this without working files externally!</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div align="center"><button type="button" class="btn btn-info m-2" onclick="saveGlobalSettings()">Save Changes</button></div>
            <sup>1</sup> Checked every 5 minutes
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'saveGlobalSettings') {
    $currentSettings = getFile(SETTINGS_FILE);
    $newSettings = [];

    foreach ($_POST as $key => $val) {
        if ($key == 'm') {
            continue;
        }

        $newSettings[$key] = trim($val);
    }

    if ($currentSettings['global']['environment'] != $_POST['environment']) {
        if ($_POST['environment'] == 0) { //-- USE INTERNAL
            linkWebroot('internal');
        } else { //-- USE EXTERNAL
            linkWebroot('external');
        }
    }

    $settings['global'] = $newSettings;
    setFile(SETTINGS_FILE, $settings);
}
