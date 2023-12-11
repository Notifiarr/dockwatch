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
    if (file_exists(LOGS_PATH . $log)) {
        $log        = file(LOGS_PATH . $log);
        $header     = 'Lines: ' . count($log);
        
        foreach ($log as $index => $line) {
            $content .= str_pad(($index + 1), strlen(count($log)), ' ', STR_PAD_RIGHT) .' | '. $line;
        }
    } else {
        $error = 'Selected log file not found';
    }

    return json_encode(['header' => $header, 'log' => str_replace('[ERROR]', '<span class="text-danger">[ERROR]</span>', $content), 'error' => $error]);
}

function deleteLog($log)
{
    logger(UI_LOG, 'deleteLog() ->');
    logger(UI_LOG, '$log: \''. $log .'\'');
    logger(UI_LOG, 'removing \''. LOGS_PATH . $log .'\'');
    unlink(LOGS_PATH . $log);
    logger(UI_LOG, 'deleteLog() <-');

    return json_encode(['deleted' => LOGS_PATH . $log]);
}

function purgeLogs($group)
{
    logger(UI_LOG, 'purgeLogs() ->');
    logger(UI_LOG, '$group: \''. $group .'\'');
    $removedFiles = [];
    $logDir = LOGS_PATH . $group .'/';
    $dir = opendir($logDir);
    while ($log = readdir($dir)) {
        if ($log[0] == '.' || is_dir($logDir . $log)) {
            continue;
        }
        logger(UI_LOG, 'removing \''. $logDir . $log .'\'');
        $removedFiles[] = $logDir . $log;
        unlink($logDir . $log);
    }
    closedir($dir);
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
