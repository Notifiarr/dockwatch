<?php

/*
----------------------------------
 ------  Created: 102626   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Dispatcher
{
    protected $dispatchOpened = false;

    public function schedules()
    {
        //-- MINUTES IS THE DISPATCHER POLL. STATE, PRUNE, AND SECURITY STILL APPLY THEIR OWN SETTINGS IN THE SCRIPT
        return [
            'sse'         => ['minutes' => 1, 'label' => '1m'],
            'stats'       => ['minutes' => 1, 'label' => '1m'],
            'commands'    => ['minutes' => 1, 'label' => '1m'],
            'state'       => ['minutes' => 1, 'label' => '1m'],
            'pulls'       => ['minutes' => 5, 'label' => '5m'],
            'housekeeper' => ['minutes' => 10, 'label' => '10m'],
            'health'      => ['minutes' => 15, 'label' => '15m'],
            'prune'       => ['minutes' => 60, 'label' => '24h'],
            'security'    => ['minutes' => 60, 'label' => '24h'],
        ];
    }

    public function jobIsDue($job, $minute = null)
    {
        $schedule = $this->schedules()[$job];
        if (!$schedule) {
            return false;
        }

        if (!$minute) {
            $minute = date('i');
        }
        $every  = $schedule['minutes'];
        if ($every <= 1) {
            return true;
        }
        if ($every >= 60) {
            return $minute == 0;
        }

        return ($minute % $every) == 0;
    }

    public function taskLabel($job, $settings = [])
    {
        if ($job == 'state') {
            return $settings['stateCronTime'] . 'm';
        }
        if ($job == 'backup') {
            return backupTimeLabel($settings['backupTime']);
        }

        return $this->schedules()[$job]['label'];
    }

    public function dispatch()
    {
        $lock = $this->setLockFile('dispatcher');
        if (!$lock) {
            return;
        }

        try {
            $this->dispatchJobs();
            $this->dispatchBackup();
        } finally {
            $this->removeLockFile($lock);
            $this->dispatchLogClose();
        }
    }

    public function dispatchJobs()
    {
        foreach ($this->schedules() as $job => $schedule) {
            if (!$this->jobIsDue($job)) {
                continue;
            }

            $result = $this->hasLockFile($job) ? 'running' : 'started';
            $this->dispatchLog($job . '=' . $result);
            if ($result == 'started') {
                $this->spawnScript($job . '.php', '', CRON_DISPATCHER_LOG);
            }
        }
    }

    public function dispatchLog($msg)
    {
        if (!$this->dispatchOpened) {
            logger(CRON_DISPATCHER_LOG, 'dispatcher ->');
            $this->dispatchOpened = true;
        }
        logger(CRON_DISPATCHER_LOG, $msg);
    }

    public function dispatchLogClose()
    {
        if (!$this->dispatchOpened) {
            return;
        }

        logger(CRON_DISPATCHER_LOG, 'dispatcher <-');
        $this->dispatchOpened = false;
    }

    public function dispatchBackup()
    {
        if (date('i') % 10 != 0) {
            return 'wait';
        }

        $time = normalizeBackupTime($this->database->getSetting('backupTime'));
        if (date('H:i') != $time) {
            $result = 'skip';
        } else if ($this->hasLockFile('backup')) {
            $result = 'running';
        } else if ($this->automaticBackupExists()) {
            $result = 'exists';
        } else {
            $result = 'automatic';
        }

        $this->dispatchLog('backup=' . $result);
        if ($result == 'automatic') {
            $this->spawnScript('backup.php', 'automatic', CRON_DISPATCHER_LOG);
        }

        return $result;
    }

    public function runBackup($type = '')
    {
        logger(CRON_BACKUP_LOG, 'backup ->');
        $lock = false;

        try {
            set_time_limit(0);

            $lock = $this->setLockFile('backup');
            if (!$lock) {
                logger(CRON_BACKUP_LOG, 'already running');
                return false;
            }

            $time = normalizeBackupTime($this->database->getSetting('backupTime'));
            if ($type != 'manual') {
                $type = 'automatic';
            }
            logger(CRON_BACKUP_LOG, 'type=' . $type);

            if ($type != 'manual' && date('H:i') != $time) {
                logger(CRON_BACKUP_LOG, 'skip');
                return false;
            }
            if ($type != 'manual' && $this->automaticBackupExists()) {
                logger(CRON_BACKUP_LOG, 'already exists');
                return false;
            }

            if (!is_dir(BACKUP_PATH) && !mkdir(BACKUP_PATH, 0755, true)) {
                logger(CRON_BACKUP_LOG, 'path failed ' . BACKUP_PATH, 'error');
                $this->notifyBackup($type, false, '', time());
                return false;
            }

            $started = time();
            $path    = $this->database->mysqli_backup($type);
            $success = !empty($path);
            if ($success) {
                backupConfigFiles($path);
            }
            logger(CRON_BACKUP_LOG, 'result=' . ($success ? 'success' : 'failed') . ($success ? ' path=' . $path : ''));
            $this->notifyBackup($type, $success, $success ? $path : '', $started);

            return $success ? $path : false;
        } finally {
            $this->removeLockFile($lock);
            logger(CRON_BACKUP_LOG, 'backup <-');
        }
    }

    public function notifyBackup($type, $success, $path = '', $started = 0)
    {
        global $notifications;

        if (!$notifications) {
            return;
        }

        $size = ($success && $path) ? backupPathSize($path) : 0;
        $payload = [
            'event'   => 'backup',
            'type'    => ($type == 'manual') ? 'manual' : 'automatic',
            'status'  => $success ? 'success' : 'failed',
            'runtime' => backupRuntime($started),
            'size'    => $size ? byteConversion($size) : '',
        ];

        $notifications->notify(0, 'backup', $payload);
    }

    public function spawnScript($script, $arg = '', $log = '')
    {
        global $shell;

        $log    = $log ?: CRON_DISPATCHER_LOG;
        $script = basename($script);
        $file   = str_replace('\\', '/', ABSOLUTE_PATH) . 'crons/' . $script;
        if (!str_ends_with($script, '.php') || !is_file($file)) {
            $this->cronLine($log, $script . ' not found');
            return;
        }

        $cmd = '/usr/bin/php ' . $file . ($arg != '' ? ' ' . $arg : '');
        $this->cronLine($log, 'spawn=' . $script . ($arg != '' ? ' ' . $arg : ''));
        $pid = $shell->background($cmd);
        $this->cronLine($log, 'pid=' . ($pid != '' ? $pid : '0'));
    }

    public function cronLine($log, $msg)
    {
        if ($log == CRON_DISPATCHER_LOG) {
            $this->dispatchLog($msg);
            return;
        }

        logger($log, $msg);
    }

    public function automaticBackupExists()
    {
        $dateDir = BACKUP_PATH . date('Ymd');
        if (!is_dir($dateDir)) {
            return false;
        }

        $dir = opendir($dateDir);
        if (!$dir) {
            return false;
        }

        while ($run = readdir($dir)) {
            if (str_ends_with($run, '_automatic')) {
                closedir($dir);
                return true;
            }
        }
        closedir($dir);

        return false;
    }

    public function getLockFile($name)
    {
        return CRON_LOGS_PATH . $name . '.lock';
    }

    public function setLockFile($name)
    {
        if (!is_dir(CRON_LOGS_PATH) && !mkdir(CRON_LOGS_PATH, 0755, true)) {
            return false;
        }

        $fp = fopen($this->getLockFile($name), 'c');
        if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
            if ($fp) {
                fclose($fp);
            }
            return false;
        }

        return $fp;
    }

    public function removeLockFile($fp)
    {
        if (!$fp) {
            return;
        }

        flock($fp, LOCK_UN);
        fclose($fp);
    }

    public function hasLockFile($name)
    {
        if (!is_dir(CRON_LOGS_PATH)) {
            return false;
        }

        $fp = fopen($this->getLockFile($name), 'c');
        if (!$fp) {
            return false;
        }

        $locked = !flock($fp, LOCK_EX | LOCK_NB);
        if (!$locked) {
            flock($fp, LOCK_UN);
        }
        fclose($fp);

        return $locked;
    }
}
