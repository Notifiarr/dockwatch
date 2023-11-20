<?php

/*
----------------------------------
 ------  Created: 111623   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Notifiarr
{
    public function notifiarr($payload)
    {
        $headers    = ['x-api-key:' . $this->platformSettings[1]['apikey']];
        $url        = 'https://notifiarr.com/api/v1/notification/dockwatch';
        $curl       = curl($url, $headers, 'POST', json_encode($payload));

        logger($this->logfile, 'notification response:' . json_encode($curl), ($curl['response']['code'] != 200 ? 'error' : 'info'));

        $return = ['code' => 200];
        if ($curl['response']['code'] != 200) {
            $return = ['code' => $curl['response']['code'], 'error' => $curl['response']['details']['response']];
        }

        return $return;
    }
}