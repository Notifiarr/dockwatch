<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

function notificationPlatformField($platform, $field)
{
    global $settingsFile;
    $settingsFile = $settingsFile['notifications']['platforms'][$platform];

    switch ($field['type']) {
        case 'password':
            return '<input class="form-control" type="password" id="notifications-platform-' . $platform . '-' . $field['name'] . '" value="' . $settingsFile[$field['name']] . '" '. ($field['required'] ? 'data-required="true"' : '') .'>';
        case 'text':
            return '<input class="form-control" type="text" id="notifications-platform-' . $platform . '-' . $field['name'] . '" value="' . $settingsFile[$field['name']] . '" '. ($field['required'] ? 'data-required="true"' : '') .'>';
    }
}

function sendTestNotification($platform)
{
    global $notifications, $settingsFile;

    //-- INITIALIZE THE NOTIFY CLASS
    if (!$notifications) {
        $notifications = new Notifications();
        logger(SYSTEM_LOG, 'Init class: Notifications()');
    }

    $return     = '';
    $payload    = [
        'event'     => 'test', 
        'title'     => 'DockWatch Test', 
        'message'   => 'This is a test message sent from DockWatch'
    ];

    $result = $notifications->notify($platform, $payload);

    if ($result['code'] != 200) {
        $return = 'Code ' . $result['code'] . ', ' . $result['error'];
    }

    return $return;
}