<?php

/*
----------------------------------
 ------  Created: 111723   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';
set_time_limit(0);

$logfile = ABSOLUTE_PATH . LOGS_PATH . 'cron-pulls-' . date('Ymd') . '.log';
logger($logfile, 'Cron run started');
echo 'Cron run started: pulls' . "\n";

$updateSettings = $settings['containers'];
$states         = is_array($state) ? $state : json_decode($state, true);
$pulls          = getFile(PULL_FILE);

if ($updateSettings) {
    foreach ($updateSettings as $containerHash => $settings) {
        //-- SET TO IGNORE
        if (!$settings['updates']) {
            continue;
        }

        $containerState = findContainerFromHash($containerHash);

        if ($containerState) {
            if (date('H') == $settings['hour']) {
                $image = $containerState['inspect'][0]['Config']['Image'];

                $msg = 'Pulling: ' . $image;
                logger($logfile, $msg);
                echo $msg. "\n";
                $pull = dockerPullContainer($image);

                $msg = 'Inspecting container: ' . $containerState['Names'];
                logger($logfile, $msg);
                echo $msg. "\n";
                $inspectContainer = dockerInspect($containerState['Names']);
                $inspectContainer = json_decode($inspectContainer, true);

                $msg = 'Inspecting image: ' . $image;
                logger($logfile, $msg);
                echo $msg. "\n";
                $inspectImage = dockerInspect($image);
                $inspectImage = json_decode($inspectImage, true);

                $msg = 'Updating pull data: ' . $containerState['Names'];
                logger($logfile, $msg);
                echo $msg. "\n";
                $pulls[md5($containerState['Names'])]   = [
                                                            'checked'   => time(),
                                                            'name'      => $containerState['Names'],
                                                            'image'     => $inspectImage[0]['Id'],
                                                            'container' => $inspectContainer[0]['Image']
                                                        ];
            } else {
                $msg = 'Skipping: ' . $containerState['Names'];
                logger($logfile, $msg);
                echo $msg. "\n";
            }
        }
    }

    setFile(PULL_FILE, $pulls);
}
logger($logfile, 'Cron run finished');
