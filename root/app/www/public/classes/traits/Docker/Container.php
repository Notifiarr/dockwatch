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
}
