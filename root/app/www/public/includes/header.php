<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

$_SESSION['IN_DOCKWATCH'] = true;
$currentPage = $settingsTable['currentPage'] && in_array($settingsTable['currentPage'], $pages) ? $settingsTable['currentPage'] : 'overview';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?= APP_NAME ?><?= $settingsTable['serverName'] ? ' - ' . $settingsTable['serverName'] : '' ?></title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">

        <!-- Favicon -->
        <link href="images/logo.ico" rel="icon">

        <!-- Google Web Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Roboto:wght@500;700&display=swap" rel="stylesheet">

        <!-- Icon Font Stylesheet -->
        <link href="libraries/fontawesome/all.min.css" rel="stylesheet">
        <link href="libraries/bootstrap/bootstrap-icons.css" rel="stylesheet">

        <!-- Bootstrap Stylesheet -->
        <link href="libraries/bootstrap/bootstrap.min.css" rel="stylesheet">

        <!-- Customized Bootstrap Stylesheet -->
        <link href="themes/base.css?t=<?= filemtime('themes/base.css') ?>" rel="stylesheet">
        <link href="themes/<?= USER_THEME ?>.min.css?t=<?= filemtime('themes/' . USER_THEME . '.min.css') ?>" rel="stylesheet">

        <!-- Datatable Stylesheet -->
        <link href="libraries/datatable/datatables.min.css" rel="stylesheet">

        <!-- Misc Stylesheet -->
        <link href="css/style.css?t=<?= filemtime('css/style.css') ?>" rel="stylesheet">

        <script type="text/javascript">
            const DEFAULT_PAGE = '<?= $settingsTable['defaultPage'] ?: 'overview' ?>';
            const CURRENT_PAGE = '<?= $currentPage ?>';
            let USE_SSE = <?= $settingsTable['sseEnabled'] ? 'true' : 'false' ?>;
            const SSE_SETTING = <?= intval($settingsTable['sseEnabled']) ?>;
            const APP_SERVER_ID = <?= APP_SERVER_ID ?>;

            document.addEventListener('DOMContentLoaded', function(event) {
                const showNavbar = (toggleId, navId, bodyId, headerId) => {
                    const toggle    = document.getElementById(toggleId),
                    nav             = document.getElementById(navId),
                    bodypd          = document.getElementById(bodyId),
                    headerpd        = document.getElementById(headerId)

                    if (toggle && nav && bodypd && headerpd) {
                        toggle.addEventListener('click', () => {
                            nav.classList.toggle('show-navbar')
                            toggle.classList.toggle('bx-x')
                            bodypd.classList.toggle('body-pd')
                            headerpd.classList.toggle('body-pd')
                        })
                    }
                }

                showNavbar('header-toggle', 'nav-bar', 'body-pd', 'header', 'footer')

                const linkColor = document.querySelectorAll('.nav_link')

                function colorLink() {
                    if (linkColor) {
                        linkColor.forEach(l => l.classList.remove('active'))
                        this.classList.add('active')
                    }
                }

                linkColor.forEach(l => {
                    if (l.onclick.toString().match(/initPage\('(.+)'\)/)[1] == DEFAULT_PAGE) l.classList.add('active')
                    l.addEventListener('click', colorLink)
                })
            });
        </script>
    </head>

    <body id="body-pd" data-bs-theme="<?= USER_THEME_MODE ?>">
        <div id="spinner" class="show bg-dark position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-info" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>

        <header class="header bg-body" id="header">
            <div class="header_toggle"><i class="fas fa-bars" id="header-toggle"></i></div>
            <div class="header_img">
                <a class="nav-link dropdown-toggle d-flex align-items-center text-secondary" href="#" id="theme-menu" aria-expanded="false" data-bs-toggle="dropdown" data-bs-display="static" aria-label="Toggle theme">
                    <i class="fas fa-cloud-sun"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light" onclick="swapLightDark('light')">
                            <i class="bi bi-sun-fill"></i><span class="ms-2">Light</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark" onclick="swapLightDark('dark')">
                            <i class="bi bi-moon-stars-fill"></i><span class="ms-2">Dark</span>
                        </button>
                    </li>
                </ul>
            </div>
        </header>
        <div class="l-navbar" id="nav-bar">
            <nav class="nav">
                <div>
                    <a href="#" class="nav_logo"><img src="images/logo.png" height="45"><span class="nav_logo-name">Dockwatch</span></a>
                    <div class="nav_list">
                        <a href="#" onclick="serverListToggle()" class="nav_servers_link"><i class="fas fa-server fa-fw nav_icon"></i><span class="nav_name">Servers</span></a>
                        <a href="#" onclick="initPage('overview')" class="nav_link"><i class="fas fa-heartbeat fa-fw nav_icon"></i><span class="nav_name">Overview</span></a>
                        <a href="#" onclick="initPage('containers')" class="nav_link"><i class="fas fa-th fa-fw nav_icon"></i><span class="nav_name">Containers</span></a>
                        <a href="#" onclick="initPage('networks')" class="nav_link"><i class="fas fa-network-wired fa-fw nav_icon"></i><span class="nav_name">Networks</span></a>
                        <a href="#" onclick="initPage('compose')" class="nav_link"><i class="fab fa-octopus-deploy fa-fw nav_icon"></i><span class="nav_name">Compose</span></a>
                        <a href="#" onclick="initPage('orphans')" class="nav_link"><i class="fab fa-dropbox fa-fw nav_icon"></i><span class="nav_name">Orphans</span></a>
                        <a href="#" onclick="initPage('notification')" class="nav_link"><i class="fas fa-comment-dots fa-fw nav_icon"></i><span class="nav_name">Notifications</span></a>
                        <a href="#" onclick="initPage('settings')" class="nav_link"><i class="fas fa-tools fa-fw nav_icon"></i><span class="nav_name">Settings</span></a>
                        <a href="#" onclick="initPage('tasks')" class="nav_link"><i class="fas fa-tasks fa-fw nav_icon"></i><span class="nav_name">Tasks</span></a>
                        <a href="#" onclick="initPage('commands')" class="nav_link"><i class="fab fa-docker fa-fw nav_icon"></i><span class="nav_name">Commands</span></a>
                        <a href="#" onclick="initPage('logs')" class="nav_link"><i class="fas fa-file-code fa-fw nav_icon"></i><span class="nav_name">Logs</span></a>
                    </div>
                </div>
            </nav>
        </div>
        <div id="page-panel" style="margin-bottom:75px;">
