<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'loader.php';
require 'includes/header.php';
$dockerPerms = dockerPermissionCheck();
?>
    <div id="content-overview" style="display: none;"></div>
    <div id="content-containers" style="display: none;"></div>
    <div id="content-notification" style="display: none;"></div>
    <div id="content-settings" style="display: none;"></div>
    <div id="content-logs" style="display: none;"></div>
    <div id="content-dockerPermissions" style="display: <?= ($dockerPerms ? 'none' : 'block') ?>;">
        If you are seeing this, it means the user running this container does not have permission to run docker commands. Please fix that, restart the container and try again.
    </div>
<?php
require 'includes/footer.php';