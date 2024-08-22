<?php

/*
----------------------------------
 ------  Created: 030924   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

$composeExample = 'version: "2.1"
services:
  dockwatch:
    container_name: dockwatch
    image: ghcr.io/notifiarr/dockwatch:main
    ports:
      - ' . APP_PORT . ':80/tcp
    environment:
      - PGID=999
      - TZ=America/New_York
    volumes:
      - /home/dockwatch/config:/config
      - /var/run/docker.sock:/var/run/docker.sock';

$existingComposeFolders = [];
$dir = opendir(COMPOSE_PATH);
while ($folder = readdir($dir)) {
    if ($folder[0] == '.') {
        continue;
    }

    $existingComposeFolders[] = COMPOSE_PATH . $folder;
}
closedir($dir);

if ($_POST['m'] == 'init') {
    if ($_SESSION['activeServerId'] != APP_SERVER_ID) {
        echo 'Remote compose management is not supported. Please do that on the Dockwatch instance directly.';
    } else {
        ?>
        <div class="container-fluid pt-4 px-4 mb-5">
            <div class="bg-secondary rounded h-100 p-4">
                <h6>Add new compose</h6>
                <span class="small-text"><code><?= COMPOSE_PATH ?>{name}/docker-compose.yml</code></span>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Compose</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input id="new-compose-name" class="form-control" type="text" placeholder="dockwatch"></td>
                                <td><textarea id="new-compose-data" class="form-control" rows="10" placeholder="<?= htmlspecialchars($composeExample) ?>"></textarea></td>
                                <td align="center"><input class="btn btn-outline-success" type="button" value="Add Compose" onclick="composeAdd()"></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">
                                    Important notes:<br>
                                    1. This <b>will not</b> validate the compose or yml. You need to make sure it is a valid compose (starts with <code>version: "2.1"</code> for example) and all spacing/indenting is accurate<br>
                                    2. This is not really meant to run a full stack compose, it is done via the browser so it can possibly timeout if it takes to long to pull
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="container-fluid pt-4 px-4 mb-5">
            <div class="bg-secondary rounded h-100 p-4">
                <h6>Compose management</h6>
                <span class="small-text text-muted">You can ssh into the container and run it manually <code>cd <?= COMPOSE_PATH ?>{name} && docker-compose pull</code></span>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Compose Location</th>
                                <th>Controls</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($existingComposeFolders) {
                                foreach ($existingComposeFolders as $existingComposeFolder) {
                                    $composeExists = false;
                                    if (file_exists($existingComposeFolder . '/docker-compose.yml')) {
                                        $composeExists = true;
                                    }
                                    ?>
                                    <tr>
                                        <td><?= $existingComposeFolder ?></td>
                                        <?php 
                                        if ($composeExists) {
                                            ?>
                                            <td>
                                                <input type="button" class="btn btn-outline-info" value="docker-compose pull" onclick="composePull('<?= $existingComposeFolder ?>')">
                                                <input type="button" class="btn btn-outline-info" value="docker-compose up -d" onclick="composeUp('<?= $existingComposeFolder ?>')">
                                                <input type="button" class="btn btn-outline-warning" value="Modify" onclick="composeModify('<?= $existingComposeFolder ?>')">
                                                <input type="button" class="btn btn-outline-danger" value="Delete" onclick="composeDelete('<?= $existingComposeFolder ?>')">
                                            </td>
                                            <?php
                                        } else {
                                            ?>
                                            <td colspan="2">docker-compose.yml is missing / has invalid permissions</td>
                                            <?php
                                        }
                                        ?>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}

if ($_POST['m'] == 'composeSave') {
    //-- MAKE SURE THE PATH IS IN THE COMPOSE LOCATION PATH
    if (substr($_POST['composePath'], 0, strlen(COMPOSE_PATH)) == COMPOSE_PATH) {
        file_put_contents($_POST['composePath'] . '/docker-compose.yml', rawurldecode($_POST['compose']));
    }
}

if ($_POST['m'] == 'composeModify') {
    $path       = $_POST['composePath'] . '/docker-compose.yml';
    $compose    = file_get_contents($path);

    ?>
    <code><?= $_POST['composePath'] ?>/docker-compose.yml</code><br>
    <textarea id="compose-data" class="form-control" rows="15"><?= $compose ?></textarea><br>
    <center><input type="button" class="btn btn-outline-success" value="Save changes" onclick="composeSave('<?= $_POST['composePath'] ?>')"></center>
    <?php
}

if ($_POST['m'] == 'composeAdd') {
    $path = COMPOSE_PATH . preg_replace('/[^ \w]+/', '', $_POST['name']);
    createDirectoryTree($path);
    file_put_contents($path . '/docker-compose.yml', rawurldecode($_POST['compose']));
}

if ($_POST['m'] == 'composeDelete') {
    //-- MAKE SURE THE PATH IS IN THE COMPOSE LOCATION PATH
    if (substr($_POST['composePath'], 0, strlen(COMPOSE_PATH)) == COMPOSE_PATH) {
        $shell->exec('rm -rf ' . $_POST['composePath']);
    }
}

if ($_POST['m'] == 'composePull') {
    $cmd = 'cd ' . $_POST['composePath'] . ' && docker-compose pull';
    $pull = $shell->exec($cmd . ' 2>&1');

    if (str_contains_all($pull, ['Pulling', 'Pulled'])) {
        echo 'pulled';
    } else {
        echo $pull;
    }
}

if ($_POST['m'] == 'composeUp') {
    $cmd    = 'cd ' . $_POST['composePath'] . ' && docker-compose up -d';
    $up     = $shell->exec($cmd . ' 2>&1');

    if (str_contains_all($up, ['Container', 'Started']) || str_contains_all($up, ['Container', 'Running'])) {
        echo 'up';
    } else {
        echo $up;
    }
}
