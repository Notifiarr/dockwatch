<?php

/*
----------------------------------
 ------  Created: 042124   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Container
{
    public function removeContainer($containerName)
    {
        $cmd = sprintf(DockerSock::REMOVE_CONTAINER, $containerName);
        return shell_exec($cmd . ' 2>&1');
    }

    public function startContainer($containerName, $dependencies = false)
    {
        $cmd = sprintf(DockerSock::START_CONTAINER, $containerName);    
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
