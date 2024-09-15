let init = false;
let containerTableDrawn = false;
let restoreGroups = false;
let currentPage = 'overview';

$(document).ready(function () {
    if ($('#menu-overview').length) {
        initPage(USE_EXTERNAL_LOADING);
    }

    $('#loading-modal').modal({
        keyboard: false,
        backdrop: 'static'
    });

    initializeSSE();
}).keyup(function (e) {
    if ($('#username').length) {
        if (e.keyCode === 13) {
            login();
        }
    }
});
// -------------------------------------------------------------------------------------------
function containerMenuMouseOut()
{
    if (currentPage != 'containers') {
        $('#menu-containers-label').removeClass('text-primary');
    }
}
// -------------------------------------------------------------------------------------------
function clearInitPage(page)
{
    init = false;
    $('#content-' + page).html('The <code>init</code> variable has been set to false, you can load a page without waiting now');
}
// -------------------------------------------------------------------------------------------
function initPage(page, menu = false)
{
    $('.conatiner-links').hide();

    if (page == 'containers') {
        $('.conatiner-links').show();
    }

    if (menu && EXTERNAL_LOADING) {
        window.location.href = '?page=' + page
        return;
    }

    if (init) {
        toast('Loading', '<span ondblclick="clearInitPage(\'' + page + '\')">A previous page load is still finishing, try again in a second</span>', 'info');
        return;
    }

    const dockerPermissionsError = $('#content-dockerPermissions').is(':visible');
    if (dockerPermissionsError) {
        return;
    }

    //-- CONTAINERS MENU ITEM IS A LITTLE DIFFERENT
    $('#menu-containers-label').removeClass('text-primary');
    if (page == 'containers') {
        $('#menu-containers-label').addClass('text-primary');
    }

    currentPage = page;
    init = true;
    $('[id^=content-]').hide();
    $('#content-' + page).html('Loading ' + page + ' page...').show();
    $('[id^=menu-]').removeClass('active');
    $('#menu-' + page).addClass('active');

    $.ajax({
        type: 'POST',
        url: '../ajax/' + page + '.php',
        data: '&m=init&page=' + page,
        success: function (resultData) {
            init = false;
            $('#content-' + page).html(resultData);

            if (page == 'containers') {
                $('#sse-timer').html(sseCountdown);

                containerTableDrawn = false;
                $('#container-table').dataTable({
                    dom: 'lfBrtip',
                    stateSave: true,
                    stateSaveParams: function (settings, data) {
                        data.search.search = '';
                        if (restoreGroups) {
                            restoreGroups = false;
                            data.order = '';
                            initPage('containers');
                        } else {
                            if (data.order[0] > 1) {
                                if ($('.container-group-row').length) {
                                    $('.container-group-row').show()
                                    $('.container-group').hide();
                                    $('#group-restore-btn').show();
                                }
                            }
                        }
                    },
                    paging: false,
                    ordering: true,
                    columnDefs: [{
                        targets: 'no-sort',
                        orderable: false
                    }],
                    buttons: [
                        'colvis'
                    ],
                    initComplete: function () {
                        $('#container-table_filter label').addClass('text-secondary');
                        $('#container-table_filter input').attr('placeholder', 'Search').removeClass('form-control').addClass('text-muted form-control-sm');

                        $('.buttons-colvis').on('click', function () {
                            $('.dt-button-collection').addClass('bg-secondary');
                        });

                        $('input[type=search]').on('keydown', function () {
                            $('.container-group-row').show();
                        });

                        $('.dt-buttons').prepend($('#check-all-btn')).append($('#group-restore-btn'));

                        $('.dataTables_filter').addClass('dt-buttons');

                        $('.sorting_disabled').removeClass('sorting_asc');
                    },
                    fnDrawCallback: function () {
                        if (containerTableDrawn && $('.container-group-row').length && $('.container-group').is(':visible')) {
                            $('.container-group-row').show();
                            $('.container-group').hide();
                            $('#group-restore-btn').show();
                        }
                        containerTableDrawn = true;
                    }
                });
            }
        }
    });

}
// ---------------------------------------------------------------------------------------------
function login()
{
    if (!$('#username').val() || !$('#password').val()) {
        toast('Login', 'Username and password is required', 'error');
        return;
    }

    loadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/login.php',
        data: '&m=login&user=' + $('#username').val() + '&pass=' + $('#password').val(),
        dataType: 'json',
        success: function (resultData) {
            if (resultData.timeout) {
                reload();
            }

            if (resultData.error) {
                toast('Login', 'Error: ' + resultData.error, 'error');
                loadingStop();
                return;
            }

            reload();
        }
    });
}
// ---------------------------------------------------------------------------------------------
function logout()
{
    loadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/login.php',
        data: '&m=logout',
        success: function (resultData) {
            reload();
        }
    });
}
// ---------------------------------------------------------------------------------------------
function reload()
{
    window.location.href='/';
}
// ---------------------------------------------------------------------------------------------
function toast(title, message, type)
{
    const uniqueId = Date.now() + Math.floor(Math.random() * 1000);

    let toast  = '';
    let border = 'info';

    if (type == 'error') {
        border = 'danger';
    }
    if (type == 'success') {
        border = 'success';
    }

    toast += '<div id="toast-' + uniqueId + '" class="toast text-white bg-' + border + '" data-autohide="false">';
    toast += '  <div class="toast-header text-white bg-' + border + '">';
    toast += '      <i class="far fa-bell text-white me-2"></i>';
    toast += '      <strong class="me-auto">' + title + '</strong>';
    toast += '      <small>' + type + '</small>';
    toast += '      <button type="button" class="btn-close" data-bs-dismiss="toast"></button>';
    toast += '  </div>';
    toast += '  <div class="toast-body">' + message + '</div>';
    toast += '</div>';

    $('.toast-container').append(toast);
    $('#toast-' + uniqueId).toast('show');

    setTimeout(function () {
        $('#toast-' + uniqueId).remove();
    }, 10000);

}
// -------------------------------------------------------------------------------------------
function loadingStart()
{
    if ($('#loading-modal .btn-close').is(':visible')) {
        loadingStop();
    }

    $('#loading-modal .btn-close').hide();
    $('#loading-modal').modal('show');

    $('#loading-modal .modal-header').dblclick(function () {
        $('#loading-modal .btn-close').show();
    });

}
// -------------------------------------------------------------------------------------------
function loadingStop()
{
    setTimeout(function () {
        $('#loading-modal').modal('hide');
    }, 100);

}
// -------------------------------------------------------------------------------------------
function updateActiveServer()
{
    $('[id^=external-server-icon-link-]').hide();
    $('#external-server-icon-link-' + $('#activeServerId').val()).show();

    if ($('#activeServerId').val() != APP_SERVER_ID) {
        sseSource.close();
        USE_SSE = false;
        console.log('SSE: Disabled (Remote management)');
    } else {
        USE_SSE = SSE_SETTING ? true : false;
        initializeSSE();
    }

    $.ajax({
        type: 'POST',
        url: '../ajax/settings.php',
        data: '&m=updateActiveServer&id=' + $('#activeServerId').val(),
        success: function (resultData) {
            initPage(currentPage);
        }
    });
}
// -------------------------------------------------------------------------------------------
function dialogOpen(p)
{
    const id        = p.id;
    const title     = p.title ? p.title : '&nbsp;';
    const body      = p.body ? p.body : '&nbsp;';
    const footer    = p.footer ? p.footer : '&nbsp;';
    const close     = typeof p.close === 'undefined' ? true : p.close;
    const size      = p.size ? p.size : ''; //-- sm, lg, xl
    const escape    = typeof p.escape === 'undefined' ? false : p.escape;
    const minimize  = typeof p.minimize === 'undefined' ? false : p.minimize;

    if (typeof id === 'undefined') {
        console.log('Error: Called dialogOpen with no id parameter');
        return;
    }

    if ($('#' + id).length) {
        $('#' + id).remove();
    }

    //-- CLONE IT
    $('#dialog-modal').clone().appendTo('#dialog-modal-container').prop('id', id);

    //-- USE THE CLONE
    $('#' + id).modal({
        keyboard: false,
        backdrop: 'static'
    });

    if (escape) {
        $('#' + id).attr('data-escape-close', 'true');
    }

    $('#' + id + ' .modal-title').html(title);
    $('#' + id + ' .modal-body').html(body);
    $('#' + id + ' .modal-footer').html(footer);

    if (!close) {
        $('#' + id + ' .btn-close').hide();

        $('#' + id + ' .modal-header').dblclick(function () {
            $('#' + id + ' .btn-close').show();
        });
    }

    if (minimize) {
        const closeBtn = $('#' + id + ' .btn-close').clone();

        $('#' + id + ' .btn-close').remove();
        $('#' + id + ' .modal-header').append('<div style="float: right;" class="dialog-btn-container"></div>');
        $('#' + id + ' .modal-header .dialog-btn-container').append('<i onclick="$(\'#' + id + '\').modal(\'hide\'); $(\'#' + id + '-minimized\').show();" class="fa-solid fa-window-minimize" style="cursor: pointer;"></i>').append(closeBtn);

        let minimizeDiv = '<div id="' + id + '-minimized" style="position: fixed; bottom: 0; right: 0; z-index: 10001; display: none; margin-right: 6em;">';
        minimizeDiv    += '    <div class="card bg-theme border-theme bg-opacity-75 mb-3">';
        minimizeDiv    += '        <div class="card-header border-theme fw-bold small text-inverse">' + $('#' + id + ' .modal-header .modal-title').text() + ' <i style="cursor: pointer;" onclick="$(\'#' + id + '\').modal(\'show\'); $(\'#' + id + '-minimized\').hide();" class="fa-regular fa-window-restore"></i></div>';
        minimizeDiv    += '        <div>';
        minimizeDiv    += '            <div class="card-arrow-bottom-left"></div>';
        minimizeDiv    += '            <div class="card-arrow-bottom-right"></div>';
        minimizeDiv    += '        </div>';
        minimizeDiv    += '    </div>';
        minimizeDiv    += '</div>';

        $('body').append(minimizeDiv);
    }

    $('#' + id + ' .modal-dialog').draggable({
        handle: '.modal-header'
    });

    $('#' + id + ' .modal-header').css('cursor', 'grab');

    $('#' + id).modal('show');

    if (size) {
        $('#' + id + ' .modal-dialog').addClass('modal-' + size);
    }

    if (typeof p.onOpen !== 'undefined') {
        const onOpenFunction = p.onOpen;
        function onOpenCallback(callback)
        {
            callback();
        }
        onOpenCallback(onOpenFunction);
    }

    if (typeof p.onClose !== 'undefined') {
        const onCloseFunction = p.onClose;
        function onCloseCallback(callback)
        {
            callback();
        }

        $('#' + id + ' .btn-close').attr('onclick', '');
        $('#' + id + ' .btn-close').bind('click', function () {
            onCloseCallback(onCloseFunction);
            dialogClose(id);
        });
    }

}
// -------------------------------------------------------------------------------------------
function dialogClose(elm)
{
    if (!elm) {
        console.error('Error: Called dialogClose on no elm');
        return;
    }

    if (!$('#' + elm).length) {
        console.error('Error: Could not locate dialog with id \'' + elm + '\'');
        return;
    }

    $('#' + elm).modal('hide');

}
// -------------------------------------------------------------------------------------------
function dockwatchWarning()
{
    dialogOpen({
        id: 'dockwatchWarning',
        title: 'Important Information',
        size: 'lg',
        body: $('#dockwatchWarningText').html()
    });
}
// -------------------------------------------------------------------------------------------
function dockwatchMaintenance(action)
{
    toast('Dockwatch Maintenance', 'The ' + action + ' request has been sent, please wait to refresh...', 'info');

    $.ajax({
        type: 'POST',
        url: '../ajax/maintenance.php',
        data: '&m=dockwatchMaintenance&action=' + action
    });
}
// -------------------------------------------------------------------------------------------
function fixedEncodeURIComponent(str)
{
    return encodeURIComponent(str).replace(/[!'()*]/g, function(c) {
        return '%' + c.charCodeAt(0).toString(16);
    });
}
// -------------------------------------------------------------------------------------------
function pageLoadingStart()
{
    $('#loading-modal').modal({
        keyboard: false,
        backdrop: 'static'
    });

    $('#loading-modal .btn-close').hide();
    $('#loading-modal').modal('show');

    $('#loading-modal .modal-header').dblclick(function () {
        $('#loading-modal .btn-close').show();
    });

}
// -------------------------------------------------------------------------------------------
function pageLoadingStop()
{
    setTimeout(function () {
        $('#loading-modal').modal('hide');
    }, 500);

}
// -------------------------------------------------------------------------------------------
function resetSession()
{
    $.ajax({
        type: 'POST',
        url: '../ajax/login.php',
        data: '&m=resetSession',
        success: function (resultData) {
            reload();
        }
    });
}
// -------------------------------------------------------------------------------------------
