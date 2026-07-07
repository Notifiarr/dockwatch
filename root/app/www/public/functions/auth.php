<?php

/*
----------------------------------
 ------  Created: 070426   ------
 ------  Austin Best	   ------
----------------------------------
*/

function parseLoginFile()
{
    if (!file_exists(LOGIN_FILE)) {
        return [];
    }

    $users = [];

    foreach (file(LOGIN_FILE) as $login) {
        $login = trim($login);

        if ($login == '' || !str_contains($login, ':')) {
            continue;
        }

        list($user, $pass) = explode(':', $login, 2);
        $user              = trim($user);
        $pass              = trim($pass);

        if ($user == '' || $pass == '') {
            continue;
        }

        $users[] = ['username' => $user, 'password' => $pass];
    }

    return $users;
}

function loginFileHasEntries()
{
    return count(parseLoginFile()) > 0;
}

function loginFileHasDefaultPassword()
{
    if (!file_exists(LOGIN_FILE)) {
        return false;
    }

    foreach (file(LOGIN_FILE) as $login) {
        if (trim($login) == 'admin:password') {
            return true;
        }
    }

    return false;
}

function isDefaultLoginCredential($username, $password)
{
    return $username == 'admin' && ($password == 'pass' || $password == 'password');
}

function authenticateLoginFile($username, $password)
{
    if (isDefaultLoginCredential($username, $password)) {
        return false;
    }

    foreach (parseLoginFile() as $login) {
        if (str_compare($login['username'], $username) && str_compare($login['password'], $password, true)) {
            return true;
        }
    }

    return false;
}

function mergeUserSources($usersTable, $loginFileUsers = [])
{
    if (!$loginFileUsers) {
        $loginFileUsers = parseLoginFile();
    }

    $merged = [];

    foreach ($usersTable as $user) {
        $key          = strtolower($user['username']);
        $merged[$key] = [
            'username'  => $user['username'],
            'database'  => $user,
            'loginFile' => false,
        ];
    }

    foreach ($loginFileUsers as $login) {
        $key = strtolower($login['username']);

        if (!isset($merged[$key])) {
            $merged[$key] = [
                'username'  => $login['username'],
                'database'  => null,
                'loginFile' => true,
            ];
        } else {
            $merged[$key]['loginFile'] = true;
        }
    }

    uasort($merged, fn($a, $b) => strcasecmp($a['username'], $b['username']));

    return $merged;
}

function isLoginExemptPath()
{
    return str_contains($_SERVER['PHP_SELF'] ?? '', 'login.php');
}

function isCliCronOrStartup($dockwatchScriptPath, $dockwatchCronParent)
{
    $isCron = str_contains($dockwatchScriptPath, '/crons/') || str_contains($dockwatchScriptPath, '\\crons\\') || strcasecmp($dockwatchCronParent, 'crons') === 0;

    return (IS_STARTUP || $isCron) && php_sapi_name() === 'cli';
}

function isCronOrStartupScript($dockwatchScriptPath, $dockwatchCronParent)
{
    $isCron = str_contains($dockwatchScriptPath, '/crons/') || str_contains($dockwatchScriptPath, '\\crons\\') || strcasecmp($dockwatchCronParent, 'crons') === 0;

    return $isCron || str_contains($dockwatchScriptPath, 'startup.php');
}

function enforceAuthentication()
{
    if (!USE_AUTH || $_SESSION['authenticated'] || isLoginExemptPath()) {
        return;
    }

    global $dockwatchScriptPath, $dockwatchCronParent;

    if (isCliCronOrStartup($dockwatchScriptPath, $dockwatchCronParent)) {
        return;
    }

    $isAjax = str_contains($_SERVER['PHP_SELF'] ?? '', '/ajax/');

    if ($isAjax) {
        recordIntrusion('unauthorized', ['path' => $_SERVER['PHP_SELF'] ?? '']);
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    recordIntrusion('unauthorized', ['path' => $_SERVER['PHP_SELF'] ?? '']);
    header('Location: login.php');
    exit;
}

function initCsrfToken($regenerate = false)
{
    if ($regenerate || empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function validateCsrfToken($token)
{
    return $token && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function enforceCsrfToken()
{
    if (!USE_AUTH) {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $ajaxFile = basename($_SERVER['PHP_SELF'] ?? '', '.php');
    $method   = $_POST['m'] ?? '';

    if ($ajaxFile == 'login' && str_equals_any($method, ['login', 'logout', 'resetSession'])) {
        return;
    }

    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!validateCsrfToken($token)) {
        recordIntrusion('invalid_csrf', ['ajax' => $ajaxFile, 'method' => $method]);
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

function applyUserAuthChange()
{
    global $database;

    $authWasRequired = USE_AUTH;
    $authIsRequired    = $database->getActiveUserCount() > 0 || loginFileHasEntries();

    if ($authIsRequired && !empty($_SESSION['auth_optional'])) {
        $_SESSION['authenticated'] = false;
        unset($_SESSION['auth_optional'], $_SESSION['user_id'], $_SESSION['recovery_login']);

        return ['requireLogin' => true, 'authDisabled' => false];
    }

    if (!$authIsRequired && $authWasRequired) {
        $_SESSION['authenticated'] = true;
        $_SESSION['auth_optional'] = true;
        unset($_SESSION['user_id'], $_SESSION['recovery_login']);

        return ['requireLogin' => false, 'authDisabled' => true];
    }

    return ['requireLogin' => false, 'authDisabled' => false];
}
