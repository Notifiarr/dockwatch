<?php

/*
----------------------------------
 ------  Created: 050225   ------
 ------  Austin Best	   ------
----------------------------------
*/

switch ($path) {
    case 'test': //-- .../api/notification/test
        $testNotification = $notifications->sendTestNotification($payload['linkId'], $payload['name']);

        if ($testNotification['code'] != 200) {
            $result = 'Test notification failed: ' . $testNotification['result'];
            apiResponse($testNotification['code'], ['error' => $result]);
        }

        $apiRequestResponse = ['code' => $testNotification['code'], 'result' => $result];
        break;
}
