<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'login') {
    $systemLog = LOGS_PATH . 'system/' . date('Ymd') . '.log';
    logger($systemLog, 'login ->', 'info');

    $_SESSION['authenticated'] = false;
    $error = '';

    if (!file_exists(LOGIN_FILE)) {
        $error = 'Could not find login file \'' . LOGIN_FILE . '\'';
        logger($systemLog, $error, 'info');
    } else {
        $loginsFile = file(LOGIN_FILE);

        logger($systemLog, '$loginsFile=' . json_encode($loginsFile), 'info');

        if (empty($loginsFile)) {
            $error = 'Could not read login file data or it is empty';
            logger($systemLog, $error, 'info');
        }

        if (!$error) {
            foreach ($loginsFile as $login) {
                logger($systemLog, 'credentials check', 'info');
                list($user, $pass) = explode(':', $login);

                //-- STRIP OUT THE SPACES AND LINE BREAKS USERS ACCIDENTALLY PROVIDE
                $user = trim($user);
                $pass = trim($pass);
                $_POST['user'] = trim($_POST['user']);
                $_POST['pass'] = trim($_POST['pass']);

                logger($systemLog, 'file user: \'' . $user . '\'', 'info');
                logger($systemLog, 'file pass: \'' . $pass . '\'', 'info');
                logger($systemLog, 'post user: \'' . $_POST['user'] . '\'', 'info');
                logger($systemLog, 'post pass: \'' . $_POST['pass'] . '\'', 'info');

                if (strtolower($user) == strtolower($_POST['user']) && $pass == $_POST['pass']) {
                    if ($_POST['user'] == 'admin' && ($_POST['pass'] == 'pass' || $_POST['pass'] == 'password')) {
                        $error = 'Please use something other than admin:pass and admin:password';
                        logger($systemLog, $error, 'info');
                    } else {
                        logger($systemLog, 'match found, updating session key', 'info');
                        $_SESSION['authenticated'] = true;
                        logger($systemLog, 'session key authenticated:' . $_SESSION['authenticated'], 'info');
                    }
                }
            }

            if (!$error && !$_SESSION['authenticated']) {
                $error = 'Did not find a matching user:pass in the login file with what was provided';
                logger($systemLog, $error, 'info');
            }
        }
    }

    logger($systemLog, 'session key authenticated:' . $_SESSION['authenticated'], 'info');
    logger($systemLog, 'login <-', 'info');
    echo $error;
}

if ($_POST['m'] == 'logout') {
    logger($systemLog, 'logout ->', 'info');
    session_unset();
    session_destroy();
    logger($systemLog, 'logout <-', 'info');
}