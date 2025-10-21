<?php

/*
----------------------------------
 ------  Created: 030924   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

$composeExample = 'services:
  dockwatch:
    image: "ghcr.io/notifiarr/dockwatch:main"
    container_name: "dockwatch"
    hostname: "dockwatch"
    network_mode: "bridge"
    restart: "unless-stopped"
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

    $existingComposeFolders[strtolower($folder)] = COMPOSE_PATH . $folder;
}
closedir($dir);
sort($existingComposeFolders);

if ($_POST['m'] == 'init') {
    if ($_SESSION['activeServerId'] != APP_SERVER_ID) {
        echo 'Remote compose management is not supported. Please do that on the Dockwatch instance directly.';
    } else {
    ?>
    <ol class="breadcrumb rounded p-1 ps-2">
        <li class="breadcrumb-item"><a href="#" onclick="initPage('overview')"><?= $_SESSION['activeServerName'] ?></a><span class="ms-2">↦</span></li>
        <li class="breadcrumb-item active" aria-current="page">Compose</li>
    </ol>
    <div class="bg-secondary rounded h-100 p-4">
        <h4>Add new compose</h4>
        <span class="small-text"><code><?= COMPOSE_PATH ?>{name}/docker-compose.yml</code></span>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3">Name</th>
                        <th class="bg-primary ps-3">Compose</th>
                        <th class="rounded-top-right-1 bg-primary ps-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="bg-secondary"><input id="new-compose-name" class="form-control" type="text" placeholder="dockwatch"></td>
                        <td class="bg-secondary"><textarea id="new-compose-data" class="form-control" rows="10" placeholder="<?= htmlspecialchars($composeExample) ?>"></textarea></td>
                        <td class="bg-secondary text-center"><input class="btn btn-outline-success" type="button" value="Add Compose" onclick="composeAdd()"></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="rounded-bottom-left-1 rounded-bottom-right-1 bg-primary ps-3" colspan="3">
                            Important notes:<br>
                            1. This <b>will not</b> validate the compose or yml. You need to make sure it is a valid compose and all spacing/indenting is accurate<br>
                            2. This is not really meant to run a full stack compose, it is done via the browser so it can possibly timeout if it takes to long to pull
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="bg-secondary rounded mt-3 p-4">
        <h4>Compose management</h4>
        <span class="small-text text-muted">You can ssh into the container and run it manually <code>cd <?= COMPOSE_PATH ?>{name} && docker-compose pull</code></span>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3">Compose Location</th>
                        <th class="rounded-top-right-1 bg-primary ps-3">Controls</th>
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
                            <tr class="border border-dark border-top-0 border-start-0 border-end-0">
                                <td class="bg-secondary"><?= $existingComposeFolder ?></td>
                                <?php
                                if ($composeExists) {
                                    ?>
                                    <td class="bg-secondary">
                                        <input type="button" class="btn btn-outline-info" value="docker-compose pull" onclick="composePull('<?= $existingComposeFolder ?>')">
                                        <input type="button" class="btn btn-outline-info" value="docker-compose up -d" onclick="composeUp('<?= $existingComposeFolder ?>')">
                                        <input type="button" class="btn btn-outline-warning" value="Modify" onclick="composeModify('<?= $existingComposeFolder ?>')">
                                        <input type="button" class="btn btn-outline-danger" value="Delete" onclick="composeDelete('<?= $existingComposeFolder ?>')">
                                    </td>
                                    <?php
                                } else {
                                    ?>
                                    <td class="bg-secondary" colspan="2">docker-compose.yml is missing / has invalid permissions</td>
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
    <?php
    }
}

if ($_POST['m'] == 'composeSave') {
    //-- MAKE SURE THE PATH IS IN THE COMPOSE LOCATION PATH
    if (substr($_POST['composePath'], 0, strlen(COMPOSE_PATH)) == COMPOSE_PATH) {
        $path = $_POST['composePath'] . '/docker-compose.yml';

        //-- SAVE TO FILE
        file_put_contents($path, rawurldecode($_POST['compose']));

        //-- VALIDATE YAML
        $yqOutput = $shell->exec('yq eval "." ' . escapeshellarg($path) . ' 2>&1');
        $yqResult = str_contains($yqOutput, 'Error') ? $yqOutput : '0';

        //-- CHECK IF IT IS A VALID DOCKER COMPOSE FILE
        $dockerOutput = $shell->exec('docker compose -f ' . escapeshellarg($path) . ' config 2>&1');
        $dockerResult = !str_contains($dockerOutput, 'name') ? $dockerOutput : '0';

        //-- RETURN RESULTS
        if ($yqResult === '0' && $dockerResult === '0') {
            $compose            = file_get_contents($path);
            $syntaxHighlighted  = $phiki->codeToHtml($compose, Phiki\Grammar\Grammar::Yaml, Phiki\Theme\Theme::GithubDark);

            echo $syntaxHighlighted;
        } else {
            echo 'Failed to save file.<br>Error: ' . htmlspecialchars($yqResult !== '0' ? preg_replace('/^Error:\s*/', '', $yqResult) : $dockerResult);
        }
    }
}

if ($_POST['m'] == 'composeModify') {
    $path       = $_POST['composePath'] . '/docker-compose.yml';
    $compose    = file_get_contents($path);
    $syntaxHighlighted = $phiki->codeToHtml($compose, Phiki\Grammar\Grammar::Yaml, Phiki\Theme\Theme::GithubDark);

    ?>
    <code><?= $_POST['composePath'] ?>/docker-compose.yml</code><br>
    <div id="compose-data-preview" onclick="$('#compose-data').show(); $(this).hide();"><?= $syntaxHighlighted ?></div>
    <textarea id="compose-data" class="form-control" rows="15" style='display: none;'><?= $compose ?></textarea><br>
    <center><input type="button" class="btn btn-outline-success" value="Save file" onclick="composeSave('<?= $_POST['composePath'] ?>', $('#compose-data').val())"></center>
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
