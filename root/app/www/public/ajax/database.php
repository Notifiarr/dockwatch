<?php

/*
----------------------------------
 ------  Created: 121824   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if (($_SESSION['activeServerId'] ?? APP_SERVER_ID) != APP_SERVER_ID) {
    echo json_encode(['error' => 'Database tools are not available on remote instances']);
    exit;
}

if ($_POST['m'] == 'backup') {
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
    echo json_encode(['html' => backupListHtml()]);
    exit;
}

if ($_POST['m'] == 'deleteBackup') {
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
        echo json_encode(['error' => 'Backup not found']);
        exit;
    }

    $zip = sys_get_temp_dir() . '/' . basename(dirname($path)) . '_' . basename($path) . '.zip';
    if (!zipBackupFolder($path, $zip)) {
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

if ($_POST['m'] == 'browseDatabase') {
    $browseView = strval($_POST['view'] ?? 'list');
    $result     = ['error' => 'Could not browse the database'];

    switch ($browseView) {
        case 'list':
            $browseSummary = $database->browseTablesSummary();
            ob_start();
            require ABSOLUTE_PATH . 'includes/settings/browseDatabase.php';
            $result = ['html' => ob_get_clean()];
            break;
        case 'table':
            $browse = $database->browseTablePage($_POST['table'] ?? '', $_POST['page'] ?? 1, 25);
            if (!empty($browse['error'])) {
                $result = ['error' => $browse['message'] ?? 'Could not browse that table'];
                break;
            }
            ob_start();
            require ABSOLUTE_PATH . 'includes/settings/browseDatabase.php';
            $result = [
                'html'  => ob_get_clean(),
                'page'  => intval($browse['page'] ?? 1),
                'pages' => intval($browse['pages'] ?? 1),
            ];
            break;
        case 'schema':
            $browse = $database->browseTableSchema($_POST['table'] ?? '');
            if (!empty($browse['error'])) {
                $result = ['error' => $browse['message'] ?? 'Could not load the table schema'];
                break;
            }
            ob_start();
            require ABSOLUTE_PATH . 'includes/settings/browseDatabase.php';
            $result = [
                'html'  => ob_get_clean(),
                'table' => strval($browse['table'] ?? ''),
            ];
            break;
        case 'query':
            $browse = $database->browseRunQuery($_POST['sql'] ?? '', 500);
            if (!empty($browse['error'])) {
                $result = ['error' => $browse['message'] ?? 'Query failed'];
                break;
            }
            ob_start();
            require ABSOLUTE_PATH . 'includes/settings/browseDatabase.php';
            $result = ['html' => ob_get_clean()];
            break;
    }

    echo json_encode($result);
    exit;
}
