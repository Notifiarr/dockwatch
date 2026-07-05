<?php

/*
----------------------------------
 ------  Created: 070426   ------
 ------  Austin Best	   ------
----------------------------------
*/

$q   = [];
$q[] = 'CREATE TABLE IF NOT EXISTS `' . USERS_TABLE . '` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(150) NOT NULL,
        `password` varchar(255) NOT NULL,
        `active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

if (file_exists(LOGIN_FILE)) {
    $importedUsernames = [];

    foreach (parseLoginFile() as $login) {
        $username    = $login['username'];
        $password    = $login['password'];
        $usernameKey = strtolower($username);

        if (isDefaultLoginCredential($username, $password)) {
            logger(MIGRATION_LOG, '<span class="text-warning">[W]</span> Skipping default credential for user \'' . $username . '\'', 'warn');
            continue;
        }

        if (isset($importedUsernames[$usernameKey])) {
            logger(MIGRATION_LOG, '<span class="text-warning">[W]</span> Skipping duplicate login file user \'' . $username . '\'', 'warn');
            continue;
        }

        $importedUsernames[$usernameKey] = true;

        $q[] = 'INSERT INTO `' . USERS_TABLE . "`
                (`username`, `password`, `active`, `created_at`)
                VALUES
                ('" . $database->prepare($username) . "', '" . $database->prepare(password_hash($password, PASSWORD_DEFAULT)) . "', '1', '" . time() . "')";
    }
}

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '025'
        WHERE name = 'migration'";

$error = false;

foreach ($q as $query) {
    logger(MIGRATION_LOG, '<span class="text-success">[Q]</span> ' . preg_replace('!\s+!', ' ', $query));

    $database->mysqli_query($query);

    if ($migrationError = $database->mysqli_error()) {
        if (str_contains($migrationError, 'Duplicate entry')) {
            logger(MIGRATION_LOG, '<span class="text-warning">[W]</span> ' . $migrationError, 'warn');
        } else {
            logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $migrationError, 'error');
            $error = true;
        }
    } else {
        logger(MIGRATION_LOG, '<span class="text-info">[R]</span> query applied!');
    }
}

if ($error) {
    logger(MIGRATION_LOG, 'A migration error occurred, please check the migration log for details');
}
