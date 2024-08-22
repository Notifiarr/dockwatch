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
    }

    public function connect($dbFile)
    {
        $db = new SQLite3($dbFile, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
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

    public function migrations()
    {
        $database   = $this;
        $db         = $this->db;

        if (filesize(DATABASE_PATH . 'dockwatch.db') == 0) { //-- INITIAL SETUP
            logger(SYSTEM_LOG, 'Creating database and applying migration 001_initial_setup');
            logger(MIGRATION_LOG, 'migration 001 ->');
            require MIGRATIONS_PATH . '001_initial_setup.php';
            logger(MIGRATION_LOG, 'migration 001 <-');
        } else { //-- GET CURRENT MIGRATION & CHECK FOR NEEDED MIGRATIONS
            $dir = opendir(MIGRATIONS_PATH);
            while ($migration = readdir($dir)) {
                if (substr($migration, 0, 3) > $this->getSetting('migration') && str_contains($migration, '.php')) {
                    logger(SYSTEM_LOG, 'Applying migration ' . $migration);
                    logger(MIGRATION_LOG, 'migration ' . substr($migration, 0, 3) . ' ->');
                    require MIGRATIONS_PATH . $migration;
                    logger(MIGRATION_LOG, 'migration ' . substr($migration, 0, 3) . ' <-');
                }
            }
            closedir($dir);
        }
    }
}
