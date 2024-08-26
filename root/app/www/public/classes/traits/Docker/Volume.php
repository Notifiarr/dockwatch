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
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function pruneVolume()
    {
        $cmd = DockerSock::PRUNE_VOLUME;
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function removeVolume($volume)
    {
        $cmd = sprintf(DockerSock::REMOVE_VOLUME, $this->shell->prepare($volume));
        return $this->shell->exec($cmd . ' 2>&1');
    }
}
