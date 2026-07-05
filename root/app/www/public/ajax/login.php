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

    $_SESSION['authenticated']  = false;
    $_SESSION['user_id']        = null;
    $_SESSION['recovery_login'] = false;
    $error                      = '';
    $timeout                    = false;
    $recovery                   = false;

    if (!USE_AUTH) {
        $error = 'Login is not enabled (no active users and no login file entries)';
        logger(SYSTEM_LOG, $error, 'error');
    } else {
        $failureData = [];
        if (file_exists(LOGIN_FAILURE_FILE)) {
            $failureData = json_decode(file_get_contents(LOGIN_FAILURE_FILE), true);
        }

        if (!empty($failureData['failures']) && count($failureData['failures']) > LOGIN_FAILURE_LIMIT) {
            if ($failureData['lastFailure'] + (60 * LOGIN_FAILURE_TIMEOUT) > time()) {
                $timeout = true;
            } else {
                rename(LOGIN_FAILURE_FILE, LOGIN_FAILURE_FILE . '_' . time());
            }
        }

        if (!$timeout) {
            $_POST['user'] = trim($_POST['user']);
            $_POST['pass'] = trim($_POST['pass']);

            $userId = $database->authenticateUser($_POST['user'], $_POST['pass']);

            if (!$userId && file_exists(LOGIN_FILE)) {
                if (authenticateLoginFile($_POST['user'], $_POST['pass'])) {
                    logger(SYSTEM_LOG, 'Recovery login via login file for user: ' . $_POST['user'], 'warn');
                    $userId   = $database->getUserIdByUsername($_POST['user']) ?: true;
                    $recovery = true;
                }
            }

            if ($userId) {
                session_regenerate_id(true);
                $_SESSION['authenticated']  = true;
                $_SESSION['auth_optional']  = false;
                $_SESSION['user_id']        = is_int($userId) ? $userId : null;
                $_SESSION['recovery_login'] = $recovery;

                if (file_exists(LOGIN_FAILURE_FILE)) {
                    rename(LOGIN_FAILURE_FILE, LOGIN_FAILURE_FILE . '_' . time());
                }

                initCsrfToken(true);
            } elseif (!$error) {
                $error = 'Did not find a matching username and password, login failure recorded.';
                logger(SYSTEM_LOG, $error, 'error');

                $loginFailures                = $failureData ?: [];
                $loginFailures['lastFailure'] = time();
                $loginFailures['failures'][]  = ['time' => date('c'), 'user' => $_POST['user']];
                file_put_contents(LOGIN_FAILURE_FILE, json_encode($loginFailures));
            }
        }
    }

    logger(SYSTEM_LOG, 'session key authenticated:' . $_SESSION['authenticated']);
    logger(SYSTEM_LOG, 'login <-');
    echo json_encode(['error' => $error, 'timeout' => $timeout, 'recovery' => $recovery]);
}

if ($_POST['m'] == 'logout') {
    logger(SYSTEM_LOG, 'logout ->');
    session_unset();
    session_destroy();
    logger(SYSTEM_LOG, 'logout <-');
}
