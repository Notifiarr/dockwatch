<?php

/*
----------------------------------
 ------  Created: 042124   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Container
{
    public function removeContainer($container)
    {
        $cmd = sprintf(DockerSock::REMOVE_CONTAINER, $container);
        return shell_exec($cmd . ' 2>&1');
    }

    public function startContainer($container)
    {
        $cmd = sprintf(DockerSock::START_CONTAINER, $container);    
        return shell_exec($cmd . ' 2>&1');
    }

    public function stopContainer($container)
    {
        $cmd = sprintf(DockerSock::STOP_CONTAINER, $container);    
        return shell_exec($cmd . ' 2>&1');
    }

    public function getOrphanContainers()
    {
        $cmd = DockerSock::ORPHAN_CONTAINERS;    
        return shell_exec($cmd . ' 2>&1');
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
                $stateFile = getServerFile('state');
                $query['data'] = $stateFile['file'];
            }
        
            foreach ($query['data'] as $container) {
                if (md5($container['Names']) == $query['hash']) {
                    return $container;
                }
            }
        }
    }
}
