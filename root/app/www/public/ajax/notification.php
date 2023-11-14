<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

$triggers   = [
                [
                    'name'  => 'updated', 
                    'label' => 'Updated', 
                    'desc'  => 'Send a notification when a container has had an update applied'
                ],[
                    'name'  => 'updates', 
                    'label' => 'Updates', 
                    'desc'  => 'Send a notification when a container has an update available'
                ],[
                    'name'  => 'stateChange', 
                    'label' => 'State Change', 
                    'desc'  => 'Send a notification when a container has a state change (running -> down)' 
                ],[
                    'name'  => 'added', 
                    'label' => 'Added', 
                    'desc'  => 'Send a notification when a container is added' 
                ],[
                    'name'  => 'removed', 
                    'label' => 'Removed', 
                    'desc'  => 'Send a notification when a container is removed' 
                ],[
                    'name'  => 'cpuHigh', 
                    'label' => 'CPU Usage', 
                    'desc'  => 'Send a notification when container CPU usage exceeds threshold (set in Settings)' 
                ],[
                    'name'  => 'memHigh', 
                    'label' => 'Memory Usage', 
                    'desc'  => 'Send a notification when container memory usage exceeds threshold (set in Settings)' 
                ]
            ];

if ($_POST['m'] == 'init') {
    ?>
    <div class="container-fluid pt-4 px-4">
        <div class="bg-secondary rounded h-100 p-4">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><input type="checkbox" class="form-check-input" onchange="$('.notification-check').prop('checked', $(this).prop('checked'))"></th>
                            <th scope="col">Notification</th>
                            <th scope="col">Description</th>
                            <th scope="col">Platform</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($triggers as $trigger) {
                            $notificationSetting = $settings['notifications'][$trigger['name']];
                            ?>
                            <tr>
                                <th scope="row"><input type="checkbox" <?= ($notificationSetting['active'] ? 'checked' : '') ?> class="form-check-input notification-check" id="notifications-name-<?= $trigger['name'] ?>"></th>
                                <td><?= $trigger['label'] ?></td>
                                <td><?= $trigger['desc'] ?></td>
                                <td>
                                    <select class="form-control" id="notifications-platform-<?= $trigger['name'] ?>">
                                        <?php
                                        foreach ($platforms as $platform) {
                                            ?><option <?= ($notificationSetting['platform'] == $platform['id'] ? 'selected' : '') ?> value="<?= $platform['id'] ?>"><?= $platform['name'] ?></option><?php
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="9" align="center">
                                <button type="button" class="btn btn-info" onclick="saveNotificationSettings()">Save</button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'saveNotificationSettings') {
    $newSettings = [];

    foreach ($_POST as $key => $val) {
        if (strpos($key, '-name-') === false) {
            continue;
        }

        $type = str_replace('notifications-name-', '', $key);
        $newSettings[$type]     = [
                                    'active'    => $val, 
                                    'platform'  => $_POST['notifications-platform-' . $type]
                                ];
    }

    $settings['notifications'] = $newSettings;
    setFile(SETTINGS_FILE, $settings);
}
