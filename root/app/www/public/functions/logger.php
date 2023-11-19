<?php

/*
----------------------------------
 ------  Created: 111823   ------
 ------  Austin Best	   ------
----------------------------------
*/

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
    $file       = $fileFolder . '/' . $fileName;
    $log        = date('g:i:s') . ' ' . ($type ? '[' . strtoupper($type) . '] ' : '') . $file . ' LN: ' . $line . ' :: ' . (is_array($msg) || is_object($msg) ? loggerLoopArray($msg, 0) : $msg) . "\n";

    file_put_contents($logfile, $log, FILE_APPEND);
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
