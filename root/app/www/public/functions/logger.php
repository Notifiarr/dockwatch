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
            $log        = file($path);
            $header     = 'Lines: ' . count($log);
            
            foreach ($log as $index => $line) {
                $content .= str_pad(($index + 1), strlen(count($log)), ' ', STR_PAD_RIGHT) .' | '. $line;
            }
        }
    } else {
        $error = 'Selected log file not found';
    }

    return json_encode(['header' => $header, 'log' => str_replace('[ERROR]', '<span class="text-danger">[ERROR]</span>', $content), 'error' => $error]);
}

function deleteLog($log)
{
    $path = str_contains_any($log, ['login_failures']) ? APP_DATA_PATH . $log : LOGS_PATH . $log;

    logger(UI_LOG, 'deleteLog() ->');
    logger(UI_LOG, '$log: \''. $path .'\'');
    logger(UI_LOG, 'removing \''. $path .'\'');
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
        $logDir = LOGS_PATH . $group .'/';
        $dir = opendir($logDir);
        while ($log = readdir($dir)) {
            if ($log[0] == '.' || is_dir($logDir . $log)) {
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
    if (!$logfile) {
        return;
    }

    $backtrace  = debug_backtrace();
    $line       = $backtrace[0]['line'];
    $file       = $backtrace[0]['file'];
    $fileParts  = explode('/', $file);
    $fileName   = $fileParts[count($fileParts) - 1];
    $fileFolder = $fileParts[count($fileParts) - 2];
    $file       = ($fileFolder != 'www' && $fileFolder != 'public' ? $fileFolder . '/' : '') . $fileName;
    $log        = date('Y-m-d g:i:s') . ' ' . ($type ? '[' . strtoupper($type) . '] ' : '') . $file . ' LN: ' . $line . ' :: ' . (is_array($msg) || is_object($msg) ? loggerLoopArray($msg, 0) : $msg) . "\n";

    file_put_contents($logfile, $log, FILE_APPEND);

    $rotateSize = LOG_ROTATE_SIZE * pow(1024, 2);
    if (filesize($logfile) >= $rotateSize) {
        $rotated = str_replace('.log', '-' . time() . '.log', $logfile);
        rename($logfile, $rotated);
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
            $output = loggerLoopArray($val, ($depth += 1), $output);
            $depth -= 1;
        } else {
            $output .= $tabs . '[' . $key . '] => ' . $val . "\n";
        }
    }

    return $output;
}
