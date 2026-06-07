<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'loader.php';

maintenanceGate(503, 'Maintenance container: the UI is not available');

require 'includes/header.php';

$loadError = '';
if (!$serversTable) {
    $loadError = 'Servers table is empty, this means the migration 001 did not run or a database could not be created.';
}

if (!file_exists(REGCTL_PATH . REGCTL_BINARY)) {
    $loadError = 'The required regctl binary is missing from \'' . REGCTL_PATH . REGCTL_BINARY . '\'';
}

if (!$isDockerApiAvailable) {
    $loadError = 'There is a problem talking to the docker API. You either did not mount <code>/var/run/docker.sock</code> or you are passing in a <code>DOCKER_HOST</code> that is not valid. Try using the IP instead of container name for the docker host variable.';

    if ($_SERVER['DOCKER_HOST']) {
        $loadError .= 'You can test the response if you SSH into the container and run <code>curl ' . $_SERVER['DOCKER_HOST'] . '</code>. The expected response is a 403 error.';
    }
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
    <div id="content-networks" style="display: none;"></div>
    <div id="content-compose" style="display: none;"></div>
    <div id="content-orphans" style="display: none;"></div>
    <div id="content-notification" style="display: none;"></div>
    <div id="content-settings" style="display: none;"></div>
    <div id="content-logs" style="display: none;"></div>
    <div id="content-tasks" style="display: none;"></div>
    <div id="content-commands" style="display: none;"></div>
    <div id="content-database" style="display: none;"></div>
    <div id="content-security" style="display: none;"></div>
    <?php
}
require 'includes/footer.php';
