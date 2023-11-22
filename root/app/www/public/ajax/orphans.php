<?php

/*
----------------------------------
 ------  Created: 112223   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $orphans = json_decode(dockerGetOrphans(), true);

    ?>
    <div class="container-fluid pt-4 px-4 mb-5">
        <div class="bg-secondary rounded h-100 p-4">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><input type="checkbox" class="form-check-input" onclick="$('.orphans-check').prop('checked', $(this).prop('checked'));"></th>
                            <th scope="col">ID</th>
                            <th scope="col">Created</th>
                            <th scope="col">Repository</th>
                            <th scope="col">Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($orphans as $orphan) {
                            ?>
                            <tr id="<?= $orphan['ID'] ?>">
                                <th scope="row"><input id="orphan-<?= $orphan['ID'] ?>" type="checkbox" class="form-check-input orphans-check"></th>
                                <td><?= $orphan['ID'] ?></td>
                                <td><?= $orphan['CreatedSince'] ?></td>
                                <td><?= $orphan['Repository'] ?></td>
                                <td><?= $orphan['Size'] ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">
                                With selected: 
                                <select id="massOrphanTrigger" class="form-control d-inline-block w-50">
                                    <option value="0">-- Select option --</option>
                                    <option value="1">Remove</option>
                                </select>
                                <button type="button" class="btn btn-outline-info" onclick="removeOrphans()">Apply</button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'removeOrphans') {
    switch ($_POST['trigger']) {
        case '1': //-- Remove
            $remove = dockerRemoveImage($_POST['orphan']);
            if (strpos($remove, 'Error') !== false) {
                echo $remove;
            }
            break;
    }
}
