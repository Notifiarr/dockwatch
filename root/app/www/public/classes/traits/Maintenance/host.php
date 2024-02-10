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

        $docker = dockerStopContainer($this->hostContainer['Names']);
        logger(MAINTENANCE_LOG, 'dockerStopContainer() ' . trim($docker));
    
        logger(MAINTENANCE_LOG, '$maintenance->stopHost() <-');
    }

    public function startHost()
    {
        logger(MAINTENANCE_LOG, '$maintenance->startHost() ->');

        $docker = dockerStartContainer($this->hostContainer['Names']);
        logger(MAINTENANCE_LOG, 'dockerStartContainer() ' . trim($docker));
    
        logger(MAINTENANCE_LOG, '$maintenance->startHost() <-');
    }

    public function removeHost()
    {
        logger(MAINTENANCE_LOG, '$maintenance->removeHost() ->');

        $docker = dockerRemoveContainer($this->hostContainer['Names']);
        logger(MAINTENANCE_LOG, 'dockerRemoveContainer() ' . trim($docker));
    
        logger(MAINTENANCE_LOG, '$maintenance->removeHost() <-');
    }

    public function pullHost()
    {
        logger(MAINTENANCE_LOG, '$maintenance->pullHost() ->');

        $docker = dockerPullContainer($this->hostContainer['inspect'][0]['Config']['Image']);
        logger(MAINTENANCE_LOG, 'dockerPullContainer() ' . trim($docker));
    
        logger(MAINTENANCE_LOG, '$maintenance->pullHost() <-');
    }

    public function createHost()
    {
        logger(MAINTENANCE_LOG, '$maintenance->createHost() ->');
    
        $docker = dockerCreateContainer($this->hostContainer['inspect'][0]);
        logger(MAINTENANCE_LOG, 'dockerCreateContainer() ' . json_encode($docker, JSON_UNESCAPED_SLASHES));

        if (strlen($docker['Id']) == 64) {
            $this->startHost();
        }

        logger(MAINTENANCE_LOG, '$maintenance->createHost() <-');
    }
}
