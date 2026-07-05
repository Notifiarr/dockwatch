<?php

/*
----------------------------------
 ------  Created: 070426   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Users
{
    public function getUsers()
    {
        $usersTable = [];

        $sql = "SELECT id, username, active, created_at
                FROM " . USERS_TABLE . "
                ORDER BY username ASC";
        $res = $this->mysqli_query($sql);

        while ($row = $this->mysqli_fetchAssoc($res)) {
            $usersTable[$row['id']] = $row;
        }

        return $usersTable;
    }

    public function getActiveUserCount()
    {
        $sql = "SELECT COUNT(*) AS count
                FROM " . USERS_TABLE . "
                WHERE active = 1";
        $res = $this->mysqli_query($sql);

        if (!$res) {
            return 0;
        }

        $row = $this->mysqli_fetchAssoc($res);

        return intval($row['count'] ?? 0);
    }

    public function getUserIdByUsername($username)
    {
        $sql = "SELECT id
                FROM " . USERS_TABLE . "
                WHERE LOWER(username) = LOWER('" . $this->prepare($username) . "')
                LIMIT 1";
        $res = $this->mysqli_query($sql);
        $row = $this->mysqli_fetchAssoc($res);

        return $row['id'] ?? false;
    }

    public function authenticateUser($username, $password)
    {
        $sql = "SELECT id, password
                FROM " . USERS_TABLE . "
                WHERE LOWER(username) = LOWER('" . $this->prepare($username) . "')
                AND active = 1
                LIMIT 1";
        $res = $this->mysqli_query($sql);
        $row = $this->mysqli_fetchAssoc($res);

        if (!$row || !password_verify($password, $row['password'])) {
            return false;
        }

        return intval($row['id']);
    }

    public function setUsers($userList = [])
    {
        if (!$userList) {
            return $this->getUsers();
        }

        foreach ($userList as $userId => $userSettings) {
            switch (true) {
                case !empty($userSettings['remove']):
                    $sql = "DELETE FROM " . USERS_TABLE . "
                            WHERE id = " . intval($userId);
                    break;
                case !$userId:
                    if (empty($userSettings['username']) || empty($userSettings['password'])) {
                        continue 2;
                    }

                    $sql = "INSERT INTO " . USERS_TABLE . "
                            (`username`, `password`, `active`, `created_at`)
                            VALUES
                            ('" . $this->prepare($userSettings['username']) . "', '" . $this->prepare(password_hash($userSettings['password'], PASSWORD_DEFAULT)) . "', '" . intval(!empty($userSettings['active'])) . "', '" . time() . "')";
                    break;
                default:
                    $fields = [];

                    if (!empty($userSettings['username'])) {
                        $fields[] = "username = '" . $this->prepare($userSettings['username']) . "'";
                    }

                    if (!empty($userSettings['password'])) {
                        $fields[] = "password = '" . $this->prepare(password_hash($userSettings['password'], PASSWORD_DEFAULT)) . "'";
                    }

                    if (array_key_exists('active', $userSettings)) {
                        $fields[] = "active = " . intval(!empty($userSettings['active']));
                    }

                    if (!$fields) {
                        continue 2;
                    }

                    $sql = "UPDATE " . USERS_TABLE . "
                            SET " . implode(', ', $fields) . "
                            WHERE id = " . intval($userId);
                    break;
            }

            $this->mysqli_query($sql);
        }

        return $this->getUsers();
    }
}
