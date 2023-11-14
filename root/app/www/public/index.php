<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'loader.php';

$dockerPerms = dockerPermissionCheck();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Docker Watcher</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Roboto:wght@500;700&display=swap" rel="stylesheet"> 
    
    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
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
                    <h3 class="text-primary">DockWatch</h3>
                </a>
                <div class="navbar-brand w-100 mb-1 text-center">
                    <a href="https://github.com/Notifiarr/dockwatch" target="_blank"><i class="fab fa-github btn-secondary me-2"></i></a>
                    <a href="https://notifiarr.com/discord" target="_blank"><i class="fab fa-discord btn-secondary"></i></a>
                </div>
                <div class="navbar-nav w-100">
                    <a id="menu-overview" onclick="initPage('overview')" style="cursor: pointer;" class="nav-item nav-link active"><i class="fas fa-heartbeat me-2"></i>Overview</a>
                    <a id="menu-containers" onclick="initPage('containers')" style="cursor: pointer;" class="nav-item nav-link"><i class="fas fa-th me-2"></i>Containers</a>
                    <a id="menu-notification" onclick="initPage('notification')" style="cursor: pointer;" class="nav-item nav-link"><i class="fas fa-comment-dots me-2"></i>Notifications</a>
                    <a id="menu-settings" onclick="initPage('settings')" style="cursor: pointer;" class="nav-item nav-link"><i class="fas fa-tools me-2"></i>Settings</a>
                </div>
            </nav>
        </div>
        <!-- Sidebar End -->

        <!-- Content Start -->
        <div class="content">
            <!-- Navbar Start -->
            <nav class="navbar navbar-expand bg-secondary navbar-dark sticky-top px-4 py-0">
                <a href="#" class="sidebar-toggler flex-shrink-0 m-2">
                    <i class="fa fa-bars"></i>
                </a>
            </nav>
            <!-- Navbar End -->

            <!-- App Start -->
            <div class="container-fluid pt-4 px-4">
                <div id="content-overview" style="display: none;"></div>
                <div id="content-containers" style="display: none;"></div>
                <div id="content-notification" style="display: none;"></div>
                <div id="content-settings" style="display: none;"></div>
                <div id="content-dockerPermissions" style="display: <?= ($dockerPerms ? 'none' : 'block') ?>;">
                    If you are seeing this, it means the user running this container does not have permission to run docker commands. Please fix that, restart the container and try again.
                </div>
            </div>
            <!-- App End -->

            <!-- Footer Start -->
            <div class="container-fluid pt-4 px-4">
                <div class="bg-secondary rounded-top p-4" style="position: absolute; bottom: 0; width: 80%;">
                    <div class="row">
                        <div class="col-12 col-sm-6 text-center text-sm-start">
                            &copy; DockWatch <?= date('Y') ?>, All Right Reserved. 
                        </div>
                        <div class="col-12 col-sm-6 text-center text-sm-end">
                            Theme By <a href="https://htmlcodex.com">HTML Codex</a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer End -->
        </div>
        <!-- Content End -->

        <!-- Back to Top -->
        <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- Toast container -->
    <div class="toast-container bottom-0 end-0 p-3" style="z-index: 10000 !important; position: fixed;"></div>

    <!-- Loading modal -->
    <div class="modal fade" id="loading-modal" style="z-index: 9999 !important;" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content bg-dark" style="border: grey solid 1px;">
                <div class="modal-header" style="border: grey solid 1px;">
                    <h5 class="modal-title text-primary">Loading</h5>
                    <button type="button" class="btn btn-outline-primary btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="border: grey solid 1px;">
                    <p>
                        <div class="spinner-border text-primary" style="margin-right: 1em;"></div>
                        <span class="text-white">I'm gathering everything needed to complete the request, give me just a moment...</span>
                    </p>
                </div>
                <div class="modal-footer">&nbsp;</div>
            </div>
        </div>
    </div>

    <!-- Mass trigger modal -->
    <div class="modal fade" id="massTrigger-modal" style="z-index: 9999 !important;" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content bg-dark" style="border: grey solid 1px;">
                <div class="modal-header" style="border: grey solid 1px;">
                    <h5 class="modal-title text-primary"><div id="massTrigger-spinner" class="spinner-border text-primary" style="margin-right: 1em;"></div> Mass Trigger</h5>
                </div>
                <div class="modal-body" style="border: grey solid 1px;">
                    <div id="massTrigger-header"></div>
                    <div id="massTrigger-results" style="max-height: 200px; overflow: auto;"></div>
                </div>
                <div class="modal-footer" align="center">
                    <button id="massTrigger-close-btn" style="display: none;" type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Javascript -->
    <?= loadJS() ?>
</body>
</html>
