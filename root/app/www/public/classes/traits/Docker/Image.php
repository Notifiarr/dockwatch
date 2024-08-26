<?php

/*
----------------------------------
 ------  Created: 042124   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Image
{
    public function removeImage($image)
    {
        $cmd = sprintf(DockerSock::REMOVE_IMAGE, $this->shell->prepare($image));
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function pullImage($image)
    {
        $cmd = sprintf(DockerSock::PULL_IMAGE, $this->shell->prepare($image));
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function pruneImage()
    {
        $cmd = DockerSock::PRUNE_IMAGE;
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function getImageSizes()
    {
        $cmd = DockerSock::IMAGE_SIZES;
        return $this->shell->exec($cmd . ' 2>&1');
    }
}
