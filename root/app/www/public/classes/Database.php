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
    public $migrationStart            = '023';
    public $mysql;
    public $shell;
    public function __construct()
    {
        global $shell;

        if (IS_MAINTENANCE) {
            return;
        }

        $this->shell = $shell ?? new Shell();
        // TODO: WAIT A FEW MONTHS FOR EXISTING USERS TO MIGRATE, REMOVE THE VARIABLE PASSED INTO THE connect() FUNCTION
        $this->connect(DATABASE_PATH . DATABASE_NAME);
    }

    public function connect($dbFile)
    {
        logger(SYSTEM_LOG, 'MYSQL: Connecting to \'' . DB_NAME . ' -> ' . DB_USER . '@' . DB_HOST . '\'...', 'shell');

        try {
            $this->mysql = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        } catch (Exception $e) {
            logger(SYSTEM_LOG, 'MYSQL: Failed to connect to \'' . DB_NAME . ' -> ' . DB_USER . '@' . DB_HOST . '\': ' . $e, 'shell');

            //-- IGNORE THIS, MIGRATION WILL CREATE THE DATABASE
            if (str_contains($e, 'Unknown database')) {
                logger(SYSTEM_LOG, 'MYSQL: Connecting to \'' . DB_USER . '@' . DB_HOST . '\'...', 'shell');

                //-- CONNECT TO THE MYSQL SERVER WITH NO DATABASE
                try {
                    $this->mysql = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);
                    define('MYSQL_SETUP', true);
                } catch (Exception $e) {
                    logger(SYSTEM_LOG, 'MYSQL: Failed to connect to \'' . DB_USER . '@' . DB_HOST . '\': ' . $e, 'shell');
                }
            } elseif (str_contains_any($e, ['No such file or directory', 'Connection refused'])) {
                logger(SYSTEM_LOG, 'MYSQL: Connecting to \'' . DB_USER . '@127.0.0.1\'...', 'shell');

                //-- CONNECT TO THE MYSQL SERVER WITH NO DATABASE
                try {
                    $this->mysql = mysqli_connect('127.0.0.1', DB_USER, DB_PASSWORD);
                    define('MYSQL_SETUP', true);
                } catch (Exception $e) {
                    logger(SYSTEM_LOG, 'MYSQL: Failed to connect to \'' . DB_USER . '@127.0.0.1: ' . $e, 'shell');
                }
            } else {
                logger(SYSTEM_LOG, 'All connection attempts failed', 'shell');
            }
        }

        if (!defined('MYSQL_SETUP')) {
            define('MYSQL_SETUP', false);
        }

        // TODO: WAIT A FEW MONTHS FOR EXISTING USERS TO MIGRATE, REMOVE ALL OF THIS RELATED TO SQLITE3
        if (MYSQL_SETUP && file_exists($dbFile)) {
            logger(SYSTEM_LOG, 'SQLITE: Connecting to \'' . DATABASE_PATH . DATABASE_NAME . '\'...', 'shell');

            $db = new SQLite3($dbFile, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
            $db->busyTimeout(5000);
            $db->exec('PRAGMA journal_mode = wal;');

            $this->db = $db;

            define('SQLITE_MIGRATION', true);
        } else {
            define('SQLITE_MIGRATION', false);
        }

        if (IS_STARTUP) {
            // TODO: WAIT A FEW MONTHS FOR EXISTING USERS TO MIGRATE, REMOVE "SQLITE_MIGRATION" REFERENCE
            if (MYSQL_SETUP && !SQLITE_MIGRATION) {
                define('INITIAL_API_KEY', generateApikey());

                echo "───────────────────────────────────────\n";
                echo 'Generated API key: ' . INITIAL_API_KEY . "\n";
                echo "───────────────────────────────────────\n";
            }

            $this->migrations();
        }
    }

    // TODO: WAIT A FEW MONTHS FOR EXISTING USERS TO MIGRATE, CONSOLIDATE mysqli_* FUNCTIONS INTO THE THE NON mysqli_* FUNCTIONS
    public function mysqli_query($query)
    {
        return mysqli_query($this->mysql, $query);
    }

    public function mysqli_error()
    {
        return mysqli_error($this->mysql);
    }

    public function mysqli_fetchAssoc($res)
    {
        return mysqli_fetch_assoc($res);
    }

    public function mysqli_affectedRows()
    {
        return mysqli_affected_rows($this->mysql);
    }

    public function mysqli_insertId()
    {
        return mysqli_insert_id($this->mysql);
    }

    public function mysqli_backup()
    {
        $dir = BACKUP_PATH . date('Ymd');
        if (!is_dir($dir)) {
            createDirectoryTree($dir);
        }

        $exec = 'mariadb-dump --single-transaction=true --skip-lock-tables --quick --user=' . DB_USER . ' --password=' . DB_PASSWORD . ' --host=' . DB_HOST . ' ' . DB_NAME . ' 2>/dev/null > ' . $dir . '/' . DB_NAME . '.sql';
        $this->shell->exec($exec);
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

    public function backup()
    {
        $this->db->query("VACUUM INTO '" . BACKUP_PATH . date('Ymd') . '/' . DATABASE_NAME . "'");
    }

    public function prepare($in)
    {
        $out = addslashes(stripslashes($in));
        return $out;
    }

    public function getNewestMigration()
    {
        $newestMigration = $this->migrationStart; //--- AS OF THE MYSQL MIGRATION, NEVER USE SQLITE3 MIGRATIONS 001-022
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
        if (!IS_STARTUP) {
            return;
        }

        setFile(MIGRATION_FILE, ['started' => date('c')]);
        $database = $this; //-- USED IN THE MIGRATIONS FILES
        $db       = $this->db; //--- USED FOR SQLITE DATABASE
        $q        = []; //-- RESET THE QUERY ARRAY FOR THE INITIAL SETUP

        if (MYSQL_SETUP) {
            logger(SYSTEM_LOG, 'Creating database and applying migration ' . $this->migrationStart . '_mysql_conversion', 'shell');
            logger(SYSTEM_LOG, 'Review the migration log for specific migration query details: ' . MIGRATION_LOG, 'shell');
            logger(MIGRATION_LOG, 'migration ' . $this->migrationStart . ' ->', 'shell');
            require MIGRATIONS_PATH . $this->migrationStart . '_mysql_conversion.php';
            logger(MIGRATION_LOG, 'migration ' . $this->migrationStart . ' <-', 'shell');

            $currentMigration = $this->migrationStart;
        } else {
            $sql = "SELECT value 
                    FROM " . SETTINGS_TABLE . " 
                    WHERE name = 'migration';";
            $res = $this->mysqli_query($sql);
            $row = $this->mysqli_fetchAssoc($res);

            $currentMigration = $row['value'];
        }

        $currentMigration = intval($currentMigration);
        $neededMigrations = [];
        $dir              = opendir(MIGRATIONS_PATH);
        while ($migration = readdir($dir)) {
            $migrationFileNumber = intval(substr($migration, 0, 3));

            //--- AS OF THE MYSQL MIGRATION, NEVER USE SQLITE3 MIGRATIONS 001-022
            if ($migrationFileNumber < 23) {
                continue;
            }

            if ($migrationFileNumber > $currentMigration && str_contains($migration, '.php')) {
                $neededMigrations[$migrationFileNumber] = $migration;
            }
        }
        closedir($dir);

        if (!empty($neededMigrations)) {
            ksort($neededMigrations);
            $neededMigrationsNumbers = implode(', ', array_keys($neededMigrations));

            logger(MIGRATION_LOG, 'Current migration: ' . $currentMigration, 'shell');
            logger(MIGRATION_LOG, 'Needed migrations: ' . $neededMigrationsNumbers, 'shell');
            logger(SYSTEM_LOG, 'Applying migrations: ' . $neededMigrationsNumbers, 'shell');
            logger(SYSTEM_LOG, 'Review the migration log for specific migration query details: ' . MIGRATION_LOG, 'shell');

            foreach ($neededMigrations as $migrationNumber => $neededMigration) {
                $q = []; //-- RESET THE QUERY ARRAY FOR EACH MIGRATION
                logger(MIGRATION_LOG, 'migration ' . $migrationNumber . ' ->', 'shell');
                require MIGRATIONS_PATH . $neededMigration;
                logger(MIGRATION_LOG, 'migration ' . $migrationNumber . ' <-', 'shell');
            }
        } else {
            if ($currentMigration > 23) {
                logger(SYSTEM_LOG, 'No migrations needed', 'shell');
            }
        }
        deleteFile(MIGRATION_FILE);
    }
}
