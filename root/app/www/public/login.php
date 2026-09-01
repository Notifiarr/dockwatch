<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'loader.php';

if (!USE_AUTH) {
    header('Location: index.php');
    exit;
}

maintenanceGate(503, 'Maintenance container: the UI is not available');

$_SESSION['IN_DOCKWATCH'] = true;

require 'includes/header.php';

$loginTimeout = false;
if (file_exists(LOGIN_FAILURE_FILE)) {
    $failureData = json_decode(file_get_contents(LOGIN_FAILURE_FILE), true);

    if (!empty($failureData['failures']) && count($failureData['failures']) > LOGIN_FAILURE_LIMIT) {
        if ($failureData['lastFailure'] + (60 * LOGIN_FAILURE_TIMEOUT) > time()) {
            $loginTimeout = true;
        } else {
            //-- TIMEOUT WINDOW IS OVER
            rename(LOGIN_FAILURE_FILE, LOGIN_FAILURE_FILE . '_' . time());
        }
    }
}
?>
<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-4 offset-lg-4" style="min-width: 330px;">
            <div class="bg-secondary rounded p-4">
                <?php
                if ($loginTimeout || $apiVersionError || $apiPermissionsError) {
                    ?>Logins are disabled because of Docker API errors or too many failed login attempts. Please review the login_failures file.<?php
                } else {
                    ?>
                    <div class="mb-2">
                        <label class="form-label" for="username">Username</label>
                        <input id="username" type="text" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="password">Password</label>
                        <input id="password" type="password" class="form-control">
                    </div>
                    <div class="mt-3 text-center">
                        <button class="btn btn-outline-success" onclick="login()">Login</button>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
<?php
require 'includes/footer.php';
