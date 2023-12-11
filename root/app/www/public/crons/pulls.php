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

logger(SYSTEM_LOG, 'Cron: running pulls');
logger(CRON_PULLS_LOG, 'run ->');
echo 'Cron run started: pulls' . "\n";

if ($settingsFile['tasks']['pulls']['disabled']) {
    logger(CRON_PULLS_LOG, 'Cron run stopped: disabled in tasks menu');
    echo 'Cron run cancelled: disabled in tasks menu' . "\n";
    exit();
}

$updateSettings = $settingsFile['containers'];
$notify         = [];

if ($updateSettings) {
    foreach ($updateSettings as $containerHash => $containerSettings) {
        //-- SET TO IGNORE
        if (!$containerSettings['updates']) {
            continue;
        }

        $containerState = findContainerFromHash($containerHash);

        if ($containerState) {
            $pullHistory = $pullsFile[$containerHash];
            //-- CHECK AGAINST HOUR
            if (
                intval(date('H')) == intval($containerSettings['hour']) || 
                ($containerSettings['frequency'] == '12h' && intval(date('H')) == (intval($containerSettings['hour']) - 12)) ||
                !$pullHistory
                ) {
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

                if (!$pullHistory) {
                    $pullAllowed = true;
                }

                if ($pullAllowed) {
                    $image = $containerState['inspect'][0]['Config']['Image'];

                    $msg = 'Pulling: ' . $image;
                    logger(CRON_PULLS_LOG, $msg);
                    echo $msg . "\n";
                    $pull = apiRequest('dockerPullContainer', ['name' => $image]);

                    $msg = 'Inspecting container: ' . $containerState['Names'];
                    logger(CRON_PULLS_LOG, $msg);
                    echo $msg . "\n";
                    $inspectContainer = apiRequest('dockerInspect', ['name' => $containerState['Names'], 'useCache' => false, 'format' => true]);
                    $inspectContainer = json_decode($inspectContainer['response']['docker'], true);

                    $msg = 'Inspecting image: ' . $image;
                    logger(CRON_PULLS_LOG, $msg);
                    echo $msg . "\n";
                    $inspectImage = apiRequest('dockerInspect', ['name' => $image, 'useCache' => false, 'format' => true]);
                    $inspectImage = json_decode($inspectImage['response']['docker'], true);

                    $msg = 'Updating pull data: ' . $containerState['Names'];
                    logger(CRON_PULLS_LOG, $msg);
                    echo $msg . "\n";
                    $pullsFile[$containerHash]  = [
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
                                $newRun = '';
                                $msg = 'Building run command: ' . $containerState['Names'];
                                logger(CRON_PULLS_LOG, $msg);
                                echo $msg . "\n";
                                $runCommand = dockerAutoRun($containerState['Names']);
                                $lines = explode("\n", $runCommand);
                                foreach ($lines as $line) {
                                    $newRun .= trim(str_replace('\\', '', $line)) . ' ';
                                }
                                $runCommand = $newRun;

                                $msg = 'Stopping container: ' . $containerState['Names'];
                                logger(CRON_PULLS_LOG, $msg);
                                echo $msg . "\n";
                                dockerStopContainer($containerState['Names']);

                                $msg = 'Removing container: ' . $containerState['Names'];
                                logger(CRON_PULLS_LOG, $msg);
                                echo $msg . "\n";
                                $remove = dockerRemoveContainer($containerState['ID']);

                                $msg = 'Updating container: ' . $containerState['Names'];
                                logger(CRON_PULLS_LOG, $msg);
                                echo $msg . "\n";
                                $update = trim(dockerUpdateContainer($runCommand));

                                if (strlen($update) == 64) {
                                    $msg = 'Updating pull data: ' . $containerState['Names'];
                                    logger(CRON_PULLS_LOG, $msg);
                                    echo $msg . "\n";
                                    $pullsFile[$containerHash]  = [
                                                                    'checked'   => time(),
                                                                    'name'      => $containerState['Names'],
                                                                    'image'     => $update,
                                                                    'container' => $update
                                                                ];
                                } else {
                                    $msg = 'Invalid hash length: \'' . $update .'\'=' . strlen($update);
                                    logger(CRON_PULLS_LOG, $msg);
                                    echo $msg . "\n";
                                }

                                if ($settingsFile['notifications']['triggers']['updated']['active']) {
                                    $notify['updated'][] = ['container' => $containerState['Names']];
                                }
                            }
                            break;
                        case 2: //-- Check for updates
                            if ($settingsFile['notifications']['triggers']['updates']['active'] && $inspectImage[0]['Id'] != $inspectContainer[0]['Image']) {
                                $notify['available'][] = ['container' => $containerState['Names']];
                            }
                            break;
                    }
                } else {
                    $msg = 'Skipping: ' . $containerState['Names'] . ' (\'' . $containerSettings['frequency'] . '\' frequency not met, last check \'' . $daysSince . '\')';
                    logger(CRON_PULLS_LOG, $msg);
                    echo $msg . "\n";                    
                }
            } else {
                $msg = 'Skipping: ' . $containerState['Names'] . ' (\'' . $containerSettings['hour'] . '\' hour not met)';
                logger(CRON_PULLS_LOG, $msg);
                echo $msg . "\n";
            }
        }
    }

    setServerFile('pull', $pullsFile);

    if ($notify) {
        //-- IF THEY USE THE SAME PLATFORM, COMBINE THEM
        if ($settingsFile['notifications']['triggers']['updated']['platform'] == $settingsFile['notifications']['triggers']['updates']['platform']) {
            $payload = ['event' => 'updates', 'available' => $notify['available'], 'updated' => $notify['updated']];
            logger(CRON_PULLS_LOG, 'Notification payload: ' . json_encode($payload));
            $notifications->notify($settingsFile['notifications']['triggers']['updated']['platform'], $payload);
        } else {
            if ($notify['available']) {
                $payload = ['event' => 'updates', 'available' => $notify['available']];
                logger(CRON_PULLS_LOG, 'Notification payload: ' . json_encode($payload));
                $notifications->notify($settingsFile['notifications']['triggers']['updated']['platform'], $payload);
            }

            if ($notify['usage']['mem']) {
                $payload = ['event' => 'updates', 'updated' => $notify['updated']];
                logger(CRON_PULLS_LOG, 'Notification payload: ' . json_encode($payload));
                $notifications->notify($settingsFile['notifications']['triggers']['updates']['platform'], $payload);
            }
        }
    }
}

logger(CRON_PULLS_LOG, 'run <-');