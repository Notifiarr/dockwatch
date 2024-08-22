<?php

/*
----------------------------------
 ------  Created: 082424   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait ContainerSettings
{
    public function getContainers()
    {
        if ($this->containersTable) {
            return $this->containersTable;
        }

        $q = "SELECT *
              FROM " . CONTAINER_SETTINGS_TABLE;
        $r = $this->query($q);
        while ($row = $this->fetchAssoc($r)) {
            $containers[$row['id']] = $row;
        }

        $this->containersTable = $containers;
        return $containers ?? [];
    }

    public function getContainerFromHash($hash, $containers = [])
    {
        if (!$containers) {
            $containers = $this->getContainers();
        }

        foreach ($containers as $container) {
            if ($container['hash'] == $hash) {
                return $container;
            }
        }

        return [];
    }

    public function getContainer($hash)
    {
        $q = "SELECT *
              FROM " . CONTAINER_SETTINGS_TABLE . "
              WHERE hash = '" . $hash . "'";
        $r = $this->query($q);

        return $this->fetchAssoc($r);
    }

    public function addContainer($fields = [])
    {
        $settingsTable = $this->getSettings();

        if (!array_key_exists('updates', $fields)) {
            $fields['updates'] = $settingsTable['updates'] ?: 3;
        }

        if (!array_key_exists('frequency', $fields)) {
            $fields['frequency'] = $settingsTable['updatesFrequency'] ?: DEFAULT_CRON;
        }

        if (!array_key_exists('restartUnhealthy', $fields)) {
            $fields['restartUnhealthy'] = 0;
        }

        if (!array_key_exists('disableNotifications', $fields)) {
            $fields['disableNotifications'] = 0;
        }

        if (!array_key_exists('shutdownDelay', $fields)) {
            $fields['shutdownDelay'] = 0;
        }

        if (!array_key_exists('shutdownDelaySeconds', $fields)) {
            $fields['shutdownDelaySeconds'] = 0;
        }

        $fieldList = $valList = [];
        foreach ($fields as $field => $val) {
            $fieldList[]    = '`' . $field . '`';
            $valList[]      = '"'. $val .'"';
        }

        $q = "INSERT INTO " . CONTAINER_SETTINGS_TABLE . "
              (". implode(', ', $fieldList) .") 
              VALUES 
              (". implode(', ', $valList) .")";
        $this->query($q);

        $this->containersTable = '';
    }

    public function updateContainer($hash, $fields = [])
    {
        $updateList = [];
        foreach ($fields as $field => $val) {
            $updateList[] = $field . ' = "' . $val . '"';
        }

        $q = "UPDATE " . CONTAINER_SETTINGS_TABLE . "
              SET " . implode(', ', $updateList) . "
              WHERE hash = '" . $hash . "'";
        $this->query($q);

        $this->containersTable = '';
        return $this->error();
    }
}
