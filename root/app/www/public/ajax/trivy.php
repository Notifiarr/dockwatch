<?php
/*
----------------------------------
 ------  Created: 021826   ------
 ------  nzxl       	   ------
----------------------------------
*/

require 'shared.php';

if ($_POST['m'] == 'init') {
    $containerList = apiRequest('stats/containers')['result']['result'];
    ?>
    <ol class="breadcrumb rounded p-1 ps-2">
        <li class="breadcrumb-item"><a href="#" onclick="initPage('overview')"><?= $_SESSION['activeServerName'] ?></a><span class="ms-2">↦</span></li>
        <li class="breadcrumb-item active" aria-current="page">Trivy</li>
    </ol>
    <div class="bg-secondary rounded p-4">
        <div class="mb-3">
            <div class="select-dropdown" id="container-selector" role="listbox" aria-label="Docker containers">
                <div class="search-wrapper" onclick="$('#container-selector').addClass('visible');">
                    <i class="fas fa-search text-gray"></i>
                    <input class="search-input" type="text" placeholder="Select a container..." id="container-search" aria-label="Search containers" readonly />
                </div>
                <div class="options-list" id="container-options">
                    <?php
                    foreach ($containerList as $container) {
                        $imageName     = $container['image'];
                        $containerName = $container['name'];
                        $iconUrl       = getIconByName($imageName, $containerName);
                        ?>
                        <div class="option-item" role="option" aria-selected="false" data-id="<?= htmlspecialchars($container['id']) ?>" data-name="<?= htmlspecialchars($containerName) ?>" data-image="<?= htmlspecialchars($imageName) ?>">
                            <div class="option-left">
                                <div class="container-icon">
                                    <?php if ($iconUrl): ?>
                                        <img src="<?= htmlspecialchars($iconUrl) ?>" width="20" height="20" alt="" />
                                    <?php endif; ?>
                                </div>
                                <div class="option-info">
                                    <span class="option-name"><?= htmlspecialchars($containerName) ?></span>
                                    <span class="option-image"><?= htmlspecialchars($imageName) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="trivy-content">
            <div class="table-responsive">
                <table class="table" id="trivy-table">
                    <thead>
                        <tr>
                            <th scope="col" class="rounded-top-left-1 bg-primary ps-3">Library</th>
                            <th scope="col" class="bg-primary ps-3">Vulnerability</th>
                            <th scope="col" class="bg-primary ps-3">Severity</th>
                            <th scope="col" class="bg-primary ps-3">Status</th>
                            <th scope="col" class="bg-primary ps-3">Installed</th>
                            <th scope="col" class="bg-primary ps-3">Fixed</th>
                            <th scope="col" class="rounded-top-right-1 bg-primary ps-3">Title</th>
                        </tr>
                    </thead>
                    <tbody id="trivy-tbody" class="container-table-row">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'getVulns') {
    $image = $_POST['image'] ?? '';
    $trivy = new Trivy();
    $vulns = $trivy->getVulns($image);
    header('Content-Type: application/json');
    echo json_encode($vulns ?? []);
}
