<?php

/*
----------------------------------
 ------  Created: 121824   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'backup') {
    header('Content-Type: application/json; charset=utf-8');

    if (!isset($database->mysql) || !$database->mysql) {
        echo json_encode(['error' => 'Database not connected']);
        exit;
    }

    $path = $cron->runBackup('manual');
    if ($path) {
        echo json_encode(['success' => true, 'path' => $path]);
    } else {
        echo json_encode(['error' => 'Backup did not finish. Check ' . CRON_BACKUP_LOG]);
    }

    exit;
}

if ($_POST['m'] == 'listBackups') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['html' => backupListHtml()]);
    exit;
}

if ($_POST['m'] == 'deleteBackup') {
    header('Content-Type: application/json; charset=utf-8');

    $path = backupRunPath($_POST['folder'], $_POST['run']);
    if ($path == '') {
        echo json_encode(['error' => 'Backup not found']);
        exit;
    }

    $shell->exec('rm -rf ' . escapeshellarg($path));
    $dateDir = dirname($path);
    if (is_dir($dateDir)) {
        $left = array_diff(scandir($dateDir), ['.', '..']);
        if (!$left) {
            rmdir($dateDir);
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

if ($_POST['m'] == 'restoreBackup') {
    header('Content-Type: application/json; charset=utf-8');

    $path = backupRunPath($_POST['folder'], $_POST['run']);
    if ($path == '' || !$database->mysqli_restore($path)) {
        echo json_encode(['error' => 'Could not restore that backup']);
        exit;
    }

    logger(SYSTEM_LOG, 'Database restored from backup ' . $path);
    echo json_encode(['success' => true]);
    exit;
}

if ($_GET['m'] == 'downloadBackup') {
    $path = backupRunPath($_GET['folder'], $_GET['run']);
    if ($path == '') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Backup not found']);
        exit;
    }

    $zip = sys_get_temp_dir() . '/' . basename(dirname($path)) . '_' . basename($path) . '.zip';
    if (!zipBackupFolder($path, $zip)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Could not zip that backup']);
        exit;
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($zip) . '"');
    header('Content-Length: ' . filesize($zip));
    readfile($zip);
    unlink($zip);
    exit;
}

if ($_POST['m'] == 'init') {
    $defines = get_defined_constants();
    $tables  = [];

    foreach ($defines as $define => $value) {
        if (str_contains($define, '_TABLE') && !str_starts_with($define, 'MYSQLI_')) {
            $tables[] = $value;
        }
    }
    sort($tables);

    foreach ($tables as $table) {
        $rows      = [];
        $escaped   = str_replace('`', '``', $table);
        $schemaSql = "SHOW CREATE TABLE `" . $escaped . "`";
        $schemaRes = $database->mysqli_query($schemaSql);
        $schemaRow = $schemaRes ? $database->mysqli_fetchAssoc($schemaRes) : null;
        $createDdl = $schemaRow['Create Table'] ?? '(schema unavailable)';

        $dataSql = "SELECT * FROM `" . $escaped . "`";
        $dataRes = $database->mysqli_query($dataSql);
        if ($dataRes) {
            while ($row = $database->mysqli_fetchAssoc($dataRes)) {
                $rows[] = $row;
            }
        }
        ?>
        <div class="rounded bg-secondary mb-3">
            <div class="h5 p-2 text-info"><?= $table ?></div>
            <table class="table table-hover">
                <tr>
                    <td style="width:10%;">Schema</td>
                    <td>
                        <pre><?= str_replace('        ', '    ', str_replace('        )', ')', htmlspecialchars($createDdl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))) ?></pre>
                    </td>
                </tr>
                <tr>
                    <td>Data</td>
                    <td>
                        <code><?= htmlspecialchars($dataSql, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code><br>
                        <?php
                        if ($rows) {
                            $fields = [];
                            foreach ($rows[0] as $field => $data) {
                                $fields[] = $field;
                            }

                            ?>
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <?php
                                        foreach ($fields as $field) {
                                            ?>
                                            <td><?= $field ?></td>
                                            <?php
                                        }
                                        ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($rows as $row) {
                                        ?>
                                        <tr><?php
                                        foreach ($fields as $field) {
                                            ?>
                                                <td><?= $row[$field] ?></td><?php
                                        }
                                        ?>
                                        </tr><?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        <?php } else { ?>
                            Table is currently empty
                        <?php } ?>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
}
