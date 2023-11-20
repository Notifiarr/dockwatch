<?php

/*
----------------------------------
 ------  Created: 111923   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'loader.php';
require 'includes/header.php';
?>
<div class="container-fluid pt-4 px-4">
    <div class="bg-secondary rounded h-100 p-4">
        <div class="row justify-content-center">
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
        </div>
    </div>
</div>
<?php
require 'includes/footer.php';
