<?php

/*
----------------------------------
 ------  Created: 102626   ------
 ------  Austin Best	   ------
----------------------------------
*/

function getBackupList()
{
    $backups = [];
    if (!is_dir(BACKUP_PATH)) {
        return $backups;
    }

    $dates = opendir(BACKUP_PATH);
    if (!$dates) {
        return $backups;
    }

    while ($date = readdir($dates)) {
        if ($date[0] == '.' || !is_dir(BACKUP_PATH . $date)) {
            continue;
        }

        $runs = opendir(BACKUP_PATH . $date);
        if (!$runs) {
            continue;
        }

        while ($run = readdir($runs)) {
            if ($run[0] == '.' || !is_dir(BACKUP_PATH . $date . '/' . $run)) {
                continue;
            }
            if (!preg_match('/^(\d{6})_(manual|automatic)$/', $run, $match)) {
                continue;
            }

            $backups[] = [
                'date'   => $date,
                'time'   => $match[1],
                'method' => $match[2],
                'run'    => $run,
                'size'   => backupPathSize(BACKUP_PATH . $date . '/' . $run),
            ];
        }
        closedir($runs);
    }
    closedir($dates);

    usort($backups, function ($a, $b) {
        return strcmp($b['date'] . $b['time'], $a['date'] . $a['time']);
    });

    return $backups;
}

function backupPathSize($path)
{
    if (is_file($path)) {
        return filesize($path);
    }
    if (!is_dir($path)) {
        return 0;
    }

    $size   = 0;
    $handle = opendir($path);
    if (!$handle) {
        return 0;
    }

    while ($file = readdir($handle)) {
        if ($file[0] == '.') {
            continue;
        }
        $size += backupPathSize($path . '/' . $file);
    }
    closedir($handle);

    return $size;
}

function backupConfigFiles($dir)
{
    global $shell;

    $defines = get_defined_constants();
    foreach ($defines as $define => $defineValue) {
        if (!str_contains($define, '_FILE') || !str_contains_all($defineValue, ['config/', '.json'])) {
            continue;
        }

        $name   = basename($defineValue);
        $source = APP_DATA_PATH . $name;
        if (!is_file($source)) {
            continue;
        }

        if (copy($source, $dir . '/' . $name)) {
            logger(CRON_BACKUP_LOG, 'file=' . $name);
        } else {
            logger(CRON_BACKUP_LOG, 'file=' . $name . ' status=failed', 'error');
        }
    }

    if (!is_dir(COMPOSE_PATH)) {
        return;
    }

    $dest = rtrim($dir, '/') . '/compose';
    if (!is_dir($dest) && !mkdir($dest, 0755, true)) {
        logger(CRON_BACKUP_LOG, 'compose status=failed', 'error');
        return;
    }

    $source = rtrim(COMPOSE_PATH, '/');
    $shell->exec('cp -a ' . escapeshellarg($source . '/.') . ' ' . escapeshellarg($dest));
    logger(CRON_BACKUP_LOG, 'compose=' . $dest);
}

function backupRunPath($folder, $run)
{
    $folder = basename($folder);
    $run    = basename($run);
    if (!preg_match('/^\d{8}$/', $folder) || !preg_match('/^\d{6}_(manual|automatic)$/', $run)) {
        return '';
    }

    $path = BACKUP_PATH . $folder . '/' . $run;
    if (!is_dir($path)) {
        return '';
    }

    return $path;
}

function backupListHtml()
{
    $backups = getBackupList();
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-sm table-no-squish mb-0">
            <thead>
                <tr>
                    <th class="bg-primary ps-3">Date</th>
                    <th class="bg-primary">Time</th>
                    <th class="bg-primary">Method</th>
                    <th class="bg-primary">Size</th>
                    <th class="bg-primary pe-3"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$backups) { ?>
                    <tr class="backup-empty">
                        <td class="bg-secondary ps-3" colspan="5">No backups yet.</td>
                    </tr>
                <?php } ?>
                <?php foreach ($backups as $backup) {
                    $date = date_create_from_format('Ymd', $backup['date']);
                    $time = date_create_from_format('His', $backup['time']);
                    $folder = htmlspecialchars($backup['date'], ENT_QUOTES, 'UTF-8');
                    $run = htmlspecialchars($backup['run'], ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td class="bg-secondary ps-3"><?= htmlspecialchars($date ? $date->format('m/d/Y') : $backup['date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="bg-secondary"><?= htmlspecialchars($time ? $time->format('g:i A') : $backup['time'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="bg-secondary"><?= htmlspecialchars($backup['method'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="bg-secondary"><?= byteConversion($backup['size']) ?></td>
                        <td class="bg-secondary pe-3 text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="downloadBackup('<?= $folder ?>', '<?= $run ?>')">Download</button>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="restoreBackup('<?= $folder ?>', '<?= $run ?>')">Restore</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteBackup('<?= $folder ?>', '<?= $run ?>')">Delete</button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php

    return ob_get_clean();
}

function zipBackupFolder($dir, $zipFile)
{
    $files = backupZipFiles($dir);
    if (!$files) {
        return false;
    }

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) != true) {
            return false;
        }
        foreach ($files as $name => $path) {
            $zip->addFile($path, $name);
        }
        $zip->close();

        return is_file($zipFile) && filesize($zipFile);
    }

    $out = fopen($zipFile, 'wb');
    if (!$out) {
        return false;
    }

    $central = '';
    $offset  = 0;
    $count   = 0;
    foreach ($files as $name => $path) {
        $data = file_get_contents($path);
        if ($data == false) {
            if (filesize($path)) {
                fclose($out);
                return false;
            }
            $data = '';
        }

        $len     = strlen($data);
        $crc     = crc32($data);
        $nameLen = strlen($name);
        fwrite($out, pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $crc, $len, $len, $nameLen, 0));
        fwrite($out, $name);
        fwrite($out, $data);
        $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 0, $crc, $len, $len, $nameLen, 0, 0, 0, 0, 0, $offset);
        $central .= $name;
        $offset  += 30 + $nameLen + $len;
        $count++;
    }
    fwrite($out, $central);
    fwrite($out, pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($central), $offset, 0));
    fclose($out);

    return is_file($zipFile) && filesize($zipFile);
}

function backupZipFiles($dir, $base = '')
{
    $files  = [];
    $handle = opendir($dir);
    if (!$handle) {
        return $files;
    }

    while ($file = readdir($handle)) {
        if ($file[0] == '.') {
            continue;
        }

        $full = $dir . '/' . $file;
        $name = ($base == '' ? $file : $base . '/' . $file);
        if (is_dir($full)) {
            $files = array_merge($files, backupZipFiles($full, $name));
        } else if (is_file($full)) {
            $files[$name] = $full;
        }
    }
    closedir($handle);

    return $files;
}
