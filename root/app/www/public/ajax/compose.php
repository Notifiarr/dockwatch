<?php

/*
----------------------------------
 ------  Created: 030924   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

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

$composeFiles = [];
$dir          = opendir(COMPOSE_PATH);
while ($folder = readdir($dir)) {
    if ($folder[0] == '.') {
        continue;
    }

    $composePath   = COMPOSE_PATH . $folder;
    $composeFile   = $composePath . '/docker-compose.yml';
    $composeExists = file_exists($composeFile);

    $composeFiles[] = [
        'name'          => $folder,
        'path'          => $composePath,
        'composeExists' => $composeExists,
        'added'         => is_dir($composePath) ? filectime($composePath) : null,
        'updated'       => $composeExists ? filemtime($composeFile) : (is_dir($composePath) ? filemtime($composePath) : null),
    ];
}
closedir($dir);

usort($composeFiles, function ($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

if ($_POST['m'] == 'init') {
    if ($_SESSION['activeServerId'] != APP_SERVER_ID) {
        echo 'Remote compose management is not supported. Please do that on the Dockwatch instance directly.';
    } else {
        ?>
        <ol class="breadcrumb rounded p-1 ps-2">
            <li class="breadcrumb-item"><a href="#" onclick="initPage('overview')"><?= $_SESSION['activeServerName'] ?></a><span class="ms-2">↦</span></li>
            <li class="breadcrumb-item active" aria-current="page">Compose</li>
        </ol>
        <div class="bg-secondary rounded p-4">
            <div class="row">
                <div class="col-sm-12">
                    <div id="compose-table-buttons" class="d-none">
                        <input id="compose-add-btn" class="btn btn-outline-success dt-button mt-2 buttons-collection access-rw" type="button" value="Add Compose" tabindex="0" aria-controls="compose-table" onclick="openComposeAdd()">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-no-squish" id="compose-table">
                            <thead>
                                <tr>
                                    <th scope="col" class="rounded-top-left-1 bg-primary ps-3 container-table-header noselect">Stack</th>
                                    <th scope="col" class="bg-primary ps-3 container-table-header noselect hide-mobile">Added</th>
                                    <th scope="col" class="bg-primary ps-3 container-table-header noselect hide-mobile">Updated</th>
                                    <th scope="col" class="bg-primary ps-3 container-table-header noselect hide-mobile">Location</th>
                                    <th scope="col" class="bg-primary ps-3 container-table-header noselect hide-mobile">Containers</th>
                                    <th scope="col" class="bg-primary ps-3 container-table-header noselect no-sort text-center hide-mobile">docker-compose</th>
                                    <th scope="col" class="text-center rounded-top-right-1 bg-primary ps-3 container-table-header noselect no-sort">Controls</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($composeFiles) {
                                    foreach ($composeFiles as $composeFile) {
                                        $runningCount = 0;
                                        $stoppedCount = 0;

                                        if ($composeFile['composeExists']) {
                                            $composePs       = sprintf(DockerSock::COMPOSE_PS, $composeFile['path'], '-a');
                                            $composePsOutput = $shell->exec($composePs . ' 2>&1');

                                            if (!str_contains($composePsOutput, 'Error')) {
                                                foreach (array_filter(explode("\n", trim($composePsOutput))) as $composePsLine) {
                                                    $composePsRow = json_decode($composePsLine, true);

                                                    if (!is_array($composePsRow)) {
                                                        continue;
                                                    }

                                                    $state = strtolower($composePsRow['State'] ?? '');
                                                    if ($state == 'running') {
                                                        $runningCount++;
                                                    } else {
                                                        $stoppedCount++;
                                                    }
                                                }
                                            }
                                        }

                                        $composePathEscaped = htmlspecialchars($composeFile['path'], ENT_QUOTES);
                                        $addedLabel         = $composeFile['added'] ? date('m/d/Y h:i A', $composeFile['added']) : '';
                                        $updatedLabel       = $composeFile['updated'] ? date('m/d/Y h:i A', $composeFile['updated']) : '';
                                        ?>
                                        <tr>
                                            <td class="container-table-row bg-secondary">
                                                <span><?= htmlspecialchars($composeFile['name']) ?></span>
                                                <?php if (!$composeFile['composeExists']) { ?>
                                                    <br><span class="small-text text-warning">docker-compose.yml missing</span>
                                                <?php } ?>
                                            </td>
                                            <td class="container-table-row bg-secondary hide-mobile" data-sort="<?= $composeFile['added'] ?>">
                                                <span class="small-text text-muted"><?= $addedLabel ?></span>
                                            </td>
                                            <td class="container-table-row bg-secondary hide-mobile" data-sort="<?= $composeFile['updated'] ?>">
                                                <span class="small-text text-muted"><?= $updatedLabel ?></span>
                                            </td>
                                            <td class="container-table-row bg-secondary hide-mobile">
                                                <span class="small-text text-muted"><?= htmlspecialchars($composeFile['path']) ?></span>
                                            </td>
                                            <td class="container-table-row bg-secondary hide-mobile">
                                                <div class="d-flex flex-row align-items-start gap-3">
                                                    <div class="small-text text-muted text-center">
                                                        <span class="d-block"><?= $runningCount + $stoppedCount ?></span>
                                                    </div>
                                                    <?php if ($composeFile['composeExists']) { ?>
                                                        <div class="small-text text-muted border-start ps-3">
                                                            <span class="d-block">Running: <?= $runningCount ?></span>
                                                            <span class="d-block<?= $stoppedCount > 0 ? ' text-danger' : '' ?>">Stopped: <?= $stoppedCount ?></span>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </td>
                                            <td class="container-table-row bg-secondary text-center hide-mobile">
                                                <?php if ($composeFile['composeExists']) { ?>
                                                    <input type="button" class="btn btn-outline-info" value="ps" onclick="compose('<?= $composePathEscaped ?>', 'composePs')">
                                                    <input type="button" class="btn btn-outline-info" value="logs" onclick="compose('<?= $composePathEscaped ?>', 'composeLogs')">
                                                    <input type="button" class="btn btn-outline-info" value="stop" onclick="compose('<?= $composePathEscaped ?>', 'composeStop')">
                                                    <input type="button" class="btn btn-outline-info" value="down" onclick="compose('<?= $composePathEscaped ?>', 'composeDown')">
                                                    <input type="button" class="btn btn-outline-info" value="restart" onclick="compose('<?= $composePathEscaped ?>', 'composeRestart')">
                                                    <input type="button" class="btn btn-outline-info" value="pull" onclick="compose('<?= $composePathEscaped ?>', 'composePull')">
                                                    <input type="button" class="btn btn-outline-info" value="up -d" onclick="compose('<?= $composePathEscaped ?>', 'composeUp')">
                                                <?php } else { ?>
                                                    <span class="small-text text-muted">docker-compose.yml is missing</span>
                                                <?php } ?>
                                            </td>
                                            <td class="container-table-row bg-secondary text-center">
                                                <?php if ($composeFile['composeExists']) { ?>
                                                    <input type="button" class="btn btn-outline-warning access-rw" value="Modify" onclick="composeModify('<?= $composePathEscaped ?>')">
                                                    <input type="button" class="btn btn-outline-danger access-rww" value="Delete" onclick="composeDelete('<?= $composePathEscaped ?>')">
                                                <?php } ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td class="container-table-row bg-secondary text-center text-muted" colspan="7">No compose projects yet. Click Add Compose to create one.</td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td class="rounded-bottom-right-1 rounded-bottom-left-1 bg-primary ps-3" colspan="7"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

if ($_POST['m'] == 'composeSave') {
    if (substr($_POST['composePath'], 0, strlen(COMPOSE_PATH)) == COMPOSE_PATH) {
        $path = $_POST['composePath'] . '/docker-compose.yml';

        file_put_contents($path, rawurldecode($_POST['compose']));

        $yqOutput     = $shell->exec('yq eval "." ' . escapeshellarg($path) . ' 2>&1');
        $yqResult     = str_contains($yqOutput, 'Error') ? $yqOutput : '0';
        $dockerOutput = $shell->exec('docker compose -f ' . escapeshellarg($path) . ' config 2>&1');
        $dockerResult = !str_contains($dockerOutput, 'name') ? $dockerOutput : '0';

        if ($yqResult === '0' && $dockerResult === '0') {
            $compose = file_get_contents($path);
            /** @disregard */
            $syntaxHighlighted = $phiki->codeToHtml($compose, Phiki\Grammar\Grammar::Yaml, Phiki\Theme\Theme::GithubDark);

            echo $syntaxHighlighted;
        } else {
            echo 'Failed to save file.<br>Error: ' . htmlspecialchars($yqResult !== '0' ? preg_replace('/^Error:\s*/', '', $yqResult) : $dockerResult);
        }
    }
}

if ($_POST['m'] == 'composeModify') {
    $path    = $_POST['composePath'] . '/docker-compose.yml';
    $compose = file_get_contents($path);
    /** @disregard */
    $syntaxHighlighted = $phiki->codeToHtml($compose, Phiki\Grammar\Grammar::Yaml, Phiki\Theme\Theme::GithubDark);

    ?>
    <code><?= $_POST['composePath'] ?>/docker-compose.yml</code><br>
    <div id="compose-data-preview" onclick="$('#compose-data').show(); $(this).hide();"><?= $syntaxHighlighted ?></div>
    <textarea id="compose-data" class="form-control" rows="15" style='display: none;'><?= $compose ?></textarea><br>
    <center><input type="button" class="btn btn-outline-success" value="Save file" onclick="composeSave('<?= $_POST['composePath'] ?>', $('#compose-data').val())"></center>
    <?php
}

if ($_POST['m'] == 'composeAddForm') {
    ?>
    <span class="small-text"><code><?= COMPOSE_PATH ?>{name}/docker-compose.yml</code></span>
    <div class="row mt-3">
        <div class="col-12 mb-3">
            <label class="form-label">Name</label>
            <input id="new-compose-name" class="form-control" type="text" placeholder="dockwatch">
        </div>
        <div class="col-12 mb-3">
            <label class="form-label">Compose</label>
            <textarea id="new-compose-data" class="form-control" rows="10" placeholder="<?= htmlspecialchars($composeExample) ?>"></textarea>
        </div>
        <div class="col-12 text-center">
            <input class="btn btn-outline-success access-rw" type="button" value="Add Compose" onclick="composeAdd()">
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'composeAdd') {
    $path = COMPOSE_PATH . preg_replace('/[^ \w]+/', '', $_POST['name']);
    createDirectoryTree($path);
    file_put_contents($path . '/docker-compose.yml', rawurldecode($_POST['compose']));
}

if ($_POST['m'] == 'composeDelete') {
    if (substr($_POST['composePath'], 0, strlen(COMPOSE_PATH)) == COMPOSE_PATH) {
        $shell->exec('rm -rf ' . $_POST['composePath']);
    }
}

if ($_POST['m'] == 'composePull') {
    $cmd  = sprintf(DockerSock::COMPOSE_PULL, $_POST['composePath']);
    $pull = $shell->exec($cmd . ' 2>&1');

    if (str_contains_all($pull, ['Pulling', 'Pulled'])) {
        echo 'pulled';
    } else {
        echo $pull;
    }
}

if ($_POST['m'] == 'composeUp') {
    $cmd = sprintf(DockerSock::COMPOSE_UP, $_POST['composePath']);
    $up  = $shell->exec($cmd . ' 2>&1');

    if (str_contains_all($up, ['Container', 'Started']) || str_contains_all($up, ['Container', 'Running'])) {
        echo 'up';
    } else {
        echo $up;
    }
}

if ($_POST['m'] == 'composeStop') {
    $cmd  = sprintf(DockerSock::COMPOSE_STOP, $_POST['composePath']);
    $stop = $shell->exec($cmd . ' 2>&1');

    if (str_contains($stop, 'Stopped') || str_contains($stop, 'done')) {
        echo 'stopped';
    } else {
        echo $stop;
    }
}

if ($_POST['m'] == 'composeDown') {
    $cmd  = sprintf(DockerSock::COMPOSE_DOWN, $_POST['composePath']);
    $down = $shell->exec($cmd . ' 2>&1');

    if (str_contains_all($down, ['Container', 'Stopped'])) {
        echo 'down';
    } else {
        echo $down;
    }
}

if ($_POST['m'] == 'composeRestart') {
    $cmd     = sprintf(DockerSock::COMPOSE_RESTART, $_POST['composePath']);
    $restart = $shell->exec($cmd . ' 2>&1');

    if (str_contains($restart, 'Started') || str_contains($restart, 'Restart')) {
        echo 'restarted';
    } else {
        echo $restart;
    }
}

if ($_POST['m'] == 'composePs') {
    $cmd    = sprintf(DockerSock::COMPOSE_PS, $_POST['composePath'], '-a');
    $output = $shell->exec($cmd . ' 2>&1');

    if (str_contains($output, 'Error') || str_contains($output, 'error')) {
        echo '<pre class="small-text text-muted mb-0">' . htmlspecialchars($output) . '</pre>';
    } else {
        $rows = [];
        foreach (array_filter(explode("\n", trim($output))) as $line) {
            $row = json_decode($line, true);
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        if (!$rows) {
            echo '<p class="text-muted mb-0">No compose containers found.</p>';
        } else {
            ?>
            <table class="table table-sm table-no-squish mb-0">
                <thead>
                    <tr>
                        <th class="rounded-top-left-1 bg-primary ps-3">Name</th>
                        <th class="bg-primary ps-3">Service</th>
                        <th class="bg-primary ps-3">State</th>
                        <th class="rounded-top-right-1 bg-primary ps-3">Ports</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($rows as $row) {
                        $name    = htmlspecialchars($row['Name'] ?? '');
                        $service = htmlspecialchars($row['Service'] ?? '');
                        $state   = htmlspecialchars($row['State'] ?? ($row['Status'] ?? ''));
                        $ports   = array_filter(array_map('trim', explode(',', $row['Ports'] ?? '')));
                        ?>
                        <tr>
                            <td class="bg-secondary"><?= $name ?></td>
                            <td class="bg-secondary"><?= $service ?></td>
                            <td class="bg-secondary"><?= $state ?></td>
                            <td class="bg-secondary">
                                <?php
                                if ($ports) {
                                    foreach ($ports as $port) {
                                        ?><span class="d-block small-text text-muted"><?= htmlspecialchars($port) ?></span><?php
                                    }
                                } else {
                                    ?><span class="text-muted">-</span><?php
                                }
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            <?php
        }
    }
}

if ($_POST['m'] == 'composeLogs') {
    $cmd = sprintf(DockerSock::COMPOSE_LOGS, $_POST['composePath']);
    echo $shell->exec($cmd . ' 2>&1');
}
