<?php

/*
----------------------------------
 ------  Created: 090124   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait ContainerGroupLink
{
    public function getContainerGroupLinks()
    {
        if ($this->containerGroupLinksTable) {
            return $this->containerGroupLinksTable;
        }

        $q = "SELECT *
              FROM " . CONTAINER_GROUPS_LINK_TABLE;
        $r = $this->query($q);
        while ($row = $this->fetchAssoc($r)) {
            $containerLinks[] = $row;
        }

        $this->containerGroupLinksTable = $containerLinks;
        return $containerLinks ?? [];
    }

    public function getGroupLinkContainersFromGroupId($groupLinks, $containers, $groupId)
    {
        $groupContainers = [];
        foreach ($groupLinks as $groupLink) {
            if ($groupLink['group_id'] == $groupId) {
                $groupContainers[] = $containers[$groupLink['container_id']];
            }
        }

        return $groupContainers;
    }

    public function addContainerGroupLink($groupId, $containerId)
    {
        $q = "INSERT INTO " . CONTAINER_GROUPS_LINK_TABLE . "
              (`group_id`, `container_id`) 
              VALUES 
              ('" . intval($groupId) . "', '" . intval($containerId) . "')";
        $this->query($q);

        $this->containerGroupLinksTable = '';
    }

    public function removeContainerGroupLink($groupId, $containerId)
    {
        $q = "DELETE FROM " . CONTAINER_GROUPS_LINK_TABLE . "
              WHERE group_id = '" . intval($groupId) . "' 
              AND container_id = '" . intval($containerId) . "'";
        $this->query($q);

        $this->containerGroupLinksTable = '';
    }

    public function deleteGroupLinks($groupId)
    {
        $q = "DELETE FROM " . CONTAINER_GROUPS_LINK_TABLE . "
              WHERE group_id = " . $groupId;
        $this->query($q);

        $this->containerGroupLinksTable = '';
    }
}
