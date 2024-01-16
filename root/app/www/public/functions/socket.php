<?php

/*
----------------------------------
 ------  Created: 011624   ------
 ------  Austin Best	   ------
----------------------------------
*/

function startSocket($socketPort)
{
    stopSocket();

    $command    = 'php ' . ABSOLUTE_PATH . 'socket.php';
    $output     = '/dev/null';
    $pid        = trim(shell_exec(sprintf('%s > %s 2>&1 & echo $!', $command, $output)));
    $started    = trim(shell_exec('netstat -tulpn | grep ' . $socketPort));

    $_SESSION['socketPID'] = $started ? $pid : 0;

    return $pid;
}

function stopSocket()
{
    if ($_SESSION['socketPID']) {
        shell_exec('kill ' . $_SESSION['socketPID']);
    }
}
