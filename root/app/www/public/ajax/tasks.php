<?php

/*
----------------------------------
 ------  Created: 112223   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    ?>
    <div class="container-fluid pt-4 px-4">
        <div class="bg-secondary rounded h-100 p-4">
            <div class="row">
                <div class="col-sm-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Interval</th>
                                <th>Execute</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>State Changes</td>
                                <td>5m</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('state')"></i></td>
                            </tr>
                            <tr>
                                <td>Housekeeping</td>
                                <td>10m</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('housekeeper')"></i></td>
                            </tr>
                            <tr>
                                <td>Pulls</td>
                                <td>1h</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('pulls')"></i></td>
                            </tr>
                            <tr>
                                <td>Prune</td>
                                <td>24h</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('prune')"></i></td>
                            </tr>
                        </tbody>
                    </table>
                    <br clear="all">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Execute</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Server Variables</td>
                                <td align="center"><i class="fas fa-hourglass-start text-info" style="cursor: pointer;" onclick="runTask('server')"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-sm-9"><pre id="taskViewer" style="max-height: 500px; overflow: auto;">Select a task</pre></div>
            </div>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'runTask') {
    logger($systemLog, 'Run task: ' . $_POST['task'], 'info');

    switch ($_POST['task']) {
        case 'state':
        case 'housekeeper':
        case 'pulls':
        case 'prune':
            $cmd = '/usr/bin/php ' . ABSOLUTE_PATH . 'crons/' . $_POST['task'] . '.php';
            echo shell_exec($cmd . ' 2>&1');
            break;
        case 'server':
            echo 'cli:<br>';
            echo '/usr/bin/php -r \'print_r($_SERVER);\'<br><br>';
            echo 'browser:<br>';
            print_r($_SERVER);
            break;
    }
}
