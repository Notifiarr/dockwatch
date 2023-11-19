<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

function notificationPlatformField($platform, $field)
{
    $settings = getFile(SETTINGS_FILE);
    $settings = $settings['notifications']['platforms'][$platform];

    switch ($field['type']) {
        case 'password':
            return '<input class="form-control" type="password" id="notifications-platform-' . $platform . '-' . $field['name'] . '" value="' . $settings[$field['name']] . '" '. ($field['required'] ? 'data-required="true"' : '') .'>';
        case 'text':
            return '<input class="form-control" type="text" id="notifications-platform-' . $platform . '-' . $field['name'] . '" value="' . $settings[$field['name']] . '" '. ($field['required'] ? 'data-required="true"' : '') .'>';
    }
}
