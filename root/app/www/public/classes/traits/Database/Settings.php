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
        $q = "SELECT value
              FROM " . SETTINGS_TABLE . "
              WHERE name = '" . $field . "'";
        $r = $this->query($q);
        $row = $this->fetchAssoc($r);

        return $row['value'];
    }

    public function setSetting($field, $value)
    {
        $q = "UPDATE " . SETTINGS_TABLE . "
              SET value = '" . $this->prepare($value) . "'
              WHERE name = '" . $field . "'";
        $this->query($q);

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
                $q = "UPDATE " . SETTINGS_TABLE . "
                      SET value = '" . $this->prepare($value) . "'
                      WHERE name = '" . $field . "'";
                $this->query($q);
            }
        }

        return $this->getSettings();
    }

    public function getSettings()
    {
        $settingsTable = [];

        $q = "SELECT *
              FROM " . SETTINGS_TABLE ;
        $r = $this->query($q);
        while ($row = $this->fetchAssoc($r)) {
            $settingsTable[$row['name']] = $row['value'];
        }

        $this->settingsTable = $settingsTable;
        return $settingsTable;
    }
}
