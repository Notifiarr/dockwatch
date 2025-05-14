<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'resetSession') {
    session_unset();
    session_destroy();
}

if ($_POST['m'] == 'login') {
    logger(SYSTEM_LOG, 'login ->');

    $_SESSION['authenticated'] = false;
    $error = '';
    $timeout = false;

    if (!file_exists(LOGIN_FILE)) {
        $error = 'Could not find login file \'' . LOGIN_FILE . '\'';
        logger(SYSTEM_LOG, $error, 'error');
    } else {
        $loginsFile = file(LOGIN_FILE);

        logger(SYSTEM_LOG, '$loginsFile=' . json_encode($loginsFile));

        if (empty($loginsFile)) {
            $error = 'Could not read login file data or it is empty';
            logger(SYSTEM_LOG, $error, 'error');
        }

        if (!$error) {
            $failureData = [];
            if (file_exists(LOGIN_FAILURE_FILE)) {
                $failureData = json_decode(file_get_contents(LOGIN_FAILURE_FILE), true);
            }

            if (!empty($failureData['failures']) && count($failureData['failures']) > LOGIN_FAILURE_LIMIT) {
                $timeout = true;
            } else {
                foreach ($loginsFile as $login) {
                    logger(SYSTEM_LOG, 'credentials check');
                    list($user, $pass) = explode(':', $login);

                    //-- STRIP OUT THE SPACES AND LINE BREAKS USERS ACCIDENTALLY PROVIDE
                    $user = trim($user);
                    $pass = trim($pass);
                    $_POST['user'] = trim($_POST['user']);
                    $_POST['pass'] = trim($_POST['pass']);

                    if (str_compare($user, $_POST['user']) && str_compare($pass, $_POST['pass'], true)) {
                        if ($_POST['user'] == 'admin' && ($_POST['pass'] == 'pass' || $_POST['pass'] == 'password')) {
                            $error = 'Please use something other than admin:pass and admin:password';
                            logger(SYSTEM_LOG, $error, 'error');
                        } else {
                            logger(SYSTEM_LOG, 'match found, updating session key');
                            $_SESSION['authenticated'] = true;
                            logger(SYSTEM_LOG, 'session key authenticated:' . $_SESSION['authenticated']);

                            if (file_exists(LOGIN_FAILURE_FILE)) {
                                rename(LOGIN_FAILURE_FILE, LOGIN_FAILURE_FILE . '_' . time());
                            }
                        }
                    }
                }

                if (!$error && !$_SESSION['authenticated']) {
                    $error = 'Did not find a matching user:pass in the login file with what was provided, login failure recorded.';
                    logger(SYSTEM_LOG, $error, 'error');

                    $loginFailures['lastFailure'] = time();
                    $loginFailures['failures'][] = ['time' => date('c'), 'user' => $_POST['user'], 'pass' => $_POST['pass']];
                    file_put_contents(LOGIN_FAILURE_FILE, json_encode($loginFailures));
                }
            }
        }
    }

    logger(SYSTEM_LOG, 'session key authenticated:' . $_SESSION['authenticated']);
    logger(SYSTEM_LOG, 'login <-');
    echo json_encode(['error' => $error, 'timeout' => $timeout]);
}

if ($_POST['m'] == 'logout') {
    logger(SYSTEM_LOG, 'logout ->');
    session_unset();
    session_destroy();
    logger(SYSTEM_LOG, 'logout <-');
}