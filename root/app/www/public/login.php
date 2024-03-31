<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'loader.php';
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
    <div class="bg-secondary rounded h-100 p-4">
        <div class="row justify-content-center">
            <?php 
            if ($loginTimeout) {
                ?>Logins have been disabled from to many failed login attempts. Please review the login_failures file for more details about this.<?php
            } else {
                ?>
                <div class="col-lg-3">
                    <div class="row">
                        <div class="col-sm-12 col-lg-4">Username</div>
                        <div class="col-sm-12 col-lg-8"><input id="username" type="text" class="form-control"></div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-sm-12 col-lg-4">Password</div>
                        <div class="col-sm-12 col-lg-8"><input id="password" type="password" class="form-control"></div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-sm-12"><button class="btn btn-outline-success" onclick="login()">Login</button></div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>
<?php
require 'includes/footer.php';
