<?php

/*
----------------------------------
 ------  Created: 021024   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Maint
{
    public function startMaintenance()
    {
        logger(MAINTENANCE_LOG, '$maintenance->startMaintenance() ->');

        $startContainer = $this->docker->startContainer($this->maintenanceContainerName);
        logger(MAINTENANCE_LOG, '$docker->startContainer() ' . trim($startContainer));

        logger(MAINTENANCE_LOG, '$maintenance->startMaintenance() <-');
    }

    public function stopMaintenance()
    {
        logger(MAINTENANCE_LOG, '$maintenance->stopMaintenance() ->');

        $stopContainer = $this->docker->stopContainer($this->maintenanceContainerName);
        logger(MAINTENANCE_LOG, '$docker->stopContainer() ' . trim($stopContainer));

        logger(MAINTENANCE_LOG, '$maintenance->stopMaintenance() <-');
    }

    public function removeMaintenance()
    {
        logger(MAINTENANCE_LOG, '$maintenance->removeMaintenance() ->');

        $removeContainer = $this->docker->removeContainer($this->maintenanceContainerName);
        logger(MAINTENANCE_LOG, '$docker->removeContainer() ' . trim($removeContainer));

        logger(MAINTENANCE_LOG, '$maintenance->removeMaintenance() <-');
    }

    public function pullMaintenance()
    {
        logger(MAINTENANCE_LOG, '$maintenance->pullMaintenance() ->');

        $pullImage = $this->docker->pullImage(APP_MAINTENANCE_IMAGE);
        logger(MAINTENANCE_LOG, '$docker->pullImage() ' . trim($pullImage));

        logger(MAINTENANCE_LOG, '$maintenance->pullMaintenance() <-');
    }

    public function createMaintenance()
    {
        $port   = intval($this->maintenancePort) > 0 ? intval($this->maintenancePort) : APP_MAINTENANCE_PORT;
        $ip     = $this->maintenanceIP;

        logger(MAINTENANCE_LOG, '$maintenance->createMaintenance() ->');
        logger(MAINTENANCE_LOG, 'using ip ' . $ip);
        logger(MAINTENANCE_LOG, 'using port ' . $port);

        $this->pullMaintenance();

        $apiRequest = apiRequest('docker/container/inspect', ['name' => $this->hostContainer['Names'], 'useCache' => false, 'format' => true]);
        logger(MAINTENANCE_LOG, 'docker/container/inspect: ' . json_encode($apiRequest, JSON_UNESCAPED_SLASHES));
        $inspectImage = $apiRequest['result'];
        $inspectImage = json_decode($inspectImage, true);

        $inspectImage[0]['Name']            = '/' . $this->maintenanceContainerName;
        $inspectImage[0]['Config']['Image'] = APP_MAINTENANCE_IMAGE;

        //-- CLEAR ALL PORTS
        $inspectImage[0]['HostConfig']['PortBindings']  = [];
        $inspectImage[0]['NetworkSettings']['Ports']    = [];

        //-- SET MAINTENANCE PORT
        $inspectImage[0]['HostConfig']['PortBindings']['80/tcp'][0]['HostPort'] = strval($port);
        $inspectImage[0]['NetworkSettings']['Ports']['80/tcp'][0]['HostPort']   = strval($port);

        //-- STATIC IP CHECK
        if ($ip) {
            if ($inspectImage[0]['NetworkSettings']['Networks']) {
                $network = array_keys($inspectImage[0]['NetworkSettings']['Networks'])[0];

                if ($inspectImage[0]['NetworkSettings']['Networks'][$network]['IPAMConfig']['IPv4Address']) {
                    $inspectImage[0]['NetworkSettings']['Networks'][$network]['IPAMConfig']['IPv4Address'] = $ip;
                }
                if ($inspectImage[0]['NetworkSettings']['Networks'][$network]['IPAddress']) {
                    $inspectImage[0]['NetworkSettings']['Networks'][$network]['IPAddress'] = $ip;
                }
            }
        }

        $this->removeMaintenance();

        logger(MAINTENANCE_LOG, 'dockerCreateContainer() ->');
        $docker = dockerCreateContainer($inspectImage);
        logger(MAINTENANCE_LOG, 'dockerCreateContainer() ' . json_encode($docker, JSON_UNESCAPED_SLASHES));
        logger(MAINTENANCE_LOG, 'dockerCreateContainer() <-');

        if (strlen($docker['Id']) == 64) {
            $this->docker->removeImage($inspectImage['Id']);
            $this->startMaintenance();
        }

        logger(MAINTENANCE_LOG, '$maintenance->createMaintenance() <-');
    }
}
