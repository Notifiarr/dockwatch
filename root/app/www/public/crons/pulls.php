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

$logfile = LOGS_PATH . 'crons/cron-pulls-' . date('Ymd') . '.log';
logger($logfile, 'Cron run started');
echo 'Cron run started: pulls' . "\n";

$updateSettings = $settings['containers'];
$states         = is_array($state) ? $state : json_decode($state, true);
$pulls          = getFile(PULL_FILE);
$notify         = [];

if ($updateSettings) {
    foreach ($updateSettings as $containerHash => $settings) {
        //-- SET TO IGNORE
        if (!$settings['updates']) {
            continue;
        }

        $containerState = findContainerFromHash($containerHash);

        if ($containerState) {
            $pullHistory = $pulls[$containerHash];
            //-- CHECK AGAINST HOUR
            if (date('H') == $settings['hour']) {
                $pullAllowed = false;

                //-- CHECK AGAINST FREQUENCY
                if ($settings['frequency'] == '12h') {
                    $pullAllowed = true;
                } else {
                    $pullDays   = calculateDaysFromString($settings['frequency']);
                    $daysSince  = daysBetweenDates(date('Ymd', $pullHistory['checked']), date('Ymd'));

                    if ($daysSince >= $pullDays) {
                        $pullAllowed = true;
                    }
                }

                if ($pullAllowed) {
                    $image = $containerState['inspect'][0]['Config']['Image'];

                    $msg = 'Pulling: ' . $image;
                    logger($logfile, $msg);
                    echo $msg. "\n";
                    $pull = dockerPullContainer($image);

                    $msg = 'Inspecting container: ' . $containerState['Names'];
                    logger($logfile, $msg);
                    echo $msg. "\n";
                    $inspectContainer = dockerInspect($containerState['Names'], false);
                    $inspectContainer = json_decode($inspectContainer, true);

                    $msg = 'Inspecting image: ' . $image;
                    logger($logfile, $msg);
                    echo $msg. "\n";
                    $inspectImage = dockerInspect($image, false);
                    $inspectImage = json_decode($inspectImage, true);

                    $msg = 'Updating pull data: ' . $containerState['Names'];
                    logger($logfile, $msg);
                    echo $msg. "\n";
                    $pulls[$containerHash]  = [
                                                'checked'   => time(),
                                                'name'      => $containerState['Names'],
                                                'image'     => $inspectImage[0]['Id'],
                                                'container' => $inspectContainer[0]['Image']
                                            ];

                    switch ($settings['updates']) {
                        case 1: //-- Auto update
                            if ($settings['notifications']['triggers']['updated']['active'] && $inspectImage[0]['Id'] != $inspectContainer[0]['Image']) {
                                logger($logfile, 'Building run command for ' . $containerState['Names']);
                                //$runCommand = dockerAutoRun($containerState['Names']);
                                logger($logfile, 'Stopping container ' . $containerState['Names']);
                                //dockerStopContainer($containerState['Names']);
                                logger($logfile, 'Removing container ' . $containerState['Names']);
                                //dockerRemoveContainer($containerState['Id']);
                                logger($logfile, 'Updating container ' . $containerState['Names']);
                                //dockerUpdateContainer($runCommand);

                                $notify['updated'][] = ['container' => $containerState['Names']];
                            }
                            break;
                        case 2: //-- Check for updates
                            if ($settings['notifications']['triggers']['updates']['active'] && $inspectImage[0]['Id'] != $inspectContainer[0]['Image']) {
                                $notify['available'][] = ['container' => $containerState['Names']];
                            }
                            break;
                    }
                } else {
                    $msg = 'Skipping: ' . $containerState['Names'] . ' (\'' . $settings['frequency'] . '\' frequency not met)';
                    logger($logfile, $msg);
                    echo $msg. "\n";                    
                }
            } else {
                $msg = 'Skipping: ' . $containerState['Names'] . ' (\'' . $settings['hour'] . '\' hour not met)';
                logger($logfile, $msg);
                echo $msg. "\n";
            }
        }
    }

    setFile(PULL_FILE, $pulls);

    if ($notify) {
        //-- IF THEY USE THE SAME PLATFORM, COMBINE THEM
        if ($settings['notifications']['triggers']['updated']['platform'] == $settings['notifications']['triggers']['updates']['platform']) {
            $payload = ['event' => 'updates', 'available' => $notify['available'], 'updated' => $notify['updated']];
            logger($logfile, 'Notification payload: ' . json_encode($payload));
            $notifications->notify($settings['notifications']['triggers']['updated']['platform'], $payload);
        } else {
            if ($notify['available']) {
                $payload = ['event' => 'updates', 'available' => $notify['available']];
                logger($logfile, 'Notification payload: ' . json_encode($payload));
                $notifications->notify($settings['notifications']['triggers']['updated']['platform'], $payload);
            }

            if ($notify['usage']['mem']) {
                $payload = ['event' => 'updates', 'updated' => $notify['updated']];
                logger($logfile, 'Notification payload: ' . json_encode($payload));
                $notifications->notify($settings['notifications']['triggers']['updates']['platform'], $payload);
            }
        }
    }
}
logger($logfile, 'Cron run finished');
