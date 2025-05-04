<?php

/*
----------------------------------
 ------  Created: 050325   ------
 ------  Austin Best	   ------
----------------------------------
*/

switch ($path) {
    case 'container': //-- .../api/docker/container/$action
        switch (true) {
            case $IS_GET && $action == 'inspect':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $apiRequestResponse = $docker->inspect($parameters['name'], $parameters['useCache'], $parameters['format'], $parameters['params']);                
                break;
            case $IS_GET && $action == 'logs':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $apiRequestResponse = $docker->logs($parameters['name']);
                break;
            case $IS_GET && $action == 'ports':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $apiRequestResponse = $docker->getContainerPort($parameters['name'], $parameters['params']);
                break;
            case $IS_GET && $action == 'shell':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }
                if (!$parameters['params']) {
                    apiResponse(400, ['error' => 'Missing parameters']);
                }

                $container      = $parameters['name'];
                $command        = $parameters['params'];

                $apiRequestResponse = json_encode($docker->exec($container, $command));
                break;
            case $IS_GET && $action == 'stats':
                $apiRequestResponse = $docker->stats($parameters['useCache']);
                break;
            case $IS_POST && $action == 'create':
                if (!$payload['inspect']) {
                    apiResponse(400, ['error' => 'Missing inspect parameter']);
                }

                $apiRequestResponse = dockerCreateContainer(json_decode($payload['inspect'], true));
                break;
            case $IS_POST && $action == 'kill':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $apiRequestResponse = $docker->killContainer($payload['name']);
                break;
            case $IS_POST && $action == 'pull':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $apiRequestResponse = $docker->pullImage($payload['name']);
                break;
            case $IS_POST && $action == 'remove':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $apiRequestResponse = $docker->removeContainer($payload['name']);
                break;
            case $IS_POST && $action == 'restart':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $stopContainer = $docker->stopContainer($payload['name']);
                $return[] = 'docker/container/stop: ' . json_encode($stopContainer, JSON_UNESCAPED_SLASHES);
                $startContainer = $docker->startContainer($payload['name']);
                $return[] = 'docker/container/start: ' . json_encode($startContainer, JSON_UNESCAPED_SLASHES);

                if ($payload['dependencies']) {
                    $dependencyFile = getFile(DEPENDENCY_FILE);
                    $dependencies   = $dependencyFile[$payload['name']]['containers'];
                    $dependencies   = is_array($dependencies) ? $dependencies : [];

                    if ($dependencies) {
                        $return[] = 'restarting dependencies...';

                        foreach ($dependencies as $dependency) {
                            $stopContainer = $docker->stopContainer($dependency);
                            $return[] = 'docker/container/stop: ' . json_encode($stopContainer, JSON_UNESCAPED_SLASHES);
                            $startContainer = $docker->startContainer($dependency);
                            $return[] = 'docker/container/start: ' . json_encode($startContainer, JSON_UNESCAPED_SLASHES);
                        }
                    }
                }

                $apiRequestResponse = $payload['dependencies'] ? $return : $startContainer;
                break;
            case $IS_POST && $action == 'start':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $startContainer = $docker->startContainer($payload['name']);
                $return[] = 'docker/container/start: ' . json_encode($startContainer, JSON_UNESCAPED_SLASHES);

                if ($payload['dependencies']) {
                    $dependencyFile = getFile(DEPENDENCY_FILE);
                    $dependencies   = $dependencyFile[$payload['name']]['containers'];
                    $dependencies   = is_array($dependencies) ? $dependencies : [];

                    if ($dependencies) {
                        $return[] = 'starting dependenices...';

                        foreach ($dependencies as $dependency) {
                            $startContainer = $docker->startContainer($dependency);
                            $return[] = 'docker/container/start: ' . json_encode($startContainer, JSON_UNESCAPED_SLASHES);
                        }
                    }
                }

                $apiRequestResponse = $payload['dependencies'] ? $return : $startContainer;
                break;
            case $IS_POST && $action == 'stop':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $stopContainer = $docker->stopContainer($payload['name']);
                $return[] = 'docker/container/stop: ' . json_encode($stopContainer, JSON_UNESCAPED_SLASHES);

                if ($payload['dependencies']) {
                    $dependencyFile = getFile(DEPENDENCY_FILE);
                    $dependencies   = $dependencyFile[$payload['name']]['containers'];
                    $dependencies   = is_array($dependencies) ? $dependencies : [];

                    if ($dependencies) {
                        $return[] = 'stopping dependenices...';

                        foreach ($dependencies as $dependency) {
                            $stopContainer = $docker->stopContainer($dependency);
                            $return[] = 'docker/container/stop: ' . json_encode($stopContainer, JSON_UNESCAPED_SLASHES);
                        }
                    }
                }

                $apiRequestResponse = $payload['dependencies'] ? $return : $stopContainer;
                break;
            default:
                apiResponse(400, ['error' => 'Invalid action for requested path']);
                break;
        }
        break;
    case 'create': //-- .../api/docker/create/$action
        switch (true) {
            case $IS_GET && $action == 'compose':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $apiRequestResponse = dockerAutoCompose($parameters['name']);
                break;
            case $IS_GET && $action == 'run':
                if (!$parameters['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $apiRequestResponse = dockerAutoRun($parameters['name']);
                break;
            default:
                apiResponse(400, ['error' => 'Invalid action for requested path']);
                break;
        }
        break;
    case 'image': //-- ../api/docker/image/$action
        switch (true) {
            case $IS_POST && $action == 'remove':
                if (!$payload['image']) {
                    apiResponse(400, ['error' => 'Missing image parameter']);
                }

                $apiRequestResponse = $docker->removeImage($payload['image']);
                break;
        }
        break;
    case 'images': //-- ../api/docker/images/$action
        switch (true) {
            case $IS_GET && $action == 'sizes':
                $apiRequestResponse = $docker->getImageSizes();
                break;
        }
        break;
    case 'login': //-- ../api/docker/login
        switch (true) {
            case $IS_POST:
                if (!$payload['registry']) {
                    apiResponse(400, ['error' => 'Missing registry parameter']);
                }
                if (!$payload['username']) {
                    apiResponse(400, ['error' => 'Missing username parameter']);
                }
                if (!$payload['password']) {
                    apiResponse(400, ['error' => 'Missing password parameter']);
                }
        
                $apiRequestResponse = $docker->login($payload['registry'], $payload['username'], $payload['password']);
                break;
            default:
                apiResponse(400, ['error' => 'Invalid action for requested path']);
                break;
        }
        break;
    case 'network': //-- ../api/docker/network/$action
        switch (true) {
            case $IS_POST && $action == 'remove':
                if (!$payload['name']) {
                    apiResponse(400, ['error' => 'Missing name parameter']);
                }

                $apiRequestResponse = $docker->removeNetwork($payload['id']);
                break;
        }
        break;
    case 'networks': //-- ../api/docker/networks
        $apiRequestResponse = $docker->getNetworks($parameters['params']);
        break;
    case 'orphans': //-- ../api/docker/orphans/$action
        switch (true) {
            case $IS_GET && $action == 'containers':
                $apiRequestResponse = $docker->getOrphanContainers();
                break;
            case $IS_GET && $action == 'networks':
                $apiRequestResponse = $docker->getOrphanNetworks();
                break;
            case $IS_GET && $action == 'volumes':
                $apiRequestResponse = $docker->getOrphanVolumes();
                break;
        }
        break;
    case 'permissions': //-- ../api/docker/permissions
        $apiRequestResponse = dockerPermissionCheck();
        break;
    case 'processList': //-- ../api/docker/processList
        $apiRequestResponse = $docker->processList($parameters['useCache'], $parameters['format'], $parameters['params']);
        break;
    case 'unused': //-- ../api/docker/unused/$action
        switch (true) {
            case $IS_GET && $action == 'containers':
                $apiRequestResponse = $docker->getUnusedContainers();
                break;
        }
        break;
    case 'volume': //-- ../api/docker/volume/$action
        switch (true) {
            case $IS_POST && $action == 'remove':
                if (!$payload['id']) {
                    apiResponse(400, ['error' => 'Missing id parameter']);
                }

                $apiRequestResponse = $docker->removeVolume($payload['name']);
                break;
        }
        break;
    default:
        apiResponse(400, ['error' => 'Invalid path for requested route']);
        break;
}
