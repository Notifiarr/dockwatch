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
        $networks   = json_decode(shell_exec($cmd . ' 2>&1'), true);

        foreach ($networks as $network) {
            if (str_contains_any($network['Name'], ['bridge', 'host', 'none'])) {
                continue;
            }

            $cmd        = sprintf(DockerSock::INSPECT_NETWORK, $network['ID']);
            $inspect    = json_decode(shell_exec($cmd . ' 2>&1'), true);

            if (empty($inspect[0]['Containers'])) {
                $orphans[] = $network;
            }
        }

        return json_encode($orphans);
    }

    public function pruneNetwork()
    {
        $cmd = DockerSock::PRUNE_NETWORK;
        return shell_exec($cmd . ' 2>&1');
    }

    public function removeNetwork($network)
    {
        $cmd = sprintf(DockerSock::REMOVE_NETWORK, $network);
        return shell_exec($cmd . ' 2>&1');
    }
}
