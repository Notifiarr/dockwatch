<?php

/*
----------------------------------
 ------  Created: 042124   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Volume
{
    public function getOrphanVolumes()
    {
        $cmd = DockerSock::ORPHAN_VOLUMES;    
        return shell_exec($cmd . ' 2>&1');
    }
}
