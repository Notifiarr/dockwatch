<?php

/*
----------------------------------
 ------  Created: 082124   ------
 ------  Austin Best	   ------
----------------------------------
*/

//-- BRING IN THE EXTRAS
loadClassExtras('Database');

class Database
{
    use ContainerGroupLink;
    use ContainerGroups;
    use ContainerSettings;
    use NotificationLink;
    use NotificationPlatform;
    use NotificationTrigger;
    use Servers;
    use Settings;

    public $db;
    public $settingsTable;
    public $serversTable;
    public $notificationPlatformTable;
    public $notificationTriggersTable;
    public $notificationLinkTable;
    public $containersTable;
    public $containerGroupsTable;
    public $containerGroupLinksTable;

    public function __construct()
    {
        logger(SYSTEM_LOG, 'Connect to \'' . DATABASE_PATH . DATABASE_NAME . '\'');
        $this->connect(DATABASE_PATH . DATABASE_NAME);

        if (IS_STARTUP && filesize(DATABASE_PATH . 'dockwatch.db') == 0) {
            define('INITIAL_API_KEY', generateApikey());
            $this->migrations();

            echo "───────────────────────────────────────" . "\n";
            echo 'Generated API key: ' . INITIAL_API_KEY . "\n";
            echo "───────────────────────────────────────" . "\n";
        }
    }

    public function connect($dbFile)
    {
        $db = new SQLite3($dbFile, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $db->busyTimeout(5000);
        $db->exec('PRAGMA journal_mode = wal;');

        $this->db = $db;
    }

    public function query($query)
    {
        if (str_equals_any(substr($query, 0, 5), ['UPDATE', 'INSERT', 'DELETE'])) {
            $transaction[] = 'BEGIN TRANSACTION;';
            $transaction[] = $query . (substr($query, -1) != ';' ? ';' : '');
            $transaction[] = 'COMMIT;';

            $transaction = implode("\n", $transaction);
        } else {
            $transaction = $query;
        }

        return $this->db->query($transaction);
    }

    public function fetchAssoc($res)
    {
        return !$res ? [] : $res->fetchArray(SQLITE3_ASSOC);
    }

    public function affectedRows($res)
    {
        return !$res ? 0 : $res->changes(SQLITE3_ASSOC);
    }

    public function insertId()
    {
        return $this->db->lastInsertRowID();
    }

    public function error()
    {
        return $this->db->lastErrorMsg();
    }

    public function prepare($in)
    {
        $out = addslashes(stripslashes($in));
        return $out;
    }

    public function backup()
    {
        $this->db->query("VACUUM INTO '" . BACKUP_PATH . date('Ymd') . '/' . DATABASE_NAME . "'");
    }

    public function getNewestMigration()
    {
        $newestMigration = '001';
        $dir             = opendir(MIGRATIONS_PATH);
        while ($migration = readdir($dir)) {
            if (intval(substr($migration, 0, 3)) > intval($newestMigration) && str_contains($migration, '.php')) {
                $newestMigration = substr($migration, 0, 3);
            }
        }
        closedir($dir);

        return $newestMigration;
    }

    public function migrations()
    {
        $database = $this;
        $db       = $this->db;

        //-- DONT RUN MIGRATIONS IF IT IS ALREADY RUNNING
        if (file_exists(MIGRATION_FILE)) {
            return;
        }

        setFile(MIGRATION_FILE, ['started' => date('c')]);
        $currentMigration = intval($this->getSetting('migration'));

        if (filesize(DATABASE_PATH . 'dockwatch.db') == 0) { //-- INITIAL SETUP
            $q = []; //-- RESET THE QUERY ARRAY FOR THE INITIAL SETUP
            logger(SYSTEM_LOG, 'Creating database and applying migration 001_initial_setup');
            logger(MIGRATION_LOG, '====================|');
            logger(MIGRATION_LOG, '====================| migrations');
            logger(MIGRATION_LOG, '====================|');
            logger(MIGRATION_LOG, 'migration 001 ->');
            require MIGRATIONS_PATH . '001_initial_setup.php';
            logger(MIGRATION_LOG, 'migration 001 <-');

            $neededMigrations = [];
            $dir              = opendir(MIGRATIONS_PATH);
            while ($migration = readdir($dir)) {
                $migrationFileNumber = intval(substr($migration, 0, 3));

                if ($migrationFileNumber > $currentMigration && str_contains($migration, '.php')) {
                    $neededMigrations[$migrationFileNumber] = $migration;
                }
            }
            closedir($dir);

            if ($neededMigrations) {
                ksort($neededMigrations);

                foreach ($neededMigrations as $migrationNumber => $neededMigration) {
                    $q = []; //-- RESET THE QUERY ARRAY FOR EACH MIGRATION
                    logger(MIGRATION_LOG, 'migration ' . $migrationNumber . ' ->');
                    require MIGRATIONS_PATH . $neededMigration;
                    logger(MIGRATION_LOG, 'migration ' . $migrationNumber . ' <-');
                }
            }
        } else { //-- CHECK FOR NEEDED MIGRATIONS
            $neededMigrations = [];
            $dir              = opendir(MIGRATIONS_PATH);
            while ($migration = readdir($dir)) {
                $migrationFileNumber = intval(substr($migration, 0, 3));

                if ($migrationFileNumber > $currentMigration && str_contains($migration, '.php')) {
                    $neededMigrations[$migrationFileNumber] = $migration;
                }
            }
            closedir($dir);

            if ($neededMigrations) {
                ksort($neededMigrations);
                $neededMigrationsNumbers = implode(', ', array_keys($neededMigrations));

                logger(SYSTEM_LOG, 'Applying migrations: ' . $neededMigrationsNumbers);
                logger(MIGRATION_LOG, '====================|');
                logger(MIGRATION_LOG, '====================| migrations');
                logger(MIGRATION_LOG, '====================|');
                logger(MIGRATION_LOG, 'Current migration: ' . $currentMigration);
                logger(MIGRATION_LOG, 'Needed migrations: ' . $neededMigrationsNumbers);

                foreach ($neededMigrations as $migrationNumber => $neededMigration) {
                    $q = []; //-- RESET THE QUERY ARRAY FOR EACH MIGRATION
                    logger(MIGRATION_LOG, 'migration ' . $migrationNumber . ' ->');
                    require MIGRATIONS_PATH . $neededMigration;
                    logger(MIGRATION_LOG, 'migration ' . $migrationNumber . ' <-');
                }
            }
        }

        deleteFile(MIGRATION_FILE);
    }
}
