<?php

/*
----------------------------------
 ------  Created: 083024   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Servers
{
    public function setServers($serverList = [])
    {
        if (!$serverList) {
            return $this->serversTable;
        }

        foreach ($serverList as $serverId => $serverSettings) {
            switch (true) {
                case $serverSettings['remove']:
                    $q = "DELETE FROM " . SERVERS_TABLE . "
                          WHERE id = " . $serverId;
                    break;
                case !$serverId:
                    $q = "INSERT INTO " . SERVERS_TABLE . "
                          (`name`, `url`, `apikey`) 
                          VALUES 
                          ('" . $this->prepare($serverSettings['name']) . "', '" . $this->prepare($serverSettings['url']) . "', '" . $this->prepare($serverSettings['apikey']) . "')";
                    break;
                default:
                    $q = "UPDATE " . SERVERS_TABLE . "
                        SET name = '" . $this->prepare($serverSettings['name']) . "', url = '" . $this->prepare($serverSettings['url']) . "', apikey = '" . $this->prepare($serverSettings['apikey']) . "'
                        WHERE id = " . $serverId;
                    break;
            }
            $this->query($q);
        }

        return $this->getServers();
    }

    public function getServers()
    {
        $serversTable = [];

        $q = "SELECT *
              FROM " . SERVERS_TABLE;
        $r = $this->query($q);
        while ($row = $this->fetchAssoc($r)) {
            $serversTable[$row['id']] = $row;
        }

        $this->serversTable = $serversTable;
        return $serversTable;
    }
}
