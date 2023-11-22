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
                            $notificationSetting = $settings['notifications']['triggers'][$trigger['name']];
                            ?>
                            <tr>
                                <th scope="row"><input type="checkbox" <?= ($notificationSetting['active'] ? 'checked' : '') ?> class="form-check-input notification-check" id="notifications-name-<?= $trigger['name'] ?>"></th>
                                <td><?= $trigger['label'] ?></td>
                                <td><?= $trigger['desc'] ?></td>
                                <td>
                                    <select class="form-control" id="notifications-platform-<?= $trigger['name'] ?>">
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
            <button type="button" class="btn btn-info" onclick="saveNotificationSettings()">Save Changes</button>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'saveNotificationSettings') {
    //-- TRIGGER SETTINGS
    $newSettings = [];
    foreach ($_POST as $key => $val) {
        if (strpos($key, '-name-') === false) {
            continue;
        }

        $type = str_replace('notifications-name-', '', $key);
        $newSettings[$type]     = [
                                    'active'    => trim($val),
                                    'platform'  => $_POST['notifications-platform-' . $type]
                                ];
    }
    $settings['notifications']['triggers'] = $newSettings;

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
    $settings['notifications']['platforms'] = $newSettings;

    setFile(SETTINGS_FILE, $settings);
}

if ($_POST['m'] == 'testNotify') {
    $payload    = [
                    'event'     => 'test', 
                    'title'     => 'DockWatch Test', 
                    'message'   => 'This is a test message sent from DockWatch'
                ];

    $result = $notifications->notify($_POST['platform'], $payload);

    if ($result['code'] != 200) {
        echo 'Code ' . $result['code'] . ', ' . $result['error'];
    }
}