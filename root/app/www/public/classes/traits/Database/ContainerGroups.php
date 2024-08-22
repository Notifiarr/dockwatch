<?php

/*
----------------------------------
 ------  Created: 082424   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait ContainerGroups
{
    public function getContainerGroups()
    {
        if ($this->containerGroupsTable) {
            return $this->containerGroupsTable;
        }

        $q = "SELECT *
              FROM " . CONTAINER_GROUPS_TABLE;
        $r = $this->query($q);
        while ($row = $this->fetchAssoc($r)) {
            $containerGroups[] = $row;
        }

        $this->containerGroupsTable = $containerGroups;
        return $containerGroups ?? [];
    }

    public function getContainerGroupFromHash($hash, $groups)
    {
        if (!$groups) {
            $groups = $this->getContainerGroups();
        }

        foreach ($groups as $group) {
            if ($group['hash'] == $hash) {
                return $group;
            }
        }

        return [];
    }

    public function updateContainerGroup($groupId, $fields = [])
    {
        $updateList = [];
        foreach ($fields as $field => $val) {
            $updateList[] = $field . ' = "' . $val . '"';
        }

        $q = "UPDATE " . CONTAINER_GROUPS_TABLE . "
              SET " . implode(', ', $updateList) . "
              WHERE id = '" . $groupId . "'";
        $this->query($q);

        $this->containerGroupsTable = '';
    }

    public function addContainerGroup($groupName)
    {
        $q = "INSERT INTO " . CONTAINER_GROUPS_TABLE . "
              (`hash`, `name`) 
              VALUES 
              ('" . md5($groupName) . "', '" . $this->prepare($groupName) . "')";
        $this->query($q);

        $this->containerGroupsTable = '';
        return $this->insertId();
    }

    public function deleteContainerGroup($groupId)
    {
        $q = "DELETE FROM " . CONTAINER_GROUPS_TABLE . "
              WHERE id = " . $groupId;
        $this->query($q);

        $this->containerGroupsTable = '';
        $this->deleteGroupLinks($groupId);
    }
}
