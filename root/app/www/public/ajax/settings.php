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
    ?>
    <div class="container-fluid pt-4 px-4">
        <div class="bg-secondary rounded h-100 p-4">
            <h4>New Containers</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Setting</th>
                            <th scope="col">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Updates<sup>1</sup></th>
                            <td>
                                <select class="form-control" id="globalSetting-updates">
                                    <option <?= ($globalSettings['updates'] == 0 ? 'selected' : '') ?> value="0">Ignore</option>
                                    <option <?= ($globalSettings['updates'] == 1 ? 'selected' : '') ?> value="1">Auto update</option>
                                    <option <?= ($globalSettings['updates'] == 2 ? 'selected' : '') ?> value="2">Check for updates</option>
                                </select>
                            </td>
                            <td>What settings to use for new containers that are added</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h4 class="mt-3">Thresholds</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Setting</th>
                            <th scope="col">Description</th>
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
                            <th scope="col">Name</th>
                            <th scope="col">Setting</th>
                            <th scope="col">Description</th>
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
            <div align="center"><button type="button" class="btn btn-info m-2" onclick="saveGlobalSettings()">Save Changes</button></div>
            <sup>1</sup> Checked every 5 minutes
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'saveGlobalSettings') {
    $newSettings = [];

    foreach ($_POST as $key => $val) {
        if ($key == 'm') {
            continue;
        }

        $newSettings[$key] = $val;
    }

    $settings['global'] = $newSettings;
    setFile(SETTINGS_FILE, $settings);
}
