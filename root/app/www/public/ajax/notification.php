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
                    'desc'  => 'Send a notification when a container has had an update applied, updates event'
                ],[
                    'name'  => 'updates', 
                    'label' => 'Updates', 
                    'desc'  => 'Send a notification when a container has an update available, updates event'
                ],[
                    'name'  => 'stateChange', 
                    'label' => 'State change', 
                    'desc'  => 'Send a notification when a container has a state change (running -> down), state event' 
                ],[
                    'name'  => 'added', 
                    'label' => 'Added', 
                    'desc'  => 'Send a notification when a container is added, state event' 
                ],[
                    'name'  => 'removed', 
                    'label' => 'Removed', 
                    'desc'  => 'Send a notification when a container is removed, state event' 
                ],[
                    'name'  => 'prune', 
                    'label' => 'Prune', 
                    'desc'  => 'Send a notification when an image or volume is pruned, prune event' 
                ],[
                    'name'  => 'cpuHigh', 
                    'label' => 'CPU usage', 
                    'desc'  => 'Send a notification when container CPU usage exceeds threshold (set in Settings), usage event' 
                ],[
                    'name'  => 'memHigh', 
                    'label' => 'Memory usage', 
                    'desc'  => 'Send a notification when container memory usage exceeds threshold (set in Settings), usage event' 
                ],[
                    'name'  => 'health', 
                    'label' => 'Health change', 
                    'desc'  => 'Send a notification when container becomes unhealthy, health event' 
                ]
            ];

if ($_POST['m'] == 'init') {
    $notificationPlatforms = $notifications->getPlatforms();
    ?>
    <div class="container-fluid pt-4 px-4">
        <div class="bg-secondary rounded h-100 p-4">
            <h4 class="mt-3">Triggers</h4>
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
                            $notificationSetting = $settingsFile['notifications']['triggers'][$trigger['name']];
                            ?>
                            <tr>
                                <th scope="row"><input type="checkbox" <?= ($notificationSetting['active'] ? 'checked' : '') ?> class="form-check-input notification-check" id="notifications-name-<?= $trigger['name'] ?>"></th>
                                <td><?= $trigger['label'] ?></td>
                                <td><?= $trigger['desc'] ?></td>
                                <td>
                                    <select class="form-control" id="notifications-platform-<?= $trigger['name'] ?>">
                                        <option value="0">-- Select one --</option>
                                        <?php
                                        foreach ($platforms as $platformId => $platform) {
                                            ?><option <?= ($notificationSetting['platform'] == $platformId ? 'selected' : '') ?> value="<?= $platformId ?>"><?= $platform['name'] ?></option><?php
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="bg-secondary rounded h-100 p-4 mt-3">
            <h4 class="mt-3">Platforms</h4>
            <?php foreach ($notificationPlatforms as $platformId => $platform) { ?>
            <h6><?= $platform['name'] ?> <i style="cursor: pointer;" class="fas fa-bell text-info" title="Test notification" onclick="testNotify('<?= $platformId ?>')"></i></h6>
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
                        <?php foreach ($platform['fields'] as $field) { ?>
                        <tr>
                            <th scope="row"><?= $field['label'] ?><?= ($field['required'] ? '<span class="text-danger">*</span>' : '') ?></th>
                            <td><?= notificationPlatformField($platformId, $field) ?></td>
                            <td><?= $field['description'] ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php } ?>
        </div>
        <div class="bg-secondary rounded h-100 p-4 mt-3 text-center">
            <button type="button" class="btn btn-success" onclick="saveNotificationSettings()">Save Changes</button>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'saveNotificationSettings') {
    //-- TRIGGER SETTINGS
    $newSettings = [];
    foreach ($_POST as $key => $val) {
        if (!str_contains($key, '-name-')) {
            continue;
        }

        $type = str_replace('notifications-name-', '', $key);
        $newSettings[$type]     = [
                                    'active'    => trim($val),
                                    'platform'  => $_POST['notifications-platform-' . $type]
                                ];
    }
    $settingsFile['notifications']['triggers'] = $newSettings;

    //-- PLATFORM SETTINGS
    $newSettings = [];
    foreach ($_POST as $key => $val) {
        $strip = str_replace('notifications-platform-', '', $key);
        list($platformId, $platformField) = explode('-', $strip);

        if (!is_numeric($platformId)) {
            continue;
        }

        $newSettings[$platformId][$platformField] = trim($val);
    }
    $settingsFile['notifications']['platforms'] = $newSettings;

    $saveSettings = setServerFile('settings', $settingsFile);

    if ($saveSettings['code'] != 200) {
        $error = 'Error saving notification settings on server ' . ACTIVE_SERVER_NAME;
    }

    echo json_encode(['error' => $error, 'server' => ACTIVE_SERVER_NAME]);
}

if ($_POST['m'] == 'testNotify') {
    $apiResponse = apiRequest('testNotify', [], ['platform' => $_POST['platform']]);

    if ($apiResponse['code'] == 200) {
        $result = 'Test notification sent on server ' . ACTIVE_SERVER_NAME;
    } else {
        $error = 'Failed to send test notification on server ' . ACTIVE_SERVER_NAME . '. ' . $apiResponse['response']['result'];
    }

    echo json_encode(['error' => $error, 'result' => $result]);
}
