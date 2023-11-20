<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'login') {
    $loginsFile = file(LOGIN_FILE);
    foreach ($loginsFile as $login) {
        list($user, $pass) = explode(':', $login);
        if ($user === $_POST['user'] && $pass === $_POST['pass']) {
            $_SESSION['authenticated'] = true;
        }
    }
}

if ($_POST['m'] == 'logout') {
    session_unset();
    session_destroy();
}