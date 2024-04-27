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

    public function pruneVolume()
    {
        $cmd = DockerSock::PRUNE_VOLUME;
        return shell_exec($cmd . ' 2>&1');
    }

    public function removeVolume($volume)
    {
        $cmd = sprintf(DockerSock::REMOVE_VOLUME, $volume);
        return shell_exec($cmd . ' 2>&1');
    }
}
