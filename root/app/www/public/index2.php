<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

// This will NOT report uninitialized variables
error_reporting(E_ERROR | E_PARSE);

require 'loader.php';
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= $lightDark ?>">
<head>
    <meta charset="utf-8">
    <title></title>
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
    <link href="themes/<?= USER_THEME ?>.min.css" rel="stylesheet">

    <!-- Datatable Stylesheet -->
    <link href="libraries/datatable/datatables.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">

    <script type="text/javascript">
        const DEFAULT_PAGE = '<?= $settingsTable['defaultPage'] ?: 'overview' ?>';
        const CURRENT_PAGE = '<?= $settingsTable['currentPage'] && in_array($settingsTable['currentPage'], $pages) ? $settingsTable['currentPage'] : 'overview' ?>';
        let USE_SSE = <?= $settingsTable['sseEnabled'] ? 'true' : 'false' ?>;
        const SSE_SETTING = <?= intval($settingsTable['sseEnabled']) ?>;
        const APP_SERVER_ID = <?= APP_SERVER_ID ?>;
    </script>
</head>

<body id="body-pd" data-bs-theme="<?= USER_THEME_MODE ?>">
    <header class="header" id="header">
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
                    <a href="#" onclick="initPage('overview')" class="nav_link active"><i class="fas fa-heartbeat fa-fw nav_icon"></i><span class="nav_name">Overview</span></a> 
                    <a href="#" onclick="initPage('containers')" class="nav_link"><i class="fas fa-th fa-fw nav_icon"></i><span class="nav_name">Containers</span></a>
                    <a href="#" onclick="initPage('network')" class="nav_link"><i class="fas fa-network-wired fa-fw nav_icon"></i><span class="nav_name">Networks</span></a>
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
    <div style="height:2000px;">
        <p>content stuff here...</p>
    </div>
    <footer id="footer" class="footer fixed-bottom">
        <div class="container border border-bottom-0 border-start-0 border-end-0 border-secondary">
            <div id="footer-content" class="row">
                <div class="col-sm-12 col-lg-4 text-center">
                    Branch: <?= gitBranch() ?>, Hash: <a href="https://github.com/Notifiarr/dockwatch/commit/<?= gitHash() ?>" target="_blank" class="text-info"><?= substr(gitHash(), 0, 7) ?></a><br>
                </div>
                <div class="col-sm-12 col-lg-4 text-center">
                    <a href="https://dockwatch.wiki" target="_blank" title="Visit the <?= APP_NAME ?> wiki"><i class="fab fa-wikipedia-w fa-lg btn-secondary me-2"></i></a>
                    <a href="https://github.com/Notifiarr/dockwatch" title="Visit the <?= APP_NAME ?> github" target="_blank"><i class="fab fa-github fa-alg btn-secondary me-2"></i></a>
                    <a href="https://notifiarr.com/discord" title="Get some help if the wiki does not cover it" target="_blank"><i class="fab fa-discord fa-lg btn-secondary"></i></a>
                </div>
                <div class="col-sm-12 col-lg-4 text-center">
                    <span class="text-muted">Themes by <a href="https://bootswatch.com/" target="_blank">Bootswatch</a></span>
                    <?php
                    $themes = getThemes();
                    ?>
                    <select class="form-select d-inline-block w-50" onchange="updateSetting('defaultTheme', $(this).val());">
                        <?php
                        foreach ($themes as $theme) {
                            ?><option <?= $theme == USER_THEME ? 'selected' : '' ?> value="<?= $theme ?>"><?= $theme ?></option><?php
                        }
                        ?>
                    </select>
                    <!-- 
                        | <i class="fas fa-stopwatch" onclick="$('#loadtime-debug').toggle()"></i> 
                        | <i title="Clear session" class="fas fa-sign-out-alt" onclick="resetSession()"></i></span> 
                    -->
                </div>
            </div>
        </div>
    </footer>
</body>

<!-- JavaScript Libraries -->
<script src="libraries/jquery/jquery-3.4.1.min.js"></script>
<script src="libraries/jquery/jquery-ui-1.13.2.min.js"></script>
<script src="libraries/bootstrap/bootstrap.bundle.min.js"></script>
<script src="libraries/datatable/datatables.min.js"></script>
<script src="libraries/kpopup/kpopup.js"></script>
<script src="libraries/chart/chart.umd.min.js"></script>
<script src="libraries/chart/chartjs.plugin.datalabels.min.js"></script>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function(event) {
        const showNavbar = (toggleId, navId, bodyId, headerId) => {
            const toggle    = document.getElementById(toggleId),
            nav             = document.getElementById(navId),
            bodypd          = document.getElementById(bodyId),
            headerpd        = document.getElementById(headerId)

            if (toggle && nav && bodypd && headerpd) {
                toggle.addEventListener('click', () => {
                    nav.classList.toggle('show')
                    toggle.classList.toggle('bx-x')
                    bodypd.classList.toggle('body-pd')
                    headerpd.classList.toggle('body-pd')
                })
            }
        }
       
        showNavbar('header-toggle', 'nav-bar', 'body-pd', 'header')

        const linkColor = document.querySelectorAll('.nav_link')

        function colorLink() {
            if (linkColor) {
                linkColor.forEach(l => l.classList.remove('active'))
                this.classList.add('active')
            }
        }

        linkColor.forEach(l => l.addEventListener('click', colorLink))
    });
</script>

<!-- Javascript -->
<?= loadJS() ?>
