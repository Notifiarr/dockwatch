<?php

/*
----------------------------------
 ------  Created: 042124   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Container
{
    public function getContainerPort($containerName, $params = '')
    {
        $cmd = sprintf(DockerSock::CONTAINER_PORT, $this->shell->prepare($containerName), $this->shell->prepare($params));
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function removeContainer($containerName)
    {
        $cmd = sprintf(DockerSock::REMOVE_CONTAINER, $this->shell->prepare($containerName));
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function startContainer($containerName)
    {
        $cmd = sprintf(DockerSock::START_CONTAINER, $this->shell->prepare($containerName));    
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function stopContainer($containerName)
    {
        $nameHash   = md5($containerName);
        $container  = $this->database->getContainerFromHash($nameHash);
        $delay      = intval($container['shutdownDelaySeconds']) >= 5 ? ' -t ' . $container['shutdownDelaySeconds'] : ' -t 120';

        if ($container['shutdownDelay']) {
            logger(SYSTEM_LOG, 'stopContainer() delaying stop command for container ' . $containerName . ' with ' . $delay);
        }

        $cmd = sprintf(DockerSock::STOP_CONTAINER, $this->shell->prepare($containerName), ($container['shutdownDelay'] ? $this->shell->prepare($delay) : ''));

        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function getOrphanContainers()
    {
        $cmd = DockerSock::ORPHAN_CONTAINERS;    
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function findContainer($query = [])
    {
        if ($query['id']) {
            foreach ($query['data'] as $process) {
                if ($process['ID'] == $query['id']) {
                    return $process['Names'];
                }
            }
        }

        if ($query['hash']) {
            if (!$query['data']) {
                $query['data'] = apiRequest('file-state')['result'];
            }
        
            foreach ($query['data'] as $container) {
                if (md5($container['Names']) == $query['hash']) {
                    return $container;
                }
            }
        }
    }

    public function getContainerNetworkDependencies($parentId, $processList)
    {
        $dependencies = [];

        foreach ($processList as $process) {
            $networkMode = $process['inspect'][0]['HostConfig']['NetworkMode'];
    
            if (str_contains($networkMode, ':')) {
                list($null, $networkContainer) = explode(':', $networkMode);
    
                if ($networkContainer == $parentId) {
                    $dependencies[] = $process['Names'];
                }
            }
        }
    
        return $dependencies;
    }

    public function getContainerLabelDependencies($containerName, $processList)
    {
        $dependencies = [];

        foreach ($processList as $process) {
            $labels = $process['inspect'][0]['Config']['Labels'] ? $process['inspect'][0]['Config']['Labels'] : [];
    
            foreach ($labels as $name => $key) {
                if (str_contains($name, 'depends_on')) {
                    list($container, $condition) = explode(':', $key);
    
                    if ($container == $containerName) {
                        $dependencies[] = $process['Names'];
                    }
                }
            }
        }
    
        return $dependencies;
    }

    public function setContainerDependencies($processList)
    {
        $dependencyList = [];
        foreach ($processList as $process) {
            $dependencies = $this->getContainerNetworkDependencies($process['ID'], $processList);
    
            if ($dependencies) {
                $dependencyList[$process['Names']] = ['id' => $process['ID'], 'containers' => $dependencies];
            }
        }
    
        apiRequest('file-dependency', [], ['contents' => $dependencyList]);
    
        return $dependencyList;
    }

}
