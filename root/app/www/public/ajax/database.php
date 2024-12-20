<?php

/*
----------------------------------
 ------  Created: 121824   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $defines = get_defined_constants();
    $tables = [];

    foreach ($defines as $define => $value) {
        if (str_contains($define, '_TABLE')) {
            $tables[] = $value;
        }
    }
    sort($tables);

    foreach ($tables as $table) {
        $q = "SELECT sql 
              FROM sqlite_schema 
              WHERE name = '" . $table . "'";
        $r = $database->query($q);
        $schema = $database->fetchAssoc($r);

        $rows = [];
        $q = "SELECT *
              FROM '" . $table . "'";
        $r = $database->query($q);
        while ($row = $database->fetchAssoc($r)) {
            $rows[] = $row;
        }
        ?>
        <div class="rounded bg-secondary mb-3">
            <div class="h5 p-2 text-primary"><?= $table ?></div><hr>
            <table class="table table-hover">
                <tr>
                    <td style="width:10%;">Schema</td>
                    <td><pre><?= str_replace('        ', '    ', str_replace('        )', ')', $schema['sql'])) ?></pre></td>
                </tr>
                <tr>
                    <td>Data</td>
                    <td>
                        <code><?= $q ?></code><br>
                        <?php
                        if ($rows) {
                            $fields = [];
                            foreach ($rows[0] as $field => $data) {
                                $fields[] = $field;
                            }

                            ?>
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <?php
                                        foreach ($fields as $field) {
                                            ?>
                                            <td><?= $field ?></td>
                                            <?php
                                        }
                                        ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($rows as $row) {
                                        ?><tr><?php
                                        foreach ($fields as $field) {
                                            ?><td><?= $row[$field] ?></td><?php
                                        }
                                        ?></tr><?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        <?php } else { ?>
                            Table is currently empty
                        <?php } ?>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
}
