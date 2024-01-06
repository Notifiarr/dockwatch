<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'loader.php';
require 'includes/header.php';

$dockerPerms = apiRequest('dockerPermissionCheck');
$dockerPerms = json_decode($dockerPerms['response']['docker'], true);

$loadError = '';
if (!$serversFile) {
    $loadError = 'Servers file missing or corrupt';
}

if (!file_exists(REGCTL_PATH . REGCTL_BINARY)) {
    $loadError = 'The required regctl binary is missing from \'' . REGCTL_PATH . REGCTL_BINARY . '\'';
}

if ($loadError) {
    ?>
    <div id="apiError">
        <?= $loadError ?>
    </div>
    <?php
} else {
?>
    <div id="content-overview" style="display: none;"></div>
    <div id="content-containers" style="display: none;"></div>
    <div id="content-orphans" style="display: none;"></div>
    <div id="content-notification" style="display: none;"></div>
    <div id="content-settings" style="display: none;"></div>
    <div id="content-logs" style="display: none;"></div>
    <div id="content-tasks" style="display: none;"></div>
    <div id="content-commands" style="display: none;"></div>
    <div id="content-dockerPermissions" style="display: <?= ($dockerPerms ? 'none' : 'block') ?>;">
        If you are seeing this, it means the user:group running this container does not have permission to run docker commands. Please fix that, restart the container and try again.<br><br>
        <div class="bg-secondary rounded p-4">
            An example for Ubuntu:
            <div class="ms-2">
                Set the PGID to the docker group<br>
                &nbsp;&nbsp;&nbsp;- <code>ls -ltra /var/run/docker.sock</code> to see the group running the sock ("docker" for example)<br>
                &nbsp;&nbsp;&nbsp;- <code>grep docker /etc/group</code> to see the group id and use as the PGID<br>
                Change the user:group with a chown (if necessary)<br>
                Wipe the appdir and retry (if necessary)<br>
                Try with --force-recreate (if necessary)
            </div>
        </div>
        <div class="bg-secondary rounded p-4 mt-3">
            An example for Synology:
            <div class="ms-2">
                Create a docker group <code>sudo synogroup --add docker</code><br>
                &nbsp;&nbsp;&nbsp;- Take note of the group id it returns next to Group Id: [65537] (for example)<br>
                Adjust docker sock permissions: <code>sudo chown root:docker /var/run/docker.sock</code><br>
                Assign the PGID to the group id from above and restart (docker-compose up -d)
            </div>
        </div>
    </div>
<?php
}
require 'includes/footer.php';