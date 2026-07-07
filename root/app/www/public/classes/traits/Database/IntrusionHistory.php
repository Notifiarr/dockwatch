<?php

/*
----------------------------------
 ------  Created: 070626   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait IntrusionHistory
{
    public function addIntrusionHistory($entry)
    {
        $createdAt = strtotime($entry['time'] ?? '') ?: time();

        $sql = "INSERT INTO " . INTRUSION_HISTORY_TABLE . "
                (`type`, `created_at`, `ip`, `method`, `uri`, `referrer`, `user_agent`, `username`, `apikey`, `container`, `token`, `details`)
                VALUES
                ('" . $this->prepare($entry['type'] ?? '') . "', '" . intval($createdAt) . "', '" . $this->prepare($entry['ip'] ?? '') . "', '" . $this->prepare($entry['method'] ?? '') . "', '" . $this->prepare($entry['uri'] ?? '') . "', '" . $this->prepare($entry['referrer'] ?? '') . "', '" . $this->prepare($entry['userAgent'] ?? '') . "', '" . $this->prepare($entry['username'] ?? '') . "', '" . $this->prepare($entry['apikey'] ?? '') . "', '" . $this->prepare($entry['container'] ?? '') . "', '" . $this->prepare($entry['token'] ?? '') . "', '" . $this->prepare($entry['details'] ?? '') . "')";

        $this->mysqli_query($sql);

        return !$this->mysqli_error();
    }

    public function getIntrusionHistory($limit = 100, $offset = 0, $filters = [])
    {
        $history = [];
        $where   = [];

        if (!empty($filters['type'])) {
            $where[] = "`type` = '" . $this->prepare($filters['type']) . "'";
        }

        if (!empty($filters['ip'])) {
            $where[] = "`ip` = '" . $this->prepare($filters['ip']) . "'";
        }

        $sql = "SELECT id, type, created_at, ip, method, uri, referrer, user_agent, username, apikey, container, token, details
                FROM " . INTRUSION_HISTORY_TABLE;

        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY created_at DESC
                LIMIT " . intval($offset) . ", " . intval($limit);

        $res = $this->mysqli_query($sql);

        while ($row = $this->mysqli_fetchAssoc($res)) {
            $history[] = $row;
        }

        return $history;
    }
}
