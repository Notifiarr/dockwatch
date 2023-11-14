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

$updateSettings = $settings['containers'];
$states         = is_array($state) ? $state : json_decode($state, true);
$pulls          = getFile(PULL_FILE);

if ($updateSettings) {
    foreach ($updateSettings as $containerHash => $settings) {
        //-- SET TO IGNORE
        if (!$settings['updates']) {
            continue;
        }

        $containerState = [];
        foreach ($states as $state) {
            if (md5($state['Names']) == $containerHash) {
                $containerState = $state;
                break;
            }
        }

        if ($containerState) {
            if (date('H') == $settings['hour']) {
                $image = $containerState['inspect'][0]['Config']['Image'];

                echo 'Pulling: ' . $image. "\n";
                $pull = dockerPullContainer($image);

                echo 'Inspecting container: ' . $containerState['Names'] . "\n";
                $inspectContainer = dockerInspect($containerState['Names']);
                $inspectContainer = json_decode($inspectContainer, true);

                echo 'Inspecting image: ' . $image . "\n";
                $inspectImage = dockerInspect($image);
                $inspectImage = json_decode($inspectImage, true);

                echo 'Updating pull data: ' . $containerState['Names'] . "\n";
                $pulls[md5($containerState['Names'])]   = [
                                                            'checked'   => time(),
                                                            'name'      => $containerState['Names'],
                                                            'image'     => $inspectImage[0]['Id'],
                                                            'container' => $inspectContainer[0]['Image']
                                                        ];
            } else {
                echo 'Skipping: ' . $containerState['Names'] . "\n";
            }
        }
    }

    setFile(PULL_FILE, $pulls);
}
