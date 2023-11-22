<?php

/*
----------------------------------
 ------  Created: 111523   ------
 ------  Austin Best	   ------
----------------------------------
*/

?>
            </div>
            <!-- App End -->
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
        <div class="modal-dialog" style="max-width: 1000px">
            <div class="modal-content bg-dark" style="border: grey solid 1px;">
                <div class="modal-header" style="border: grey solid 1px;">
                    <h5 class="modal-title text-primary"><div id="massTrigger-spinner" class="spinner-border text-primary" style="margin-right: 1em;"></div> Mass Trigger</h5>
                </div>
                <div class="modal-body" style="border: grey solid 1px;">
                    <div id="massTrigger-header"></div>
                    <div id="massTrigger-results" style="max-height: 400px; overflow: auto;"></div>
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
