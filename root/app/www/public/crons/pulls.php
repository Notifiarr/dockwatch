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
echo date('c') . ' Cron: pulls ->' . "\n";

if (!canCronRun('pulls', $settingsTable)) {
    exit();
}

$containersTable    = apiRequest('database-getContainers')['result'];
$notify             = [];
$startStamp         = new DateTime();

if ($containersTable) {
    $imagesUpdated = [];

    foreach ($containersTable as $containerSettings) {
        $containerHash = $containerSettings['hash'];
        $containerState = $docker->findContainer(['hash' => $containerHash, 'data' => $stateFile]);

        //-- AUTO RESTART IF ENABLED
        if ($containerSettings['autoRestart'] && $containerState) {
            try {
                $cron = Cron\CronExpression::factory($containerSettings['autoRestartFrequency']);
                if ($cron->isDue($startStamp)) {
                    $msg = 'Auto Restarting: ' . $containerState['Names'];
                    logger(CRON_PULLS_LOG, $msg);
                    echo date('c') . ' ' . $msg . "\n";

                    $apiRequest = apiRequest('docker-restartContainer', [], ['name' => $containerState['Names'], 'dependencies' => 1]);

                    $msg = 'Auto Restart: ' . json_encode($apiRequest);
                    logger(CRON_PULLS_LOG, $msg);
                    echo date('c') . ' ' . $msg . "\n";

                    $msg = 'Auto Restarted: ' . $containerState['Names'];
                    logger(CRON_PULLS_LOG, $msg);
                    echo date('c') . ' ' . $msg . "\n";
                }
            } catch (Exception $e) {
                $msg = 'Skipping Auto Restart: ' . $containerState['Names'] . ' frequency setting is not a valid cron syntax \'' . $containerSettings['autoRestartFrequency'] . '\'';
                logger(CRON_PULLS_LOG, $msg);
                echo date('c') . ' ' . $msg . "\n";
            }
        }

        //-- SET TO IGNORE
        if (!$containerSettings['updates']) {
            continue;
        }

        $preVersion = $postVersion = $cron = '';
        if ($containerState) {
            $isDockwatch = isDockwatchContainer($containerState) ? true : false;
            $pullHistory = $pullsFile[$containerHash];

            try {
                $cron = Cron\CronExpression::factory($containerSettings['frequency']);
            } catch (Exception $e) {
                $msg = 'Skipping: ' . $containerState['Names'] . ' frequency setting is not a valid cron syntax \'' . $containerSettings['frequency'] . '\'';
                logger(CRON_PULLS_LOG, $msg);
                echo date('c') . ' ' . $msg . "\n";
                continue;
            }

            if ($cron->isDue($startStamp)) {
                $image          = $docker->isIO($containerState['inspect'][0]['Config']['Image']);
                $currentImageID = $containerState['ID'];

                if (!$image) {
                    $msg = 'Skipping local (has no Config.Image): ' . $containerState['Names'];
                    logger(CRON_PULLS_LOG, $msg, 'error');
                    echo date('c') . ' ' . $msg . "\n";
                    continue;
                }

                $msg = 'Inspecting image: ' . $image;
                logger(CRON_PULLS_LOG, $msg);
                echo date('c') . ' ' . $msg . "\n";
                $inspectImage = $docker->inspect($image, false);
                logger(CRON_PULLS_LOG, '$inspectImage=' . $inspectImage);
                $inspectImage = json_decode($inspectImage, true);

                if ($inspectImage) {
                    foreach ($inspectImage[0]['Config']['Labels'] as $label => $val) {
                        if (str_contains($label, 'image.version')) {
                            $preVersion = $val;
                            break;
                        }
                    }
                }

                $msg = 'Pre image version: ' . $preVersion;
                logger(CRON_PULLS_LOG, $msg);
                echo date('c') . ' ' . $msg . "\n";

                $msg = 'Getting registry digest: ' . $image;
                logger(CRON_PULLS_LOG, $msg);
                echo date('c') . ' ' . $msg . "\n";
                $regctlDigest = trim(regctlCheck($image));

                if (!$regctlDigest || str_contains($regctlDigest, 'Error')) {
                    $msg = 'Skipping checks (regctl failed): \'' . $regctlDigest . '\'';
                    logger(CRON_PULLS_LOG, $msg, 'error');
                    echo date('c') . ' ' . $msg . "\n";
                    continue;
                }

                //-- LOOP ALL IMAGE DIGESTS, STOP AT A MATCH
                foreach ($inspectImage[0]['RepoDigests'] as $digest) {
                    list($cr, $imageDigest) = explode('@', $digest);

                    if ($imageDigest == $regctlDigest) {
                        break;
                    }
                }

                $msg = 'Updating pull data: ' . $containerState['Names'] . "\n";
                $msg .= '|__ regctl \'' . truncateMiddle(str_replace('sha256:', '', $regctlDigest), 30) . '\' image \'' . truncateMiddle(str_replace('sha256:', '', $imageDigest), 30) .'\'';
                logger(CRON_PULLS_LOG, $msg);
                echo date('c') . ' ' . $msg . "\n";

                $pullsFile[$containerHash]  = [
                                                'checked'       => time(),
                                                'name'          => $containerState['Names'],
                                                'regctlDigest'  => $regctlDigest,
                                                'imageDigest'   => $imageDigest
                                            ];

                //-- SKIP IF AGE < MINAGE
                $digestTag = explode(':', $image)[0] . '@' . $regctlDigest;
                $checkImageAge = regctlGetCreatedDate($digestTag);
                if ($checkImageAge < $containerSettings['minage']) {
                    $msg = 'Skipping container update: ' . $containerState['Names'] . ' (does not meet the minimum age requirement of '.$containerSettings['minage'].' days, got '.$checkImageAge.' days)';
                    logger(CRON_PULLS_LOG, $msg);
                    echo date('c') . ' ' . $msg . "\n";

                    if ($containerSettings['updates'] == 1) {
                        $containerSettings['updates'] = 2;
                    }
                }

                //-- DONT AUTO UPDATE THIS CONTAINER, CHECK ONLY
                if (skipContainerActions($image, $skipContainerActions)) {
                    if ($isDockwatch) {
                        $msg = '[BYPASS] Skipping container update: ' . $containerState['Names'] . ' (image is listed in skipContainerActions())';
                    } else {
                        $msg = 'Skipping container update: ' . $containerState['Names'] . ' (image is listed in skipContainerActions())';

                        if ($containerSettings['updates'] == 1) {
                            $containerSettings['updates'] = 2;
                        }
                    }

                    logger(CRON_PULLS_LOG, $msg);
                    echo date('c') . ' ' . $msg . "\n";
                }

                if (!$isDockerApiAvailable) {
                    $msg = 'Skipping container update: ' . $containerState['Names'] . ' (docker engine api access is not available)';
                    logger(CRON_PULLS_LOG, $msg);
                    echo date('c') . ' ' . $msg . "\n";

                    if ($containerSettings['updates'] == 1) {
                        $containerSettings['updates'] = 2;
                    }
                }

                if (in_array($image, $imagesUpdated) || $regctlDigest != $imageDigest) {
                    $imagesUpdated[] = $image; //-- THIS IS TO TRACK WHAT UPDATES SO MULTIPLE CONTAINERS ON THE SAME IMAGE ALL UPDATE

                    switch ($containerSettings['updates']) {
                        case 1: //-- Auto update
                            if ($isDockwatch) {
                                $msg = '$maintenance->apply(), check the maintenance log for update details';
                                logger(CRON_PULLS_LOG, $msg);
                                echo date('c') . ' ' . $msg . "\n";

                                $pullsFile[$containerHash]  = [
                                                                'checked'       => time(),
                                                                'name'          => $containerState['Names'],
                                                                'regctlDigest'  => $regctlDigest,
                                                                'imageDigest'   => $regctlDigest
                                                            ];

                                //-- UPDATE THE PULLS FILE BEFORE THIS CALL SINCE THIS STOPS THE PROCESS
                                apiRequest('file-pull', [], ['contents' => $pullsFile]);

                                $maintenance = new Maintenance();
                                $maintenance->apply('update');
                            } else {
                                $msg = 'Inspecting container: ' . $containerState['Names'];
                                logger(CRON_PULLS_LOG, $msg);
                                echo date('c') . ' ' . $msg . "\n";
                                $inspect = $docker->inspect($containerState['Names'], false);

                                $msg = 'Pulling image: ' . $image;
                                logger(CRON_PULLS_LOG, $msg);
                                echo date('c') . ' ' . $msg . "\n";
                                $docker->pullImage($image);

                                $msg = 'Stopping container: ' . $containerState['Names'];
                                logger(CRON_PULLS_LOG, $msg);
                                echo date('c') . ' ' . $msg . "\n";
                                $stop = $docker->stopContainer($containerState['Names']);
                                logger(CRON_PULLS_LOG, trim($stop));

                                $msg = 'Removing container: ' . $containerState['Names'] . ' (' . $containerState['ID'] . ')';
                                logger(CRON_PULLS_LOG, $msg);
                                echo date('c') . ' ' . $msg . "\n";
                                $remove = $docker->removeContainer($containerState['Names']);
                                logger(CRON_PULLS_LOG, trim($remove));

                                $msg = 'Updating container: ' . $containerState['Names'];
                                logger(CRON_PULLS_LOG, $msg);
                                echo date('c') . ' ' . $msg . "\n";
                                $update = dockerCreateContainer(json_decode($inspect, true));
                                logger(CRON_PULLS_LOG, 'dockerCreateContainer: ' . trim(json_encode($update, JSON_UNESCAPED_SLASHES)));

                                if (strlen($update['Id']) == 64) {
                                    // REMOVE THE IMAGE AFTER UPDATE
                                    $msg = 'Removing old image: ' . $currentImageID;
                                    logger(CRON_PULLS_LOG, $msg);
                                    echo date('c') . ' ' . $msg . "\n";
                                    $removeImage = $docker->removeImage($currentImageID);
                                    logger(CRON_PULLS_LOG, '$docker->removeImage: ' . trim(json_encode($removeImage, JSON_UNESCAPED_SLASHES)));

                                    $msg = 'Updating pull data: ' . $containerState['Names'];
                                    logger(CRON_PULLS_LOG, $msg);
                                    echo date('c') . ' ' . $msg . "\n";
                                    $pullsFile[$containerHash]  = [
                                                                    'checked'       => time(),
                                                                    'name'          => $containerState['Names'],
                                                                    'regctlDigest'  => $regctlDigest,
                                                                    'imageDigest'   => $regctlDigest
                                                                ];

                                    if (str_contains($containerState['State'], 'running')) {
                                        $msg = 'Starting container: ' . $containerState['Names'];
                                        logger(CRON_PULLS_LOG, $msg);
                                        echo date('c') . ' ' . $msg . "\n";
                                        $start = $docker->startContainer($containerState['Names']);
                                        logger(CRON_PULLS_LOG, 'dockerStartContainer:' . trim($start));
                                    } else {
                                        logger(CRON_PULLS_LOG, 'container was not running, not starting it');
                                    }

                                    $msg = 'Inspecting image: ' . $image;
                                    logger(CRON_PULLS_LOG, $msg);
                                    echo date('c') . ' ' . $msg . "\n";
                                    $inspectImage   = $docker->inspect($image, false);
                                    $inspectImage   = json_decode($inspectImage, true);

                                    if ($inspectImage) {
                                        foreach ($inspectImage[0]['Config']['Labels'] as $label => $val) {
                                            if (str_contains($label, 'image.version')) {
                                                $postVersion = $val;
                                                break;
                                            }
                                        }
                                    }

                                    $msg = 'Post image version: ' . $postVersion;
                                    logger(CRON_PULLS_LOG, $msg);
                                    echo date('c') . ' ' . $msg . "\n";

                                    $dependencyFile = apiRequest('file-dependency')['result'];
                                    $dependencies   = $dependencyFile[$containerState['Names']]['containers'];

                                    if (is_array($dependencies)) {
                                        $msg = 'dependencies found ' . count($dependencies);
                                        logger(CRON_PULLS_LOG, $msg);
                                        echo date('c') . ' ' . $msg . "\n";

                                        updateDependencyParentId($containerState['Names'], $update['Id']);

                                        foreach ($dependencies as $dependencyContainer) {
                                            $msg = '[dependency] docker-inspect: ' . $dependencyContainer;
                                            logger(CRON_PULLS_LOG, $msg);
                                            echo date('c') . ' ' . $msg . "\n";
                                            $apiResponse = apiRequest('docker-inspect', ['name' => $dependencyContainer, 'useCache' => false, 'format' => true]);
                                            logger(CRON_PULLS_LOG, 'dockerInspect:' . json_encode($apiResponse, JSON_UNESCAPED_SLASHES));
                                            $inspectImage = $apiResponse['result'];

                                            $msg = '[dependency] docker-stopContainer: ' . $dependencyContainer;
                                            logger(CRON_PULLS_LOG, $msg);
                                            echo date('c') . ' ' . $msg . "\n";
                                            $apiRequest = apiRequest('docker-stopContainer', [], ['name' => $dependencyContainer]);
                                            logger(CRON_PULLS_LOG, 'dockerStopContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));

                                            $msg = '[dependency] docker-removeContainer: ' . $dependencyContainer;
                                            logger(CRON_PULLS_LOG, $msg);
                                            echo date('c') . ' ' . $msg . "\n";
                                            $apiRequest = apiRequest('docker-removeContainer', [], ['name' => $dependencyContainer]);
                                            logger(CRON_PULLS_LOG, 'docker-removeContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));

                                            $msg = '[dependency] docker-createContainer: ' . $dependencyContainer;
                                            logger(CRON_PULLS_LOG, $msg);
                                            echo date('c') . ' ' . $msg . "\n";
                                            $apiRequest = apiRequest('docker-createContainer', [], ['inspect' => $inspectImage]);
                                            logger(CRON_PULLS_LOG, 'docker-createContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                                            $update         = $apiRequest['result'];
                                            $createResult   = 'failed';

                                            if (strlen($update['Id']) == 64) {
                                                $createResult = 'complete';
                                                logger(CRON_PULLS_LOG, 'Container ' . $dependencyContainer . ' re-create: ' . $createResult);

                                                if (str_contains($containerState['State'], 'running')) {
                                                    $msg = '[dependency] docker-startContainer: ' . $dependencyContainer;
                                                    logger(CRON_PULLS_LOG, $msg);
                                                    echo date('c') . ' ' . $msg . "\n";
                                                    $apiRequest = apiRequest('docker-startContainer', [], ['name' => $dependencyContainer]);
                                                    logger(CRON_PULLS_LOG, 'docker-startContainer:' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
                                                } else {
                                                    logger(CRON_PULLS_LOG, 'container was not running, not starting it');
                                                }
                                            }
                                        }
                                    }

                                    if (apiRequest('database-isNotificationTriggerEnabled', ['trigger' => 'updated'])['result'] && !$containerSettings['disableNotifications']) {
                                        $notify['updated'][]    = [
                                                                    'container' => $containerState['Names'],
                                                                    'image'     => $image,
                                                                    'pre'       => ['digest' => str_replace('sha256:', '', $imageDigest), 'version' => $preVersion],
                                                                    'post'      => ['digest' => str_replace('sha256:', '', $regctlDigest), 'version' => $postVersion]
                                                                ];
                                    }
                                } else {
                                    $msg = 'Invalid hash length: \'' . $update .'\'=' . strlen($update['Id']);
                                    logger(CRON_PULLS_LOG, $msg);
                                    echo date('c') . ' ' . $msg . "\n";

                                    if (apiRequest('database-isNotificationTriggerEnabled', ['trigger' => 'updated'])['result'] && !$containerSettings['disableNotifications']) {
                                        $notify['failed'][] = ['container' => $containerState['Names']];
                                    }
                                }
                            }
                            break;
                        case 2: //-- Check for updates
                            if (apiRequest('database-isNotificationTriggerEnabled', ['trigger' => 'updates'])['result'] && !$containerSettings['disableNotifications'] && $inspectImage[0]['Id'] != $inspectContainer[0]['Image']) {
                                $notify['available'][] = [
                                    'container' => $containerState['Names'],
                                    'minage' => $containerSettings['minage'] ?? 0,
                                    'currentage' => $checkImageAge ?? 0
                                ];
                            }
                            break;
                    }
                }
            } else {
                $msg = 'Skipping: ' . $containerState['Names'] . ', frequency setting will run: ' . $cron->getNextRunDate()->format('Y-m-d H:i:s');
                logger(CRON_PULLS_LOG, $msg);
                echo date('c') . ' ' . $msg . "\n";
            }
        }
    }

    apiRequest('file-pull', [], ['contents' => $pullsFile]);

    if ($notify) {
        //-- IF THEY USE THE SAME PLATFORM, COMBINE THEM
        if (apiRequest('database-getNotificationLinkPlatformFromName', ['name' => 'updated'])['result'] == apiRequest('database-getNotificationLinkPlatformFromName', ['name' => 'updates'])['result']) {
            $payload = ['event' => 'updates', 'available' => $notify['available'], 'updated' => $notify['updated']];
            $notifications->notify(0, 'updates', $payload);

            logger(CRON_PULLS_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
        } else {
            if ($notify['available']) {
                $payload = ['event' => 'updates', 'available' => $notify['available']];
                $notifications->notify(0, 'updates', $payload);

                logger(CRON_PULLS_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
            }

            if ($notify['updated']) {
                $payload = ['event' => 'updates', 'updated' => $notify['updated']];
                $notifications->notify(0, 'updated', $payload);

                logger(CRON_PULLS_LOG, 'Notification payload: ' . json_encode($payload, JSON_UNESCAPED_SLASHES));
            }
        }
    }
}

echo date('c') . ' Cron: pulls <-' . "\n";
logger(CRON_PULLS_LOG, 'run <-');
