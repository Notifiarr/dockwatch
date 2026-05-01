<?php

/*
----------------------------------
 ------  Created: 082124   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Settings
{
    public function getSetting($field)
    {
        $sql = "SELECT value
                FROM " . SETTINGS_TABLE . "
                WHERE name = '" . $field . "'";
        $res = $this->mysqli_query($sql);
        $row = $this->mysqli_fetchAssoc($res);

        if (file_exists(MIGRATION_FILE) && !$row['value']) {
            logger(MIGRATION_LOG, '<span class="text-success">[Q]</span> ' . preg_replace('!\s+!', ' ', $sql));
            logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . json_encode($row));
            logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $this->mysqli_error(), 'error');
        }

        return $row['value'];
    }

    public function setSetting($field, $value)
    {
        $sql = "UPDATE " . SETTINGS_TABLE . "
                SET value = '" . $this->prepare($value) . "'
                WHERE name = '" . $field . "'";
        $this->mysqli_query($sql);

        return $this->getSettings();
    }

    public function setSettings($newSettings = [], $currentSettings = [])
    {
        if (!$newSettings) {
            return;
        }

        if (!$currentSettings) {
            $currentSettings = $this->getSettings();
        }

        foreach ($newSettings as $field => $value) {
            if ($currentSettings[$field] != $value) {
                $sql = "UPDATE " . SETTINGS_TABLE . "
                        SET value = '" . $this->prepare($value) . "'
                         WHERE name = '" . $field . "'";
                $this->mysqli_query($sql);
            }
        }

        return $this->getSettings();
    }

    public function getSettings()
    {
        $settingsTable = [];

        $sql = "SELECT *
                FROM " . SETTINGS_TABLE;
        $res = $this->mysqli_query($sql);
        while ($row = $this->mysqli_fetchAssoc($res)) {
            $settingsTable[$row['name']] = $row['value'];
        }

        $this->settingsTable = $settingsTable;
        return $settingsTable;
    }
}
