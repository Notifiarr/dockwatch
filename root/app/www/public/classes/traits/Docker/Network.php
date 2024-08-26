<?php

/*
----------------------------------
 ------  Created: 042124   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Network
{
    public function getOrphanNetworks()
    {
        $orphans = [];

        $cmd        = DockerSock::ORPHAN_NETWORKS;
        $networks   = json_decode($this->shell->exec($cmd . ' 2>&1'), true);

        foreach ($networks as $network) {
            if (str_contains_any($network['Name'], ['bridge', 'host', 'none'])) {
                continue;
            }

            $cmd        = sprintf(DockerSock::INSPECT_NETWORK, $network['ID']);
            $inspect    = json_decode($this->shell->exec($cmd . ' 2>&1'), true);

            if (empty($inspect[0]['Containers'])) {
                $orphans[] = $network;
            }
        }

        return json_encode($orphans);
    }

    public function getNetworks($params = '')
    {
        $cmd = sprintf(DockerSock::GET_NETWORKS, $this->shell->prepare($params));
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function pruneNetwork()
    {
        $cmd = DockerSock::PRUNE_NETWORK;
        return $this->shell->exec($cmd . ' 2>&1');
    }

    public function removeNetwork($network)
    {
        $cmd = sprintf(DockerSock::REMOVE_NETWORK, $this->shell->prepare($network));
        return $this->shell->exec($cmd . ' 2>&1');
    }
}
