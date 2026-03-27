<?php
/*
----------------------------------
 ------  Created: 021826   ------
 ------       nzxl         ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $containerList = apiRequest('stats/containers')['result']['result'];
    $security      = new Security();
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
                                <th scope="col" class="bg-primary ps-3 container-table-header noselect no-sort"></th>
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
                                        <?php if ($iconUrl): ?>
                                            <img src="<?= htmlspecialchars($iconUrl) ?>" width="32" height="32" alt="" />
                                        <?php endif; ?>
                                    </td>
                                    <td class="container-table-row bg-secondary">
                                        <span class="container-name"><?= htmlspecialchars($containerName) ?></span>
                                        <br>
                                        <span class="text-muted small-text"><?= htmlspecialchars($imageName) ?></span>
                                    </td>
                                    <td class="container-table-row bg-secondary text-center">
                                        <?php if ($hasHistory): ?>
                                            <i class="fas fa-plus-square text-info security-expand" style="cursor: pointer;" onclick="toggleSecurityScans('<?= $containerHash ?>')"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="container-table-row bg-secondary text-center" data-sort="<?= $vulnCounts['critical'] ?>">
                                        <?php if ($vulnCounts['critical'] > 0): ?>
                                            <span class="badge bg-danger"><?= $vulnCounts['critical'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="container-table-row bg-secondary text-center" data-sort="<?= $vulnCounts['high'] ?>">
                                        <?php if ($vulnCounts['high'] > 0): ?>
                                            <span class="badge bg-warning text-dark"><?= $vulnCounts['high'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="container-table-row bg-secondary text-center" data-sort="<?= $vulnCounts['medium'] ?>">
                                        <?php if ($vulnCounts['medium'] > 0): ?>
                                            <span class="badge bg-info text-dark"><?= $vulnCounts['medium'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="container-table-row bg-secondary text-center" data-sort="<?= $vulnCounts['low'] ?>">
                                        <?php if ($vulnCounts['low'] > 0): ?>
                                            <span class="badge bg-secondary"><?= $vulnCounts['low'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="container-table-row bg-secondary">
                                        <?php if ($vulnCounts['lastScan']): ?>
                                            <span class="small-text text-muted"><?= date('Y-m-d H:i', $vulnCounts['lastScan']) ?></span>
                                        <?php else: ?>
                                            <span class="small-text text-muted">Never</span>
                                        <?php endif; ?>
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
                                <td class="rounded-bottom-right-1 rounded-bottom-left-1 bg-primary ps-3" colspan="10">
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
    $image    = $_POST['image'] ?? '';
    $file     = $_POST['file'] ?? null;
    $security = new Security();
    $vulns    = $security->getVulns($image, $file);
    echo json_encode($vulns ?? []);
}

if ($_POST['m'] == 'getScanHistory') {
    $image    = $_POST['image'] ?? '';
    $security = new Security();
    $history  = $security->getScanHistory($image);
    echo json_encode($history);
}

if ($_POST['m'] == 'getRecentScans') {
    $image    = $_POST['image'] ?? '';
    $limit    = intval($_POST['limit'] ?? 3);
    $security = new Security();
    $history  = $security->getScanHistory($image);
    $recent   = array_slice($history, 1, $limit);
    echo json_encode($recent);
}

if ($_POST['m'] == 'runScan') {
    $image = $_POST['image'] ?? '';
    $name  = $_POST['name'] ?? '';

    logger(UI_LOG, 'scanning image ' . $image . ' ->');

    $security = new Security();
    $result   = $security->scanImage($image, intval($settingsTable['securityScanner']), $settingsTable['securitySnykAPIKey']);

    if (!empty($result)) {
        logger(UI_LOG, $result);
    }

    logger(UI_LOG, 'scanning image ' . $image . ' <-');

    $vulnCounts = $security->getVulnCounts($image);
    echo json_encode([
        'success' => true,
        'result'  => $result,
        'counts'  => $vulnCounts,
    ]);
}
