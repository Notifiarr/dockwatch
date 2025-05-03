<?php

/*
----------------------------------
 ------  Created: 021024   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Host
{
    public function stopHost()
    {
        logger(MAINTENANCE_LOG, '$maintenance->stopHost() ->');

        $stopContainer = $this->docker->stopContainer($this->hostContainer['Names']);
        logger(MAINTENANCE_LOG, '$docker->stopContainer() ' . trim($stopContainer));

        logger(MAINTENANCE_LOG, '$maintenance->stopHost() <-');
    }

    public function startHost()
    {
        logger(MAINTENANCE_LOG, '$maintenance->startHost() ->');

        $startContainer = $this->docker->startContainer($this->hostContainer['Names']);
        logger(MAINTENANCE_LOG, '$docker->startContainer() ' . trim($startContainer));

        logger(MAINTENANCE_LOG, '$maintenance->startHost() <-');
    }

    public function removeHost()
    {
        logger(MAINTENANCE_LOG, '$maintenance->removeHost() ->');

        $removeContainer = $this->docker->removeContainer($this->hostContainer['Names']);
        logger(MAINTENANCE_LOG, '$docker->removeContainer() ' . trim($removeContainer));

        logger(MAINTENANCE_LOG, '$maintenance->removeHost() <-');
    }

    public function pullHost()
    {
        logger(MAINTENANCE_LOG, '$maintenance->pullHost() ->');

        $pullImage = $this->docker->pullImage($this->hostContainer['inspect'][0]['Config']['Image']);
        logger(MAINTENANCE_LOG, '$docker->pullImage() ' . trim($pullImage));

        logger(MAINTENANCE_LOG, '$maintenance->pullHost() <-');
    }

    public function createHost()
    {
        logger(MAINTENANCE_LOG, '$maintenance->createHost() ->');

        $docker = dockerCreateContainer($this->hostContainer['inspect'][0]);
        logger(MAINTENANCE_LOG, 'dockerCreateContainer() ' . json_encode($docker, JSON_UNESCAPED_SLASHES));

        if (strlen($docker['Id']) == 64) {
            $currentImageID = explode('|', $this->docker->getImageByDigest($this->hostContainer['inspect'][0]['Image']))[0];
            $this->docker->removeImage($currentImageID);

            $this->startHost();
        }

        logger(MAINTENANCE_LOG, '$maintenance->createHost() <-');
    }
}
