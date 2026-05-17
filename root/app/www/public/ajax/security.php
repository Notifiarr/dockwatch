<?php
/*
----------------------------------
 ------  Created: 021826   ------
 ------       nzxl         ------
----------------------------------
*/

require 'shared.php';

$security = new Security();

if ($_POST['m'] == 'init') {
    $containerList = apiRequest('stats/containers')['result']['result'];
    ?>
    <ol class="breadcrumb rounded p-1 ps-2">
        <li class="breadcrumb-item"><a href="#" onclick="initPage('overview')"><?= $_SESSION['activeServerName'] ?></a><span class="ms-2">↦</span></li>
        <li class="breadcrumb-item active" aria-current="page">Security</li>
    </ol>
    <div class="bg-secondary rounded p-4">
        <div class="row">
            <div class="col-sm-12">
                <div class="table-responsive">
                    <table class="table table-no-squish" id="security-table">
                        <thead>
                            <tr>
                                <th scope="col" class="rounded-top-left-1 bg-primary ps-3 container-table-header noselect no-sort"></th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect no-sort"></th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect">Container</th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect text-center">Critical</th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect text-center">High</th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect text-center">Medium</th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect text-center">Low</th>
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect no-sort">Last Scan</th>
                                <th scope="col" class="rounded-top-right-1 bg-primary ps-3 container-table-header noselect no-sort">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($containerList as $container) {
                                $imageName     = $container['image'];
                                $containerName = $container['name'];
                                $containerHash = md5($containerName);
                                $isDockwatch   = isDockwatchContainer($container);
                                $iconUrl       = getIconByName($imageName, $containerName);
                                $vulnCounts    = $security->getVulnCounts($imageName);
                                $scanCount     = $security->getScanHistoryCount($imageName);
                                $hasHistory    = $scanCount > 1;
                                ?>
                                <tr id="security-row-<?= $containerHash ?>" data-hash="<?= $containerHash ?>" data-image="<?= htmlspecialchars($imageName) ?>" data-name="<?= htmlspecialchars($containerName) ?>" data-has-history="<?= $hasHistory ? '1' : '0' ?>">
                                    <td class="container-table-row bg-secondary"><input type="checkbox" class="form-check-input security-check" onchange="toggleSecurityCheckAll()"></td>
                                    <td class="container-table-row bg-secondary">
                                        <?php if ($iconUrl) { ?>
                                            <img src="<?= htmlspecialchars($iconUrl) ?>" width="32" height="32" alt="" />
                                        <?php } ?>
                                    </td>
                                    <td class="container-table-row bg-secondary">
                                        <span class="container-name"><?= htmlspecialchars($containerName) ?></span>
                                        <br>
                                        <span class="text-muted small-text"><?= htmlspecialchars($imageName) ?></span>
                                    </td>
                                    <td class="container-table-row bg-secondary text-center" data-sort="<?= $vulnCounts['critical'] ?>">
                                        <?php if ($vulnCounts['critical'] > 0) { ?>
                                            <span class="badge bg-danger"><?= $vulnCounts['critical'] ?></span>
                                        <?php } else { ?>
                                            <span class="text-muted">0</span>
                                        <?php } ?>
                                    </td>
                                    <td class="container-table-row bg-secondary text-center" data-sort="<?= $vulnCounts['high'] ?>">
                                        <?php if ($vulnCounts['high'] > 0) { ?>
                                            <span class="badge bg-warning text-dark"><?= $vulnCounts['high'] ?></span>
                                        <?php } else { ?>
                                            <span class="text-muted">0</span>
                                        <?php } ?>
                                    </td>
                                    <td class="container-table-row bg-secondary text-center" data-sort="<?= $vulnCounts['medium'] ?>">
                                        <?php if ($vulnCounts['medium'] > 0) { ?>
                                            <span class="badge bg-info text-dark"><?= $vulnCounts['medium'] ?></span>
                                        <?php } else { ?>
                                            <span class="text-muted">0</span>
                                        <?php } ?>
                                    </td>
                                    <td class="container-table-row bg-secondary text-center" data-sort="<?= $vulnCounts['low'] ?>">
                                        <?php if ($vulnCounts['low'] > 0) { ?>
                                            <span class="badge bg-secondary"><?= $vulnCounts['low'] ?></span>
                                        <?php } else { ?>
                                            <span class="text-muted">0</span>
                                        <?php } ?>
                                    </td>
                                    <td class="container-table-row bg-secondary">
                                        <?php if ($vulnCounts['lastScan']) { ?>
                                            <span class="text-muted"><?= date('m/d/Y h:i A', $vulnCounts['lastScan']) ?></span>
                                        <?php } else { ?>
                                            <span class="text-muted">Never</span>
                                        <?php } ?>
                                        <?php if ($hasHistory) { ?>
                                            <div class="text-center"><span class="small-text text-info security-history-toggle" onclick="toggleSecurityScans('<?= $containerHash ?>')">History</span></div>
                                        <?php } ?>
                                    </td>
                                    <td class="container-table-row bg-secondary">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-light bg-secondary btn-sm" title="View Current Scan" onclick="viewSecurityScan('<?= $containerHash ?>', '<?= htmlspecialchars($imageName) ?>', 'current')"><i class="fas fa-eye fa-xs"></i></button>
                                            <button type="button" class="btn btn-outline-light bg-secondary btn-sm" title="View Scan History" onclick="viewSecurityScanHistory('<?= $containerHash ?>', '<?= htmlspecialchars($imageName) ?>')"><i class="fas fa-history fa-xs"></i></button>
                                            <button type="button" class="btn btn-outline-light bg-secondary btn-sm access-rw" title="Run Scan" onclick="runSecurityScan('<?= $containerHash ?>', '<?= htmlspecialchars($imageName) ?>', '<?= htmlspecialchars($containerName) ?>')"><i class="fas fa-shield-alt fa-xs"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <tfoot>
                            <tr>
                                <td class="rounded-bottom-right-1 rounded-bottom-left-1 bg-primary ps-3" colspan="9">
                                    <button id="check-all-security-btn" class="dt-button mt-2 buttons-collection access-rw" tabindex="0" aria-controls="security-table" type="button"><input type="checkbox" class="form-check-input" onclick="toggleAllSecurity()" id="security-toggle-all"></button>
                                    <button id="security-scan-btn" class="dt-button mt-2 buttons-collection access-rw" tabindex="0" aria-controls="security-table" type="button" onclick="massApplySecurityScan()">Scan Selected</button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'getVulns') {
    echo json_encode($security->getVulns($_POST['image'], $_POST['file']) ?? []);
}

if ($_POST['m'] == 'getScanHistory') {
    echo json_encode($security->getScanHistory($_POST['image']));
}

if ($_POST['m'] == 'getRecentScans') {
    $limit   = intval($_POST['limit'] ?? 3);
    $history = $security->getScanHistory($_POST['image']);
    $recent  = array_slice($history, 1, $limit);

    echo json_encode($recent);
}

if ($_POST['m'] == 'runScan') {
    logger(UI_LOG, 'scanning image ' . $_POST['image'] . ' ->');

    $result = $security->scanImage($_POST['image'], intval($settingsTable['securityScanner']), $settingsTable['securitySnykAPIKey']);

    if (!empty($result)) {
        logger(UI_LOG, $result);
    }

    logger(UI_LOG, 'scanning image ' . $_POST['image'] . ' <-');

    echo json_encode([
        'success' => true,
        'result'  => $result,
        'counts'  => $security->getVulnCounts($_POST['image']),
    ]);
}
