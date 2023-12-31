<?php

/*
----------------------------------
 ------  Created: 111623   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Notifiarr
{
    public function notifiarr($logfile, $payload)
    {
        $headers    = ['x-api-key:' . $this->platformSettings[1]['apikey']];
        $url        = 'https://notifiarr.com/api/v1/notification/dockwatch';
        $curl       = curl($url, $headers, 'POST', json_encode($payload));

        logger($logfile, 'notification response:' . json_encode($curl), ($curl['code'] != 200 ? 'error' : 'info'));

        $return = ['code' => 200];
        if ($curl['code'] != 200) {
            $return = ['code' => $curl['code'], 'error' => $curl['response']['details']['response']];
        }

        return $return;
    }
}
