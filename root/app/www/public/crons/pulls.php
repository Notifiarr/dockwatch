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
            $hour = intval($containerSettings['hour']);
            $six = 0;
            if ($containerSettings['frequency'] == '6h') {
                $six = date('g', strtotime('+ 6 hours', mktime($hour)));
            }
            $twelve = 0;
            if ($containerSettings['frequency'] == '12h') {
                $twelve = date('g', strtotime(mktime($hour)));
            }

            if (
                date('H') == $hour || 
                ($containerSettings['frequency'] == '12h' && date('g') == $twelve) ||
                ($containerSettings['frequency'] == '6h' && date('g') == $six) ||
                !$pullHistory
                ) {
                $pullAllowed = false;

                //-- CHECK AGAINST FREQUENCY
                if ($containerSettings['frequency'] == '6h' || $containerSettings['frequency'] == '12h') {
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
                    $image = isDockerIO($containerState['inspect'][0]['Config']['Image']);

                    if (!$image) {
                        $msg = 'Skipping local (has no Config.Image): ' . $containerState['Names'];
                        logger(CRON_PULLS_LOG, $msg, 'error');
                        echo $msg . "\n";
                        continue;
                    }

                    $preVersion = $postVersion = '';

                    $msg = 'Inspecting container: ' . $containerState['Names'];
                    logger(CRON_PULLS_LOG, $msg);
                    echo $msg . "\n";
                    $inspect = dockerInspect($containerState['Names']);

                    if ($inspect) {
                        $inspectArray = json_decode($inspect, true);

                        foreach ($inspectArray[0]['Config']['Labels'] as $label => $val) {
                            if (str_contains($label, 'image.version')) {
                                $preVersion = $val;
                                break;
                            }
                        }
                    }

                    $msg = 'Getting registry digest: ' . $image;
                    logger(CRON_PULLS_LOG, $msg);
                    echo $msg . "\n";
                    $regctlDigest = trim(regctlCheck($image));

                    if (str_contains($regctlDigest, 'Error')) {
                        logger(CRON_PULLS_LOG, $regctlDigest, 'error');
                        echo $regctlDigest . "\n";
                        continue;
                    }

                    $msg = 'Inspecting image: ' . $image;
                    logger(CRON_PULLS_LOG, $msg);
                    echo $msg . "\n";
                    $inspectImage   = apiRequest('dockerInspect', ['name' => $image, 'useCache' => false, 'format' => true]);
                    $inspectImage   = json_decode($inspectImage['response']['docker'], true);
                    list($cr, $imageDigest) = explode('@', $inspectImage[0]['RepoDigests'][0]);

                    $msg = 'Updating pull data: ' . $containerState['Names'] . "\n";
                    $msg .= '|__ regctl \'' . truncateMiddle(str_replace('sha256:', '', $regctlDigest), 30) . '\' image \'' . truncateMiddle(str_replace('sha256:', '', $imageDigest), 30) .'\'';
                    logger(CRON_PULLS_LOG, $msg);
                    echo $msg . "\n";
                    $pullsFile[$containerHash]  = [
                                                    'checked'       => time(),
                                                    'name'          => $containerState['Names'],
                                                    'regctlDigest'  => $regctlDigest,
                                                    'imageDigest'   => $imageDigest
                                                ];

                    //-- DONT AUTO UPDATE THIS CONTAINER, CHECK ONLY
                    if (skipContainerUpdates($image, $skipContainerUpdates)) {
                        if ($containerSettings['updates'] == 1) {
                            $containerSettings['updates'] = 2;
                        }
                    }

                    if ($regctlDigest != $imageDigest) {
                        switch ($containerSettings['updates']) {
                            case 1: //-- Auto update
                                $msg = 'Pulling image: ' . $image;
                                logger(CRON_PULLS_LOG, $msg);
                                echo $msg . "\n";
                                dockerPullContainer($image);

                                $msg = 'Stopping container: ' . $containerState['Names'];
                                logger(CRON_PULLS_LOG, $msg);
                                echo $msg . "\n";
                                $stop = dockerStopContainer($containerState['Names']);
                                logger(CRON_PULLS_LOG, trim($stop));

                                $msg = 'Removing container: ' . $containerState['Names'] . ' (' . $containerState['ID'] . ')';
                                logger(CRON_PULLS_LOG, $msg);
                                echo $msg . "\n";
                                $remove = dockerRemoveContainer($containerState['Names']);
                                logger(CRON_PULLS_LOG, trim($remove));

                                $msg = 'Updating container: ' . $containerState['Names'];
                                logger(CRON_PULLS_LOG, $msg);
                                echo $msg . "\n";
                                $update = dockerUpdateContainer(json_decode($inspect, true));
                                logger(CRON_PULLS_LOG, 'dockerUpdateContainer:' . trim(json_encode($update)));

                                if (strlen($update['Id']) == 64) {
                                    $msg = 'Updating pull data: ' . $containerState['Names'];
                                    logger(CRON_PULLS_LOG, $msg);
                                    echo $msg . "\n";
                                    $pullsFile[$containerHash]  = [
                                                                    'checked'       => time(),
                                                                    'name'          => $containerState['Names'],
                                                                    'regctlDigest'  => $regctlDigest,
                                                                    'imageDigest'   => $regctlDigest
                                                                ];

                                    $msg = 'Starting container: ' . $containerState['Names'];
                                    logger(CRON_PULLS_LOG, $msg);
                                    echo $msg . "\n";
                                    $restart = dockerStartContainer($containerState['Names']);
                                    logger(CRON_PULLS_LOG, 'dockerStartContainer:' . trim($restart));

                                    $msg = 'Inspecting container: ' . $containerState['Names'];
                                    logger(CRON_PULLS_LOG, $msg);
                                    echo $msg . "\n";
                                    $inspect = dockerInspect($containerState['Names']);
    
                                    if ($inspect) {
                                        $inspectArray = json_decode($inspect, true);
    
                                        foreach ($inspectArray[0]['Config']['Labels'] as $label => $val) {
                                            if (str_contains($label, 'image.version')) {
                                                $postVersion = $val;
                                                break;
                                            }
                                        }
                                    }

                                    if ($settingsFile['notifications']['triggers']['updated']['active']) {
                                        $notify['updated'][]    = [
                                                                    'container' => $containerState['Names'],
                                                                    'image'     => $image,
                                                                    'pre'       => ['digest' => str_replace('sha256:', '', $imageDigest), 'version' => $preVersion], 
                                                                    'post'      => ['digest' => str_replace('sha256:', '', $regctlDigest), 'version' => $postVersion]
                                                                ];
                                    }
                                } else {
                                    $msg = 'Invalid hash length: \'' . $update .'\'=' . strlen($update);
                                    logger(CRON_PULLS_LOG, $msg);
                                    echo $msg . "\n";

                                    if ($settingsFile['notifications']['triggers']['updated']['active']) {
                                        $notify['failed'][] = ['container' => $containerState['Names']];
                                    }
                                }
                                break;
                            case 2: //-- Check for updates
                                if ($settingsFile['notifications']['triggers']['updates']['active'] && $inspectImage[0]['Id'] != $inspectContainer[0]['Image']) {
                                    $notify['available'][] = ['container' => $containerState['Names']];
                                }
                                break;
                        }
                    }
                } else {
                    $msg = 'Skipping: ' . $containerState['Names'] . ' (\'' . $containerSettings['frequency'] . '\' frequency not met, last check \'' . $daysSince . '\')';
                    logger(CRON_PULLS_LOG, $msg);
                    echo $msg . "\n";                    
                }
            } else {
                $msg = 'Skipping: ' . $containerState['Names'] . ' (current 12h: \'' . date('g') . '\', current 24h: \'' . date('H') . '\', hour setting: \'' . $containerSettings['hour'] . '\', frequency setting: \'' . $containerSettings['frequency'] . '\', $six: \'' . $six . '\', $twelve: \'' . $twelve . '\')';
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