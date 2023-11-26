<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'login') {
    $_SESSION['authenticated'] = false;
    $loginsFile = file(LOGIN_FILE);
    $error = '';

    if (!file_exists(LOGIN_FILE)) {
        $error = 'Could not find login file \'' . LOGIN_FILE . '\'';
    }

    if (empty($loginsFile)) {
        $error = 'Could not read login file data or it is empty';
    }

    if ($_POST['user'] == 'admin' && ($_POST['pass'] == 'pass' || $_POST['pass'] == 'password')) {
        $error = 'Please use something other than admin:pass and admin:password';
    }

    if (!$error) {
        foreach ($loginsFile as $login) {
            list($user, $pass) = explode(':', $login);

            if (strtolower($user) == strtolower($_POST['user']) && $pass == $_POST['pass']) {
                $_SESSION['authenticated'] = true;
            }
        }
    }

    if (!$_SESSION['authenticated']) {
        $error = 'Did not find a matching user:pass in the login file with what was provided';
    }

    echo $error;
}

if ($_POST['m'] == 'logout') {
    session_unset();
    session_destroy();
}