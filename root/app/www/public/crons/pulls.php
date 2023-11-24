<?php

/*
----------------------------------
 ------  Created: 111723   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('ABSOLUTE_PATH', str_replace('crons', '', __DIR__));
require ABSOLUTE_PATH . 'loader.php';

logger($systemLog, 'Cron: running pulls', 'info');

set_time_limit(0);

$logfile = LOGS_PATH . 'crons/pulls-' . date('Ymd_Hi') . '.log';
logger($logfile, 'Cron run started');
echo 'Cron run started: pulls' . "\n";

$pulls          = getFile(PULL_FILE);
$updateSettings = $settings['containers'];
$states         = is_array($state) ? $state : json_decode($state, true);
$notify         = [];

if ($updateSettings) {
    foreach ($updateSettings as $containerHash => $containerSettings) {
        //-- SET TO IGNORE
        if (!$containerSettings['updates']) {
            continue;
        }

        $containerState = findContainerFromHash($containerHash);

        if ($containerState) {
            $pullHistory = $pulls[$containerHash];
            //-- CHECK AGAINST HOUR
            if (date('H') == $containerSettings['hour']) {
                $pullAllowed = false;

                //-- CHECK AGAINST FREQUENCY
                if ($containerSettings['frequency'] == '12h') {
                    $pullAllowed = true;
                } else {
                    $pullDays   = calculateDaysFromString($containerSettings['frequency']);
                    $daysSince  = daysBetweenDates(date('Ymd', $pullHistory['checked']), date('Ymd'));

                    if ($daysSince >= $pullDays) {
                        $pullAllowed = true;
                    }
                }

                if ($pullAllowed) {
                    $image = $containerState['inspect'][0]['Config']['Image'];

                    $msg = 'Pulling: ' . $image;
                    logger($logfile, $msg);
                    echo $msg . "\n";
                    $pull = dockerPullContainer($image);

                    $msg = 'Inspecting container: ' . $containerState['Names'];
                    logger($logfile, $msg);
                    echo $msg . "\n";
                    $inspectContainer = dockerInspect($containerState['Names'], false);
                    $inspectContainer = json_decode($inspectContainer, true);

                    $msg = 'Inspecting image: ' . $image;
                    logger($logfile, $msg);
                    echo $msg . "\n";
                    $inspectImage = dockerInspect($image, false);
                    $inspectImage = json_decode($inspectImage, true);

                    $msg = 'Updating pull data: ' . $containerState['Names'];
                    logger($logfile, $msg);
                    echo $msg . "\n";
                    $pulls[$containerHash]  = [
                                                'checked'   => time(),
                                                'name'      => $containerState['Names'],
                                                'image'     => $inspectImage[0]['Id'],
                                                'container' => $inspectContainer[0]['Image']
                                            ];

                    //-- DONT AUTO UPDATE THIS CONTAINER, CHECK ONLY
                    if (strpos($image, 'dockwatch') !== false) {
                        if ($containerSettings['updates'] == 1) {
                            $containerSettings['updates'] = 2;
                        }
                    }

                    switch ($containerSettings['updates']) {
                        case 1: //-- Auto update
                            if ($inspectImage[0]['Id'] != $inspectContainer[0]['Image']) {
                                $msg = 'Building run command: ' . $containerState['Names'];
                                logger($logfile, $msg);
                                echo $msg . "\n";
                                $runCommand = dockerAutoRun($containerState['Names']);
                                $lines = explode("\n", $runCommand);
                                foreach ($lines as $line) {
                                    $newRun .= trim(str_replace('\\', '', $line)) . ' ';
                                }
                                $runCommand = $newRun;

                                $msg = 'Stopping container: ' . $containerState['Names'];
                                logger($logfile, $msg);
                                echo $msg . "\n";
                                dockerStopContainer($containerState['Names']);

                                $msg = 'Removing container: ' . $containerState['Names'];
                                logger($logfile, $msg);
                                echo $msg . "\n";
                                $remove = dockerRemoveContainer($containerState['ID']);

                                $msg = 'Updating container: ' . $containerState['Names'];
                                logger($logfile, $msg);
                                echo $msg . "\n";
                                $update = trim(dockerUpdateContainer($runCommand));

                                if (strlen($update) == 64) {
                                    $msg = 'Updating pull data: ' . $containerState['Names'];
                                    logger($logfile, $msg);
                                    echo $msg . "\n";
                                    $pulls[$containerHash]  = [
                                                                'checked'   => time(),
                                                                'name'      => $containerState['Names'],
                                                                'image'     => $update,
                                                                'container' => $update
                                                            ];
                                } else {
                                    $msg = 'Invalid hash length: \'' . $update .'\'=' . strlen($update);
                                    logger($logfile, $msg);
                                    echo $msg . "\n";
                                }

                                if ($settings['notifications']['triggers']['updated']['active']) {
                                    $notify['updated'][] = ['container' => $containerState['Names']];
                                }
                            }
                            break;
                        case 2: //-- Check for updates
                            if ($settings['notifications']['triggers']['updates']['active'] && $inspectImage[0]['Id'] != $inspectContainer[0]['Image']) {
                                $notify['available'][] = ['container' => $containerState['Names']];
                            }
                            break;
                    }
                } else {
                    $msg = 'Skipping: ' . $containerState['Names'] . ' (\'' . $containerSettings['frequency'] . '\' frequency not met, last check \'' . $daysSince . '\')';
                    logger($logfile, $msg);
                    echo $msg . "\n";                    
                }
            } else {
                $msg = 'Skipping: ' . $containerState['Names'] . ' (\'' . $containerSettings['hour'] . '\' hour not met)';
                logger($logfile, $msg);
                echo $msg . "\n";
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
