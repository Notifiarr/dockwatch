<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

$fetchServers = false;
if (!$_SESSION['serverList'] || ($_SESSION['serverListUpdated'] + 300) < time()) {
    $fetchServers = true;
}

if ($fetchServers) {
    $serverList = '<select class="form-select w-75 d-inline-block" id="activeServer" onchange="updateServerIndex()">';
    foreach ($serversFile as $serverIndex => $serverDetails) {
        $ping = curl($serverDetails['url'] . '/api/?request=ping', ['x-api-key: ' . $serverDetails['apikey']], 'GET', '', [], 5);
        $disabled = '';
        if ($ping['code'] != 200) {
            $disabled = ' [HTTP: ' . $ping['code'] . ']';
        }
        $serverList .= '<option ' . ($disabled ? 'disabled ' : '') . ($_SESSION['serverIndex'] == $serverIndex ? 'selected' : '') . ' value="' . $serverIndex . '">' . $serverDetails['name'] . $disabled . '</option>';
        $link = $_SESSION['serverIndex'] == $serverIndex ? $serverDetails['url'] : $link;
    }
    $serverList .= '</select>';
    $serverList .= ' <a class="text-info" href="' . $link . '" target="_blank" title="Open this server in a new tab"><i id="external-server-icon" class="fas fa-external-link-alt fa-lg" style="display: ' . ($_SESSION['serverIndex'] == 0 ? 'none' : 'inline-block') . ';"></i></a>';

    $_SESSION['serverList']         = $serverList;
    $_SESSION['serverListUpdated']  = time();
} else {
    $serverList = $_SESSION['serverList'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dockwatch<?= ($settingsFile['global']['serverName'] ? ' - ' . $settingsFile['global']['serverName'] : '') ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="images/logo.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Roboto:wght@500;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="libraries/fontawesome/all.min.css" rel="stylesheet">
    <link href="libraries/bootstrap/bootstrap-icons.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="libraries/bootstrap/bootstrap.min.css" rel="stylesheet">

    <!-- Datatable Stylesheet -->
    <link href="libraries/datatable/datatables.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">

    <script type="text/javascript">
        const USE_EXTERNAL_LOADING = '<?= $settingsFile['global']['externalLoading'] && in_array($_GET['page'], $pages) ? $_GET['page'] : 'overview' ?>';
        const USE_SSE = <?= $settingsFile['global']['sseEnabled'] ? 'true' : 'false' ?>;
    </script>
</head>

<body>
    <div class="container-fluid position-relative d-flex p-0">
        <!-- Spinner Start -->
        <div id="spinner" class="show bg-dark position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <!-- Spinner End -->

        <!-- Sidebar Start -->
        <div class="sidebar pe-4 pb-3">
            <nav class="navbar bg-secondary navbar-dark">
                <a href="index.php" class="navbar-brand mx-4">
                    <h3 class="text-primary mb-0">Dockwatch</h3>
                </a>
                <div class="mb-4 w-100" align="center"><?= $serverList ?></div>
                <?php if ($_SESSION['authenticated']) { ?>
                <div class="navbar-nav w-100">
                    <a id="menu-overview" onclick="<?= $settingsFile['global']['externalLoading'] ? "window.location.href='?page=overview'" : "initPage('overview')" ?>" style="cursor: pointer;" class="nav-item nav-link active"><i class="fas fa-heartbeat me-2"></i>Overview</a>
                    <a id="menu-containers" onclick="<?= $settingsFile['global']['externalLoading'] ? "window.location.href='?page=containers'" : "initPage('containers')" ?>" style="cursor: pointer;" class="nav-item nav-link"><i class="fas fa-th me-2"></i>Containers</a>
                    <a id="menu-compose" onclick="<?= $settingsFile['global']['externalLoading'] ? "window.location.href='?page=compose'" : "initPage('compose')" ?>" style="cursor: pointer;" class="nav-item nav-link"><i class="fab fa-octopus-deploy me-2"></i>Compose</a>
                    <a id="menu-orphans" onclick="<?= $settingsFile['global']['externalLoading'] ? "window.location.href='?page=orphans'" : "initPage('orphans')" ?>" style="cursor: pointer;" class="nav-item nav-link"><i class="fas fa-th me-2"></i>Orphans</a>
                    <a id="menu-notification" onclick="<?= $settingsFile['global']['externalLoading'] ? "window.location.href='?page=notification'" : "initPage('notification')" ?>" style="cursor: pointer;" class="nav-item nav-link"><i class="fas fa-comment-dots me-2"></i>Notifications</a>
                    <a id="menu-settings" onclick="<?= $settingsFile['global']['externalLoading'] ? "window.location.href='?page=settings'" : "initPage('settings')" ?>" style="cursor: pointer;" class="nav-item nav-link"><i class="fas fa-tools me-2"></i>Settings</a>
                    <a id="menu-tasks" onclick="<?= $settingsFile['global']['externalLoading'] ? "window.location.href='?page=tasks'" : "initPage('tasks')" ?>" style="cursor: pointer;" class="nav-item nav-link"><i class="fas fa-tasks me-2"></i>Tasks</a>
                    <a id="menu-commands" onclick="<?= $settingsFile['global']['externalLoading'] ? "window.location.href='?page=commands'" : "initPage('commands')" ?>" style="cursor: pointer;" class="nav-item nav-link"><i class="fab fa-docker me-2"></i>Commands</a>
                    <a id="menu-logs" onclick="<?= $settingsFile['global']['externalLoading'] ? "window.location.href='?page=logs'" : "initPage('logs')" ?>" style="cursor: pointer;" class="nav-item nav-link"><i class="fas fa-file-code me-2"></i>Logs</a>
                    <?php if (USE_AUTH) { ?>
                    <a onclick="logout()" style="cursor: pointer;" class="nav-item nav-link"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                    <?php } ?>
                </div>
                <?php } ?>
                <div class="navbar-brand w-100 mb-1 text-center">
                    <a href="https://github.com/Notifiarr/dockwatch" target="_blank"><i class="fab fa-github btn-secondary me-2"></i></a>
                    <a href="https://notifiarr.com/discord" target="_blank"><i class="fab fa-discord btn-secondary"></i></a>
                </div>
            </nav>
            <div class="w-100 text-center small-text" style="position: absolute; bottom: 0;">
                Branch: <?= gitBranch() ?>, Hash: <a href="https://github.com/Notifiarr/dockwatch/commit/<?= gitHash() ?>" target="_blank" class="text-info"><?= substr(gitHash(), 0, 7) ?></a><br>
                <span class="text-muted">Theme By <a href="https://htmlcodex.com" target="_blank">HTML Codex</a> | <i class="fas fa-stopwatch" onclick="$('#loadtime-debug').toggle()"></i></span>
            </div>
        </div>
        <!-- Sidebar End -->

        <!-- Content Start -->
        <div class="content">
            <!-- Navbar Start -->
            <nav class="navbar navbar-expand bg-secondary navbar-dark sticky-top px-4 py-0">
                <a href="#" class="sidebar-toggler flex-shrink-0 m-2">
                    <i class="fa fa-bars"></i>
                </a>
                <div align="right" style="float: right;" class="w-100">
                    <img src="images/logo.png" height="65">
                </div>
            </nav>
            <!-- Navbar End -->

            <!-- App Start -->
            <div class="container-fluid pt-4 px-4">