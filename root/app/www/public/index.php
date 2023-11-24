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
    <div id="content-orphans" style="display: none;"></div>
    <div id="content-notification" style="display: none;"></div>
    <div id="content-settings" style="display: none;"></div>
    <div id="content-logs" style="display: none;"></div>
    <div id="content-tasks" style="display: none;"></div>
    <div id="content-dockerPermissions" style="display: <?= ($dockerPerms ? 'none' : 'block') ?>;">
        If you are seeing this, it means the user:group running this container does not have permission to run docker commands. Please fix that, restart the container and try again.<br><br>
        An example for Ubuntu:
        <div class="ms-2">
            Set the PGID to the docker group (998 or 999 but you can run <code>grep docker /etc/group</code>) and then:<br>
            Change the user:group with a chown<br>
            Wipe the appdir and retry<br>
            Try with --force-recreate
        </div>
    </div>
<?php
require 'includes/footer.php';