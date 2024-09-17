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

if (!str_contains_any($_SERVER['PHP_SELF'], ['/api/']) && !str_contains($_SERVER['PWD'], 'oneshot')) {
    if (!$_SESSION['IN_DOCKWATCH']) {
        http_response_code(400);
        exit('Error: You should use the UI, its much prettier.');
    }
}

if (IS_MIGRATION_RUNNING) {
    ?>
    <div class="text-center">
        <h3 class="text-primary">Migration problem</h3>
        <img src="images/error.gif"><br><br>
        If you are seeing this, it means a migration failed to complete! The newest migration is <?= $database->getNewestMigration() ?> and you are on migration <?= $settingsTable['migration'] ?><br>
        You can find the migration log at <code><?= MIGRATION_LOG ?></code>, upload it <a class="text-info" href="https://logs.notifiarr.com" target="_blank">here</a> and join <a class="text-info" href="https://discord.gg/AURf8Yz" target="_blank">Discord</a> for assistance in the #dockwatch channel<br>
        <div class="text-start mt-5">
            <h5>Other options...</h5>
            <ul>
                <li>You can stop <?= APP_NAME ?>, replace the database file in <code><?= DATABASE_PATH . DATABASE_NAME ?></code> with one from <code><?= BACKUP_PATH . '&lt;date&gt;/' . DATABASE_NAME ?></code> and refresh to try again</li>
                <li>You can remove the tmp file <code><?= MIGRATION_FILE ?></code> to gain access back to the UI but this is not advised</li>
                <li>You can delete <code><?= DATABASE_PATH . DATABASE_NAME ?></code> and refresh the page to try and re-run all the migrations but this is also not advised</li>
            </ul>
        </div>
    </div>
    <?php
    exit();
}
