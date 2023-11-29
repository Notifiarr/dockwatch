<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'login') {
    logger(SYSTEM_LOG, 'login ->', 'info');

    $_SESSION['authenticated'] = false;
    $error = '';

    if (!file_exists(LOGIN_FILE)) {
        $error = 'Could not find login file \'' . LOGIN_FILE . '\'';
        logger(SYSTEM_LOG, $error, 'info');
    } else {
        $loginsFile = file(LOGIN_FILE);

        logger(SYSTEM_LOG, '$loginsFile=' . json_encode($loginsFile), 'info');

        if (empty($loginsFile)) {
            $error = 'Could not read login file data or it is empty';
            logger(SYSTEM_LOG, $error, 'info');
        }

        if (!$error) {
            foreach ($loginsFile as $login) {
                logger(SYSTEM_LOG, 'credentials check', 'info');
                list($user, $pass) = explode(':', $login);

                //-- STRIP OUT THE SPACES AND LINE BREAKS USERS ACCIDENTALLY PROVIDE
                $user = trim($user);
                $pass = trim($pass);
                $_POST['user'] = trim($_POST['user']);
                $_POST['pass'] = trim($_POST['pass']);

                logger(SYSTEM_LOG, 'file user: \'' . $user . '\'', 'info');
                logger(SYSTEM_LOG, 'file pass: \'' . $pass . '\'', 'info');
                logger(SYSTEM_LOG, 'post user: \'' . $_POST['user'] . '\'', 'info');
                logger(SYSTEM_LOG, 'post pass: \'' . $_POST['pass'] . '\'', 'info');

                if (strtolower($user) == strtolower($_POST['user']) && $pass == $_POST['pass']) {
                    if ($_POST['user'] == 'admin' && ($_POST['pass'] == 'pass' || $_POST['pass'] == 'password')) {
                        $error = 'Please use something other than admin:pass and admin:password';
                        logger(SYSTEM_LOG, $error, 'info');
                    } else {
                        logger(SYSTEM_LOG, 'match found, updating session key', 'info');
                        $_SESSION['authenticated'] = true;
                        logger(SYSTEM_LOG, 'session key authenticated:' . $_SESSION['authenticated'], 'info');
                    }
                }
            }

            if (!$error && !$_SESSION['authenticated']) {
                $error = 'Did not find a matching user:pass in the login file with what was provided';
                logger(SYSTEM_LOG, $error, 'info');
            }
        }
    }

    logger(SYSTEM_LOG, 'session key authenticated:' . $_SESSION['authenticated'], 'info');
    logger(SYSTEM_LOG, 'login <-', 'info');
    echo $error;
}

if ($_POST['m'] == 'logout') {
    logger(SYSTEM_LOG, 'logout ->', 'info');
    session_unset();
    session_destroy();
    logger(SYSTEM_LOG, 'logout <-', 'info');
}