<?php

/*
----------------------------------
 ------  Created: 111623   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Notifiarr
{
    public function notifiarr($logfile, $apikey, $payload, $test = false)
    {
        $headers    = ['x-api-key:' . $apikey];
        $url        = 'https://notifiarr.com/api/v1/notification/dockwatch';
        $curl       = curl($url, $headers, 'POST', json_encode($payload));

        logger($logfile, 'notification response:' . json_encode($curl), ($curl['code'] != 200 ? 'error' : ''));

        $return = ['code' => 200];
        if (!str_equals_any($curl['code'], [200, 201, 400, 401])) {
            logger($logfile, 'sending a retry in 5s...');
            sleep(5);

            $curl = curl($url, $headers, 'POST', json_encode($payload));
            logger($logfile, 'notification response:' . json_encode($curl), ($curl['code'] != 200 ? 'error' : ''));

            if ($curl['code'] != 200) {
                $return = ['code' => $curl['code'], 'error' => $curl['response']['details']['response']];
            }
        }

        return $return;
    }
}
