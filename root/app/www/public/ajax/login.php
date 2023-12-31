<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'login') {
    logger(SYSTEM_LOG, 'login ->');

    $_SESSION['authenticated'] = false;
    $error = '';

    if (!file_exists(LOGIN_FILE)) {
        $error = 'Could not find login file \'' . LOGIN_FILE . '\'';
        logger(SYSTEM_LOG, $error);
    } else {
        $loginsFile = file(LOGIN_FILE);

        logger(SYSTEM_LOG, '$loginsFile=' . json_encode($loginsFile));

        if (empty($loginsFile)) {
            $error = 'Could not read login file data or it is empty';
            logger(SYSTEM_LOG, $error);
        }

        if (!$error) {
            foreach ($loginsFile as $login) {
                logger(SYSTEM_LOG, 'credentials check');
                list($user, $pass) = explode(':', $login);

                //-- STRIP OUT THE SPACES AND LINE BREAKS USERS ACCIDENTALLY PROVIDE
                $user = trim($user);
                $pass = trim($pass);
                $_POST['user'] = trim($_POST['user']);
                $_POST['pass'] = trim($_POST['pass']);

                logger(SYSTEM_LOG, 'file user: \'' . $user . '\'');
                logger(SYSTEM_LOG, 'file pass: \'' . $pass . '\'');
                logger(SYSTEM_LOG, 'post user: \'' . $_POST['user'] . '\'');
                logger(SYSTEM_LOG, 'post pass: \'' . $_POST['pass'] . '\'');

                if (strtolower($user) == strtolower($_POST['user']) && $pass == $_POST['pass']) {
                    if ($_POST['user'] == 'admin' && ($_POST['pass'] == 'pass' || $_POST['pass'] == 'password')) {
                        $error = 'Please use something other than admin:pass and admin:password';
                        logger(SYSTEM_LOG, $error);
                    } else {
                        logger(SYSTEM_LOG, 'match found, updating session key');
                        $_SESSION['authenticated'] = true;
                        logger(SYSTEM_LOG, 'session key authenticated:' . $_SESSION['authenticated']);
                    }
                }
            }

            if (!$error && !$_SESSION['authenticated']) {
                $error = 'Did not find a matching user:pass in the login file with what was provided';
                logger(SYSTEM_LOG, $error);
            }
        }
    }

    logger(SYSTEM_LOG, 'session key authenticated:' . $_SESSION['authenticated']);
    logger(SYSTEM_LOG, 'login <-');
    echo $error;
}

if ($_POST['m'] == 'logout') {
    logger(SYSTEM_LOG, 'logout ->');
    session_unset();
    session_destroy();
    logger(SYSTEM_LOG, 'logout <-');
}