<?php

/*
----------------------------------
 ------  Created: 111823   ------
 ------  Austin Best	   ------
----------------------------------
*/

function viewLog($log)
{
    $error = $header = $content = '';
    $path = str_contains_any($log, ['login_failures']) ? APP_DATA_PATH . $log : LOGS_PATH . $log;

    if (file_exists($path)) {
        if (str_contains_any($log, ['login_failures'])) {
            $content = json_encode(json_decode(file_get_contents($path), true), JSON_PRETTY_PRINT);
        } else {
            $log    = file($path);
            $header = 'Lines: ' . count($log);

            foreach ($log as $index => $line) {
                $content .= str_pad(($index + 1), strlen(count($log)), ' ', STR_PAD_RIGHT) . ' | ' . $line;
            }
        }
    } else {
        $error = 'Selected log file not found';
    }

    return json_encode(['header' => $header, 'log' => str_replace('[ERROR]', '<span class="text-danger">[ERROR]</span>', $content), 'error' => $error]);
}

function getLogFiles()
{
    $logFiles = [];

    //-- CHECK FOR LOGIN FAILURE FILES
    $dir = opendir(APP_DATA_PATH);
    while ($file = readdir($dir)) {
        if (str_contains($file, 'login_failures') && !str_ends_with($file, '.gz')) {
            $logFiles['login failures'][] = ['name' => $file, 'size' => filesize(APP_DATA_PATH . $file)];
        }
    }
    closedir($dir);

    $logDir = LOGS_PATH;
    if (is_dir($logDir)) {
        $dir = opendir($logDir);
        while ($group = readdir($dir)) {
            if ($group[0] != '.' && is_dir($logDir . $group)) {
                $groupDir = opendir($logDir . $group . '/');
                while ($log = readdir($groupDir)) {
                    if ($log[0] != '.' && !is_dir($log) && !str_ends_with($log, '.gz') && !str_ends_with($log, '.lock')) {
                        $logFiles[$group][] = ['name' => $log, 'size' => filesize($logDir . $group . '/' . $log)];
                    }
                }
                closedir($groupDir);
            }

            if (isset($logFiles[$group]) && is_array($logFiles[$group])) {
                sort($logFiles[$group]);
            }
        }
        closedir($dir);
    }

    return $logFiles;
}

function renderLogList()
{
    $list     = '';
    $logFiles = getLogFiles();

    foreach ($logFiles as $group => $groupLogs) {
        //-- ESCAPE FOR HTML ATTRIBUTES AND INLINE JS
        $groupHtml  = htmlspecialchars($group);
        $groupJs    = str_replace("'", "\\'", $group);
        $list      .= '<h4 style="display: inline;" class="mt-3 mb-0">' . $groupHtml . '</h4> <i class="far fa-trash-alt" style="display: inline; color: red; cursor: pointer;" title="Delete all ' . $groupHtml . ' log files" onclick="purgeLogs(\'' . $groupJs . '\')"></i><br>';

        if (str_contains_any($group, ['login failures'])) {
            $list   .= '<span class="small-text text-muted">' . APP_DATA_PATH . '</span>';
            $prefix  = '';
        } else {
            $list   .= '<span class="small-text text-muted">' . LOGS_PATH . $groupHtml . '/</span>';
            $prefix  = $group . '/';
        }

        $list .= '<div class="mb-3" style="max-height: 300px; overflow: auto;">';

        if (!$groupLogs) {
            $list .= '</div>';
            continue;
        }

        $rotated = [];
        $regular = [];

        foreach ($groupLogs as $log) {
            if (str_contains($log['name'], '-')) {
                $rotated[] = $log;
            } else {
                $regular[] = $log;
            }
        }

        $renderLogItem = function ($logs) use (&$list, $prefix) {
            foreach ($logs as $log) {
                $name      = $prefix . $log['name'];
                $logHash   = md5($name);
                $nameHtml  = htmlspecialchars($log['name']);
                $nameFull  = htmlspecialchars($name);
                $nameJs    = str_replace("'", "\\'", $name);
                $list     .= '<li>'
                    . '<span id="logList-' . $logHash . '" data-log="' . $nameFull . '" onclick="viewLog(\'' . $nameJs . '\', \'' . $logHash . '\')" style="cursor: pointer;" class="text-secondary">' . $nameHtml . '</span> (' . byteConversion($log['size']) . ') '
                    . '<i class="far fa-trash-alt text-danger" style="display: inline; cursor: pointer;" title="Delete ' . $nameHtml . '" onclick="deleteLog(\'' . $nameJs . '\')"></i> '
                    . '<i class="fas fa-file-download text-primary" style="display: inline; cursor: pointer;" title="Download ' . $nameHtml . '" onclick="downloadLog(\'' . $nameJs . '\')"></i>'
                    . '</li>';
            }
        };

        $renderLogItem($regular);

        if ($rotated) {
            $list .= '<hr>';
            $renderLogItem($rotated);
        }

        $list .= '</div>';
    }

    return $list;
}

function getLogView($name)
{
    $error  = '';
    $result = [];

    $apiRequest = apiRequest('server/log', ['name' => $name]);

    if ($apiRequest['code'] == 200) {
        $result = json_decode((string) $apiRequest['result'], true) ?: [];
    } else {
        $error = 'Failed to get log from server ' . ACTIVE_SERVER_NAME;
    }

    $log = str_contains($name, 'migrations') ? ($result['log'] ?? '') : htmlspecialchars($result['log'] ?? '');

    return ['error' => $error, 'header' => $result['header'] ?? null, 'log' => $log, 'server' => ACTIVE_SERVER_NAME];
}

function deleteLog($log)
{
    $path = str_contains_any($log, ['login_failures']) ? APP_DATA_PATH . $log : LOGS_PATH . $log;

    logger(UI_LOG, 'deleteLog() ->');
    logger(UI_LOG, '$log: \'' . $path . '\'');
    logger(UI_LOG, 'removing \'' . $path . '\'');
    unlink($path);
    logger(UI_LOG, 'deleteLog() <-');

    return json_encode(['deleted' => $path]);
}

function purgeLogs($group)
{
    logger(UI_LOG, 'purgeLogs() ->');
    $removedFiles = [];

    if (str_contains($group, 'login failures')) {
        $dir = opendir(APP_DATA_PATH);
        while ($log = readdir($dir)) {
            if (!str_contains($log, 'login_failures')) {
                continue;
            }

            logger(UI_LOG, 'removing \'' . APP_DATA_PATH . $log . '\'');
            $removedFiles[] = APP_DATA_PATH . $log;
            unlink(APP_DATA_PATH . $log);
        }
        closedir($dir);
    } else {
        logger(UI_LOG, '$group: \'' . $group . '\'');
        $logDir = LOGS_PATH . $group . '/';
        $dir    = opendir($logDir);
        while ($log = readdir($dir)) {
            if ($log[0] == '.' || is_dir($logDir . $log) || str_ends_with($log, '.lock')) {
                continue;
            }
            logger(UI_LOG, 'removing \'' . $logDir . $log . '\'');
            $removedFiles[] = $logDir . $log;
            unlink($logDir . $log);
        }
        closedir($dir);
    }

    logger(UI_LOG, 'purgeLogs() <-');

    return json_encode(['deleted' => $removedFiles]);
}

function logger($logfile, $msg, $type = 'info')
{
    global $settingsTable;

    if (!$logfile) {
        return;
    }

    if ($type == 'debug' && $settingsTable['logLevel'] != LOG_LEVEL_DEBUG) {
        return;
    }

    //-- OUTPUT THE LINE TO THE SHELL
    if ($type == 'shell') {
        if (IS_STARTUP) {
            echo $msg . "\n";
        }
        $type = '';
    }

    $backtrace  = debug_backtrace();
    $line       = $backtrace[0]['line'];
    $file       = $backtrace[0]['file'];
    $fileParts  = explode('/', $file);
    $fileName   = $fileParts[count($fileParts) - 1];
    $fileFolder = $fileParts[count($fileParts) - 2] ?? '';
    $file       = ($fileFolder != 'www' && $fileFolder != 'public' ? $fileFolder . '/' : '') . $fileName;
    $log        = date('Y-m-d g:i:s') . ' ' . ($type ? '[' . strtoupper($type) . '] ' : '') . $file . ' LN: ' . $line . ' :: ' . (is_array($msg) || is_object($msg) ? loggerLoopArray($msg, 0) : $msg) . "\n";

    $logDir = dirname($logfile);
    createDirectoryTree($logDir);

    $fp = fopen($logfile, 'ab');
    if (!$fp) {
        return;
    }

    flock($fp, LOCK_EX);
    fwrite($fp, $log);
    fflush($fp);

    $rotateSize = LOG_ROTATE_SIZE * pow(1024, 2);
    if (filesize($logfile) >= $rotateSize) {
        flock($fp, LOCK_UN);
        fclose($fp);

        $suffix        = time() . '-' . getmypid();
        $rotateAttempt = 0;
        while (file_exists($logfile)) {
            $rotated = preg_replace('/\.log$/', '-' . $suffix . ($rotateAttempt ? '-' . $rotateAttempt : '') . '.log', $logfile);
            if (!file_exists($rotated) && rename($logfile, $rotated)) {
                break;
            }
            $rotateAttempt++;
        }
    } else {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

function loggerLoopArray($stack, $depth = 0, $output = '')
{
    $tabs = '';

    for ($x = 0; $x < $depth; $x++) {
        $tabs .= "\t";
    }

    if (!is_array($stack)) {
        $output .= $tabs . $stack;
        return $output;
    }

    foreach ($stack as $key => $val) {
        if (is_object($val)) {
            $val = (array) $val;
        }

        if (is_array($val)) {
            $output .= $tabs . '[' . $key . ']' . "\n";
            $output  = loggerLoopArray($val, ($depth += 1), $output);
            $depth  -= 1;
        } else {
            $output .= $tabs . '[' . $key . '] => ' . $val . "\n";
        }
    }

    return $output;
}
