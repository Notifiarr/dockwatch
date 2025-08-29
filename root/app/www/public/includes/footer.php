<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

?>
        </div>
        <footer id="footer" class="footer fixed-bottom bg-body">
            <div class="container border border-bottom-0 border-start-0 border-end-0 border-secondary" style="height: 100%;">
                <div id="footer-content" class="row">
                    <div id="footer-branch" class="col-sm-12 col-lg-4 text-center">
                        Branch: <?= gitBranch() ?>, Hash: <a href="https://github.com/Notifiarr/dockwatch/commit/<?= gitHash() ?>" target="_blank" class="text-info"><?= substr(gitHash(), 0, 7) ?></a><br>
                    </div>
                    <div id="footer-icons" class="col-sm-12 col-lg-4 text-center">
                        <a href="https://dockwatch.wiki" target="_blank" title="Visit the <?= APP_NAME ?> wiki"><i class="fab fa-wikipedia-w fa-fw btn-secondary me-2"></i></a>
                        <a href="https://github.com/Notifiarr/dockwatch" title="Visit the <?= APP_NAME ?> github" target="_blank"><i class="fab fa-github fa-fw btn-secondary me-2"></i></a>
                        <a href="https://notifiarr.com/discord" title="Get some help if the wiki does not cover it" target="_blank"><i class="fab fa-discord fa-fw btn-secondary"></i></a>
                    </div>
                    <div id="footer-themes" class="col-sm-12 col-lg-4 text-center">
                        <span class="text-muted">
                            Themes by
                            <a href="https://nzxl.space" target="_blank">
                                <img class="nzxl_space_logo" src="images/nzxl-logo.svg" width="28" alt="nzxl" />
                            </a>
                        </span>
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

        <!-- Slider -->
        <div id="left-slider"></div>

        <!-- Toast container -->
        <div class="toast-container bottom-0 end-0 p-3" style="z-index: 10001 !important; position: fixed;"></div>

        <!-- Loading modal -->
        <div class="modal fade" id="loading-modal" style="z-index: 10000 !important;" data-bs-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content bg-dark" style="border: grey solid 1px;">
                    <div class="modal-header" style="border: grey solid 1px;">
                        <h5 class="modal-title text-info">Loading</h5>
                        <button type="button" class="btn btn-outline-primary btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="border: grey solid 1px;">
                        <p>
                            <div class="spinner-border text-info" style="margin-right: 1em;"></div>
                            <span class="text-white">I'm gathering everything needed to complete the request, give me just a moment...</span>
                        </p>
                    </div>
                    <div class="modal-footer">&nbsp;</div>
                </div>
            </div>
        </div>

        <!-- Mass trigger modal -->
        <div class="modal fade" id="massTrigger-modal" style="z-index: 9999 !important; overflow: auto;" data-bs-backdrop="static">
            <div class="modal-dialog" style="max-width: 1000px">
                <div class="modal-content bg-dark" style="border: grey solid 1px;">
                    <div class="modal-header" style="border: grey solid 1px;">
                        <h5 class="modal-title text-info"><div id="massTrigger-spinner" class="spinner-border text-info" style="margin-right: 1em;"></div> Mass Trigger: <span id="triggerAction"></span></h5>
                    </div>
                    <div class="modal-body" style="border: grey solid 1px;">
                        <div id="massTrigger-header"></div>
                        <div id="massTrigger-results" style="max-height: 600px; overflow: auto;"></div>
                    </div>
                    <div class="modal-footer" align="center">
                        <button id="massTrigger-close-btn" style="display: none;" type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Container group modal -->
        <div class="modal fade" id="containerGroup-modal" style="z-index: 9999 !important; overflow: auto;" data-bs-backdrop="static">
            <div class="modal-dialog" style="max-width: 1000px">
                <div class="modal-content bg-dark" style="border: grey solid 1px;">
                    <div class="modal-header" style="border: grey solid 1px;">
                        <h5 class="modal-title text-info">Group Management</h5>
                    </div>
                    <div class="modal-body" style="border: grey solid 1px;">
                        <div id="containerGroup-containers" style="max-height: 600px; overflow: auto;"></div>
                    </div>
                    <div class="modal-footer" align="center">
                        <button type="button" class="btn btn-outline-success" onclick="saveContainerGroup()">Save</button>
                        <button id="groupCloseBtn" type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update options modal -->
        <div class="modal fade" id="updateOptions-modal" style="z-index: 9999 !important; overflow: auto;" data-bs-backdrop="static">
            <div class="modal-dialog" style="max-width: 1000px">
                <div class="modal-content bg-dark" style="border: grey solid 1px;">
                    <div class="modal-header" style="border: grey solid 1px;">
                        <h5 class="modal-title text-info">Container update options</h5>
                    </div>
                    <div class="modal-body" style="border: grey solid 1px;">
                        <div id="updateOptions-containers" style="max-height: 600px; overflow: auto;"></div>
                    </div>
                    <div class="modal-footer" align="center">
                        <button type="button" class="btn btn-outline-success" onclick="saveUpdateOptions()">Save</button>
                        <button id="groupCloseBtn" type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generic modal -->
        <div id="dialog-modal-container">
            <div class="modal fade" id="dialog-modal" style="z-index: 99999 !important;" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-scrollable">
                    <div class="modal-content bg-dark" style="border: grey solid 1px;">
                        <div class="modal-header" style="border: grey solid 1px;">
                            <h5 class="modal-title"></h5>
                            <i class="far fa-window-close fa-2x ms-auto" data-bs-dismiss="modal" style="cursor: pointer;"></i>
                        </div>
                        <div class="modal-body" data-scrollbar=”true” data-wheel-propagation=”true”></div>
                        <div class="modal-footer"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- xterm Shell -->
        <div id="xtermShellDiv" style="display: none;">
            <div id="terminalContainer" class="bg-dark primary rounded" style="overflow: hidden; position: relative;"></div>
        </div>

        <!-- Registry login -->
        <div id="registryLoginDiv" style="display: none;">
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-sm-12 col-lg-4">Registry</div>
                    <div class="col-sm-12 col-lg-8"><input id="registryUrl" type="text" class="form-control" disabled></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-12 col-lg-4">Username</div>
                    <div class="col-sm-12 col-lg-8"><input id="registryUsername" type="text" class="form-control"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-12 col-lg-4">Password</div>
                    <div class="col-sm-12 col-lg-8"><input id="registryPassword" type="password" class="form-control"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-sm-12"><button class="btn btn-outline-success" onclick="registryLogin()">Login</button></div>
                </div>
            </div>
        </div>

        <!-- Frequency helper information -->
        <div id="containerFrequencyHelpDiv" style="display: none;">
            There are 5 parts to a cron (6 with the optional year which is not used here). Below shows each section:<br>
            <pre>
                *    *    *    *    *
                -    -    -    -    -
                |    |    |    |    |
                |    |    |    |    |
                |    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
                |    |    |    +---------- month (1 - 12)
                |    |    +--------------- day of month (1 - 31)
                |    +-------------------- hour (0 - 23)
                +------------------------- min (0 - 59)
            </pre>
            An example of a cron for every hour would be: <code>0 * * * *</code> and you can generate a cron for just about any variation using <a href="https://crontab.guru/" target="_blank" class="text-info">Cronitor</a><br><br>
            <span class="text-danger">NOTES:</span>
            <ul>
                <li>The minute section will always be set to 0 (zero) for a minimum check time of 1 hour, be respectful of external API usage.</li>
                <li>Using an invalid cron syntax will default to daily at midnight <code<?= DEFAULT_CRON ?></code></li>
            </ul>
        </div>

        <!-- Frequency cron editor -->
        <div id="frequencyCronEditorDiv" style="display: none;">
            <div id="cron"></div>
            <span class="text-danger">NOTES:</span>
            <ul>
                <li>If the cron output does not meet your requirements, you can enter your own by overwriting the output box.</li>
            </ul>
        </div>

        <!-- Warning -->
        <div id="dockwatchWarningText" style="display: none;">
            Dockwatch has a different method for updating and restarting its self, for this reason it is ignored in the check all action. It will clone its self and create a container named <code>dockwatch-maintenance</code> since it can not do things to its self.<br><br>
            The <code>dockwatch-maintenance</code> container needs a port, by default it is <code>9998</code> but you can change it in the settings. If you use a static ip for your containers, make sure and set an ip for it as well.<br><br>
            Dockwatch manual/auto update:
            <ul>
                <li>dockwatch pulls current container</li>
                <li>dockwatch creates/starts dockwatch-maintenance</li>
                <li>dockwatch-maintenance stops/removes/creates/starts dockwatch</li>
                <li>dockwatch stops/removes dockwatch-maintenance</li>
            </ul>
            Dockwatch restart (unhealthy):
            <ul>
                <li>dockwatch pulls current container</li>
                <li>dockwatch creates/starts dockwatch-maintenance</li>
                <li>dockwatch-maintenance stops/starts dockwatch</li>
                <li>dockwatch stops/removes dockwatch-maintenance</li>
            </ul>
            <br>
            Consider not running the dockwatch update time at the same time as other containers. When dockwatch starts its update process, everything after it will be ignored since it is stopping its self!<hr>
            <?php if ($_SESSION['activeServerId'] != APP_SERVER_ID) { ?>
                Remote control of self restarts and updates is not supported.
            <?php } else { ?>
                Wait 30-45 seconds after using these buttons and refresh the page so the process outlined above can complete. If you have notifications enabled you will see the maintenance container start and shortly after the dockwatch container start.<br><br>
                <center>
                    <button onclick="dockwatchMaintenance('restart')" class="btn btn-outline-info">Restart Dockwatch</button>
                    <button onclick="dockwatchMaintenance('update')" class="btn btn-outline-info">Update Dockwatch</button>
                </center>
            <?php } ?>
        </div>

        <!-- JavaScript Libraries -->
        <script src="libraries/jquery/jquery-3.4.1.min.js"></script>
        <script src="libraries/jquery/jquery-ui-1.13.2.min.js"></script>
        <script src="libraries/bootstrap/bootstrap.bundle.min.js"></script>
        <script src="libraries/datatable/datatables.min.js"></script>
        <script src="libraries/kpopup/kpopup.js"></script>
        <script src="libraries/chart/chart.umd.min.js"></script>
        <script src="libraries/chart/chartjs.plugin.datalabels.min.js"></script>
        <script src="libraries/xterm/xterm.min.js"></script>
        <script src="libraries/xterm/xterm-addon-fit.min.js"></script>

        <!-- Javascript -->
        <?= loadJS() ?>
    </body>
</html>