<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

$currentPage = $settingsTable['currentPage'] && in_array($settingsTable['currentPage'], $pages) ? $settingsTable['currentPage'] : 'overview';
$isLoginPage = str_contains($_SERVER['PHP_SELF'] ?? '', 'login.php');

if ($isLoginPage) {
    $_SESSION['IN_DOCKWATCH'] = true;
}
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

        <!-- xterm Stylesheet -->
        <link href="libraries/xterm/xterm.min.css" rel="stylesheet">

        <!-- Misc Stylesheet -->
        <link href="css/style.css?t=<?= filemtime('css/style.css') ?>" rel="stylesheet">

        <script type="text/javascript">
            const DEFAULT_PAGE = '<?= $settingsTable['defaultPage'] ?: 'overview' ?>';
            const CURRENT_PAGE = '<?= $currentPage ?>';
            let USE_SSE = <?= $settingsTable['sseEnabled'] ? 'true' : 'false' ?>;
            const SSE_SETTING = <?= intval($settingsTable['sseEnabled']) ?>;
            const APP_SERVER_ID = <?= APP_SERVER_ID ?>;
            const ACCESS_MODE = '<?= ACCESS_MODE ?>';
            const USE_AUTH = <?= USE_AUTH ? 'true' : 'false' ?>;
            const CSRF_TOKEN = '<?= USE_AUTH ? ($_SESSION['csrf_token'] ?? '') : '' ?>';

            document.addEventListener('DOMContentLoaded', function (event) {
                const showNavbar = (toggleId, navId, bodyId, headerId) => {
                    const toggle = document.getElementById(toggleId),
                        nav = document.getElementById(navId),
                        bodypd = document.getElementById(bodyId),
                        headerpd = document.getElementById(headerId)

                    let navbarState = getLocalStorage(['uiNavbarToggle'])
                    if (navbarState['uiNavbarToggle']['toggled']) {
                        nav.classList.add('show-navbar')
                        toggle.classList.add('bx-x')
                        bodypd.classList.add('body-pd')
                        headerpd.classList.add('body-pd')
                    }

                    if (toggle && nav && bodypd && headerpd) {
                        toggle.addEventListener('click', () => {
                            nav.classList.toggle('show-navbar')
                            toggle.classList.toggle('bx-x')
                            bodypd.classList.toggle('body-pd')
                            headerpd.classList.toggle('body-pd')

                            setLocalStorage('uiNavbarToggle', 'toggled', nav.classList.contains('show-navbar'))
                        })
                    }
                }

                showNavbar('header-toggle', 'nav-bar', 'body-pd', 'header', 'footer')
                setActiveNavLink(document.querySelectorAll('.nav_link'), DEFAULT_PAGE)
            });
        </script>
    </head>

    <body id="body-pd" class="<?= $isLoginPage ? 'login-page' : '' ?>" data-bs-theme="<?= USER_THEME_MODE ?>">
        <div id="spinner" class="show bg-dark position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-info" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>


        <?php if ($_SESSION['authenticated']) { ?>
            <header class="header bg-body" id="header">
                <div class="header_toggle" style="flex-grow: 1;"><i class="fas fa-bars" id="header-toggle"></i></div>
                <div class="header_shell" style="padding: 0 12px;">
                    <a class="nav-link d-flex align-items-center text-secondary" href="#" aria-expanded="false" aria-label="Open Dockwatch shell" onclick="containerShell('<?= getDockwatchContainerName() ?>')">
                        <i class="fas fa-terminal"></i>
                    </a>
                </div>
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
            <div class="l-navbar d-flex flex-column justify-content-between p-0 pt-2" id="nav-bar">
                <nav class="nav h-100 d-flex flex-column gap-1">
                    <div class="d-flex flex-column align-items-start grow">
                        <a href="#" class="nav_logo mx-auto">
                            <img src="images/logo.png" height="48">
                            <span class="nav_logo-name d-none d-xl-inline show-text">
                                Dockwatch
                                <sup><i class="fas fa-circle fa-xs <?= $accessModeClass ?>" title="<?= $accessModeHover ?>"></i></sup>
                            </span>
                        </a>
                        <div class="nav_list d-flex flex-column gap-1 align-items-start grow">
                            <a href="#" onclick="serverListToggle()" class="nav_servers_link access-rwx">
                                <i class="fas fa-server fa-fw nav_icon"></i>
                                <span class="nav_name d-none d-xl-inline show-text">Servers</span>
                            </a>
                            <a href="#" onclick="initPage('overview')" class="nav_link">
                                <i class="fas fa-heartbeat fa-fw nav_icon"></i>
                                <span class="nav_name d-none d-xl-inline show-text">Overview</span>
                            </a>
                            <a href="#" onclick="initPage('containers')" class="nav_link">
                                <i class="fas fa-th fa-fw nav_icon"></i>
                                <span class="nav_name d-none d-xl-inline show-text">Containers</span>
                            </a>
                            <a href="#" onclick="initPage('networks')" class="nav_link">
                                <i class="fas fa-network-wired fa-fw nav_icon"></i>
                                <span class="nav_name d-none d-xl-inline show-text">Networks</span>
                            </a>
                            <a href="#" onclick="initPage('compose')" class="nav_link access-rwx">
                                <i class="fab fa-octopus-deploy fa-fw nav_icon"></i>
                                <span class="nav_name d-none d-xl-inline show-text">Compose</span>
                            </a>
                            <a href="#" onclick="initPage('orphans')" class="nav_link">
                                <i class="fab fa-dropbox fa-fw nav_icon"></i>
                                <span class="nav_name d-none d-xl-inline show-text">Orphans</span>
                            </a>
                            <a href="#" onclick="initPage('notification')" class="nav_link">
                                <i class="fas fa-comment-dots fa-fw nav_icon"></i>
                                <span class="nav_name d-none d-xl-inline show-text">Notifications</span>
                            </a>
                            <a href="#" onclick="initPage('settings')" class="nav_link">
                                <i class="fas fa-tools fa-fw nav_icon"></i>
                                <span class="nav_name d-none d-xl-inline show-text">Settings</span>
                            </a>
                            <a href="#" onclick="initPage('tasks')" class="nav_link">
                                <i class="fas fa-tasks fa-fw nav_icon"></i>
                                <span class="nav_name d-none d-xl-inline show-text">Tasks</span>
                            </a>
                            <a href="#" onclick="initPage('commands')" class="nav_link">
                                <i class="fab fa-docker fa-fw nav_icon"></i>
                                <span class="nav_name d-none d-xl-inline show-text">Commands</span>
                            </a>
                            <a href="#" onclick="initPage('logs')" class="nav_link">
                                <i class="fas fa-file-code fa-fw nav_icon"></i>
                                <span class="nav_name d-none d-xl-inline show-text">Logs</span>
                            </a>
                            <?php if ($settingsTable['securityEnabled']) { ?>
                                <a href="#" onclick="initPage('security')" class="nav_link">
                                    <i class="fas fa-bug fa-fw nav_icon"></i>
                                    <span class="nav_name d-none d-xl-inline show-text">Security</span>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                    <?php if (USE_AUTH) { ?>
                        <div class="d-flex flex-column align-items-start">
                            <a href="#" onclick="logout()" class="nav_link">
                                <i class="fas fa-sign-out-alt fa-fw nav_icon"></i>
                                <span class="nav_name d-none d-xl-inline show-text">Logout</span>
                            </a>
                        </div>
                    <?php } ?>
                </nav>
            </div>
        <?php } elseif ($isLoginPage) { ?>
            <header class="header header-login bg-body" id="header">
                <div class="header-login-logo">
                    <img src="images/logo.png" alt="Dockwatch">
                    <span>Dockwatch</span>
                </div>
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
        <?php } ?>
        <div id="page-panel" style="margin-bottom:75px;">
            <?php if ($apiVersionError) { ?>
                <div id="content-dockerAPIVersionError" style="display: block;">
                    The docker API version on the host <code><?= $apiVersions[0][1] ?></code> is older than the docker version here
                    <code><?= $apiVersions[0][0] ?></code>, two choices:
                    <div class="bg-secondary rounded p-4">
                        <div class="ms-2">
                            1. Update the host docker install<br>
                            2. Add an ENV to the Dockwatch compose: <code>DOCKER_API_VERSION=<?= $apiVersions[0][1] ?></code>
                        </div>
                    </div>
                </div>
            <?php } elseif ($apiPermissionsError) { ?>
                <div id="content-dockerPermissions" style="display: block;">
                    If you are seeing this, it means the user:group running this container does not have permission to run docker
                    commands. Please fix that, restart the container and try again.<br><br>
                    <div class="bg-secondary rounded p-4">
                        An example for Ubuntu:
                        <div class="ms-2">
                            Set the PGID to the docker group<br>
                            &nbsp;&nbsp;&nbsp;- <code>ls -ltra /var/run/docker.sock</code> to see the group running the sock ("docker"
                            for example)<br>
                            &nbsp;&nbsp;&nbsp;- <code>grep docker /etc/group</code> to see the group id and use as the PGID<br>
                            Change the user:group with a chown (if necessary)<br>
                            Wipe the appdir and retry (if necessary)<br>
                            Try with --force-recreate (if necessary)
                        </div>
                    </div>
                    <div class="bg-secondary rounded p-4 mt-3">
                        An example for Synology:
                        <div class="ms-2">
                            Create a docker group <code>sudo synogroup --add docker</code><br>
                            &nbsp;&nbsp;&nbsp;- Take note of the group id it returns next to Group Id: [65537] (for example)<br>
                            Adjust docker sock permissions: <code>sudo chown root:docker /var/run/docker.sock</code><br>
                            Assign the PGID to the group id from above and restart (docker-compose up -d)
                        </div>
                    </div>
                </div>
            <?php } ?>
