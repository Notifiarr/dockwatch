<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

if (file_exists('loader.php')) {
    define('ABSOLUTE_PATH', './');
}
if (file_exists('../loader.php')) {
    define('ABSOLUTE_PATH', '../');
}
if (file_exists('../../loader.php')) {
    define('ABSOLUTE_PATH', '../../');
}
require ABSOLUTE_PATH . 'loader.php';

maintenanceGate(503, 'Maintenance container: the UI is not available', false);

if (!str_contains_any($_SERVER['PHP_SELF'], ['/api/']) && !str_contains($_SERVER['PWD'], 'oneshot')) {
    if (!$_SESSION['IN_DOCKWATCH']) {
        http_response_code(400);
        exit('Error: You should use the UI, its much prettier.');
    }
}

if ($_POST['m'] == 'removeMigrationFile') {
    if (file_exists(MIGRATION_FILE)) {
        deleteFile(MIGRATION_FILE);
    }

    exit;
}

$ajaxFile = basename(debug_backtrace()[0]['file'], '.php');
$page     = in_array($ajaxFile, $pages) ? $ajaxFile : 'overview';

if ($page == 'login' && $_SESSION['authenticated']) {
    $page = 'overview';
}

$database->setSetting('currentPage', $page);

if (IS_MIGRATION_RUNNING) {
    $highestMigration = $database->getNewestMigration();
    $currentMigration = $settingsTable['migration'];

    if ($highestMigration == $currentMigration) {
        deleteFile(MIGRATION_FILE);
    }

    ?>
    <div class="text-center">
        <h3 class="text-primary">Migration problem</h3>
        <img src="images/error.gif"><br><br>
        If you are seeing this, it means a migration failed to complete! The newest migration is <?= $highestMigration ?> and you are on migration <?= $currentMigration ?><br>
        You can find the migration log at <code><?= MIGRATION_LOG ?></code>, upload it <a class="text-info" href="https://logs.notifiarr.com" target="_blank">here</a> and join <a class="text-info" href="https://discord.gg/AURf8Yz" target="_blank">Discord</a> for assistance in the #dockwatch channel<br>
        <div class="text-start mt-5">
            <h5>Other options...</h5>
            <ul>
                <li>You can remove the tmp file <code style="cursor: pointer;" onclick="removeMigrationFile()"><?= htmlspecialchars((string) MIGRATION_FILE, ENT_QUOTES, 'UTF-8') ?></code> to gain access back to the UI but this is not advised</li>
            </ul>
        </div>
    </div>
    <?php
    exit();
}
