<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $notificationPlatformTable  = apiRequest('database/notification/platforms')['result'];
    $notificationTriggersTable  = apiRequest('database/notification/triggers')['result'];
    $notificationLinkTable      = apiRequest('database/notification/links')['result'];

    ?>
    <ol class="breadcrumb rounded p-1 ps-2">
        <li class="breadcrumb-item"><a href="#" onclick="initPage('overview')"><?= $_SESSION['activeServerName'] ?></a><span class="ms-2">↦</span></li>
        <li class="breadcrumb-item active" aria-current="page">Notifications</li>
    </ol>
    <div class="bg-secondary rounded mb-3 p-4">
        <span class="h6">Platforms</span>
        <div class="row mt-3">
            <?php
            foreach ($notificationPlatformTable as $notificationPlatform) {
                $add = $notificationPlatform['parameters'] ? '<i class="fas fa-plus-circle text-light ms-3" style="cursor: pointer;" onclick="openNotificationTriggers(' . $notificationPlatform['id'] . ')"></i>' : '<span class="small-text fst-italic ms-3">Coming soon!</span>';

                ?>
                <div class="col-sm-3 rounded border border-light me-3">
                    <div class="container">
                        <div class="bg-secondary rounded text-center p-1">
                            <h4 class="mt-3"><?= $notificationPlatform['platform'] . $add ?></h4>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    <div class="bg-secondary rounded mb-3 p-4">
        <span class="h6">Configured senders</span>
        <div class="row mt-3">
            <?php if (!$notificationLinkTable) { ?>
            <div class="container">
                <div class="bg-secondary rounded p-4">
                    Notifications have not been setup yet, click the plus icon above to set them up.
                </div>
            </div>
            <?php } else { ?>
                <?php 
                foreach ($notificationLinkTable as $notificationLink) {
                    ?>
                    <div class="col-sm-4 rounded border border-light me-3">
                        <div class="container">
                            <div class="bg-secondary rounded text-center p-2">
                                <h4 class="mt-3">
                                    <?= $notificationLink['name'] ?> 
                                    <i class="fas fa-tools text-light ms-3" style="cursor: pointer;" title="Update this sender triggers" onclick="openNotificationTriggers(<?= $notificationLink['platform_id'] ?>, <?= $notificationLink['id'] ?>)"></i>
                                    <i class="far fa-bell text-light ms-1" style="cursor: pointer;" title="Send test notification" onclick="testNotify(<?= $notificationLink['id'] ?>, 'test')"></i>
                                </h4>
                                <div class="row text-left">
                                    <?php 
                                    if (!$notificationLink['trigger_ids']) {
                                        ?>You have not configured any triggers for this notification<?php
                                    } else {
                                        $triggerIds = $notificationLink['trigger_ids'] ? json_decode($notificationLink['trigger_ids'], true) : [];
                                        $enabledTriggers = [];
                                        foreach ($triggerIds as $triggerId) {
                                            $trigger = $notifications->getNotificationTriggerNameFromId($triggerId, $notificationTriggersTable);
                                            $enabledTriggers[] = $trigger;
                                        }

                                        echo '<div><span class="text-success d-inline-block">Enabled:</span> ' . ($enabledTriggers ? implode(', ', $enabledTriggers) : 'No triggers enabled') . '</div>';
                                    } 
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            <?php } ?>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'openNotificationTriggers') {
    $_POST['linkId']            = $_POST['linkId'] ?: 0;
    $notificationPlatformTable  = apiRequest('database/notification/platforms')['result'];
    $notificationTriggersTable  = apiRequest('database/notification/triggers')['result'];
    $notificationLinkTable      = apiRequest('database/notification/links')['result'];
    $platformParameters         = json_decode($notificationPlatformTable[$_POST['platformId']]['parameters'], true);
    $platformName               = $notifications->getNotificationPlatformNameFromId($_POST['platformId'], $notificationPlatformTable);
    $linkRow                    = $notificationLinkTable[$_POST['linkId']];
    $existingTriggers           = $existingParameters = [];
    $tests                      = $notifications->getTestPayloads();

    if ($linkRow) {
        $existingTriggers   = $linkRow['trigger_ids'] ? json_decode($linkRow['trigger_ids'], true) : [];
        $existingParameters = $linkRow['platform_parameters'] ? json_decode($linkRow['platform_parameters'], true) : [];
        $existingName       = $linkRow['name'];
    }

    ?>
    <div class="container">
        <h3><?= $platformName ?></h3>
        <div class="bg-secondary rounded p-2">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th><input type="checkbox" class="form-check-input" onchange="$('.notification-trigger').prop('checked', $(this).prop('checked'))"></th>
                        <th width="25%">Trigger</th>
                        <th>Description</th>
                        <th>Event</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($notificationTriggersTable as $notificationTrigger) {
                        ?>
                        <tr>
                            <td><input <?= in_array($notificationTrigger['id'], $existingTriggers) ? 'checked' : '' ?> type="checkbox" class="form-check-input notification-trigger" id="notificationTrigger-<?= $notificationTrigger['id'] ?>"></td>
                            <td><i class="far fa-bell text-light" style="cursor: pointer;" title="Send test notification" onclick="testNotify(<?= $linkRow['id'] ?>, '<?= $notificationTrigger['name'] ?>')"></i> <?= $notificationTrigger['label'] ?></td>
                            <td><?= $notificationTrigger['description'] ?></td>
                            <td><?= $notificationTrigger['event'] ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Setting</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            Name <span class="ms-2 small-text text-danger">Required</span><br>
                            <span class="small-text">The name of this notification sender</span>
                        </td>
                        <td><input data-required="true" type="text" class="form-control" value="<?= $existingName ?: $platformName ?>" id="notificationPlatformParameter-name"></td>
                    </tr>
                    <?php
                    foreach ($platformParameters as $platformParameterField => $platformParameterData) {
                        ?>
                        <tr>
                            <td width="50%"><?= $platformParameterData['label'] . ($platformParameterData['required'] ? '<span class="ms-2 small-text text-danger">Required</span>' : '') ?><br><span class="small-text"><?= $platformParameterData['description'] ?></span></td>
                            <td>
                            <?php
                            switch ($platformParameterData['type']) {
                                case 'text':
                                    ?><input <?= $platformParameterData['required'] ? 'data-required="true"' : '' ?> type="text" id="notificationPlatformParameter-<?= $platformParameterField ?>" class="form-control" value="<?= $existingParameters[$platformParameterField] ?>"><?php
                                    break;
                            }
                            ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            <hr>
            <div class="text-center w-100">
                <?php if ($linkRow) { ?>
                    <button class="btn btn-outline-success" onclick="saveNotification(<?= $_POST['platformId'] ?>, <?= $_POST['linkId'] ?>)">Save</button>
                    <button class="btn btn-outline-danger" onclick="deleteNotification(<?= $_POST['linkId'] ?>)">Remove</button>
                <?php } else { ?>
                    <button class="btn btn-outline-success" onclick="addNotification(<?= $_POST['platformId'] ?>)">Add</button>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'addNotification') {
    if (!$_POST['platformId']) {
        $error = 'Missing required platform id';
    }

    if (!$error) {
        $notificationPlatformTable  = apiRequest('database/notification/platforms')['result'];
        $notificationTriggersTable  = apiRequest('database/notification/triggers')['result'];
        $notificationLinkTable      = apiRequest('database/notification/links')['result'];
        $platformParameters         = json_decode($notificationPlatformTable[$_POST['platformId']]['parameters'], true);
        $platformName               = $notifications->getNotificationPlatformNameFromId($_POST['platformId'], $notificationPlatformTable);

        //-- CHECK FOR REQUIRED FIELDS
        foreach ($platformParameters as $platformParameterField => $platformParameterData) {
            if ($platformParameterData['required'] && !$_POST['notificationPlatformParameter-' . $platformParameterField]) {
                $error = 'Missing required platform field: ' . $platformParameterData['label'];
                break;
            }
        }

        if (!$error) {
            $triggerIds = $platformParameters = [];
            $senderName = $platformName;

            foreach ($_POST as $key => $val) {
                if (str_contains($key, 'notificationTrigger-') && $val) {
                    $triggerIds[] = str_replace('notificationTrigger-', '', $key);
                }

                if (str_contains($key, 'notificationPlatformParameter-')) {
                    $field = str_replace('notificationPlatformParameter-', '', $key);

                    if ($field != 'name') {
                        $platformParameters[$field] = $val;
                    } else {
                        $senderName = $val;
                    }
                }
            }

            apiRequest('database/notification/link/add', ['platformId' => $_POST['platformId']], ['triggerIds' => $triggerIds, 'platformParameters' => $platformParameters, 'senderName' => $senderName]);
        }
    }

    echo json_encode(['error' => $error]);
}

if ($_POST['m'] == 'saveNotification') {
    if (!$_POST['platformId']) {
        $error = 'Missing required platform id';
    }
    if (!$_POST['linkId']) {
        $error = 'Missing required link id';
    }

    if (!$error) {
        $notificationPlatformTable  = apiRequest('database/notification/platforms')['result'];
        $notificationTriggersTable  = apiRequest('database/notification/triggers')['result'];
        $notificationLinkTable      = apiRequest('database/notification/links')['result'];
        $platformParameters         = json_decode($notificationPlatformTable[$_POST['platformId']]['parameters'], true);
        $platformName               = $notifications->getNotificationPlatformNameFromId($_POST['platformId'], $notificationPlatformTable);

        //-- CHECK FOR REQUIRED FIELDS
        foreach ($platformParameters as $platformParameterField => $platformParameterData) {
            if ($platformParameterData['required'] && !$_POST['notificationPlatformParameter-' . $platformParameterField]) {
                $error = 'Missing required platform field: ' . $platformParameterData['label'];
                break;
            }
        }

        if (!$error) {
            $triggerIds = $platformParameters = [];
            $senderName = $platformName;

            foreach ($_POST as $key => $val) {
                if (str_contains($key, 'notificationTrigger-') && $val) {
                    $triggerIds[] = str_replace('notificationTrigger-', '', $key);
                }

                if (str_contains($key, 'notificationPlatformParameter-')) {
                    $field = str_replace('notificationPlatformParameter-', '', $key);

                    if ($field != 'name') {
                        $platformParameters[$field] = $val;
                    } else {
                        $senderName = $val;
                    }
                }
            }

            apiRequest('database/notification/link/update', [], ['linkId' => $_POST['linkId'], 'triggerIds' => $triggerIds, 'platformParameters' => $platformParameters, 'senderName' => $senderName]);
        }
    }

    echo json_encode(['error' => $error]);
}

if ($_POST['m'] == 'deleteNotification') {
    apiRequest('database/notification/link/delete', [], ['linkId' => $_POST['linkId']]);
}

if ($_POST['m'] == 'testNotify') {
    $apiResponse = apiRequest('notification/test', [], ['linkId' => $_POST['linkId'], 'name' => $_POST['name']]);

    if (($apiResponse['result']['code'] && $apiResponse['result']['code'] == 200) || (!$apiResponse['result']['code'] && $apiResponse['code'] == 200)) {
        $result = 'Test notification sent on server ' . ACTIVE_SERVER_NAME;
    } else {
        $error = 'Failed to send test notification on server ' . ACTIVE_SERVER_NAME . '. ' . ($apiResponse['result']['result'] ? $apiResponse['result']['result'] : $apiResponse['result']);
    }

    echo json_encode(['error' => $error, 'result' => $result]);
}
