$(document).ready(function () {
    initPage('overview');
});
// -------------------------------------------------------------------------------------------

function initPage(page)
{
    const dockerPermissionsError = $('#content-dockerPermissions').is(':visible');
    if (dockerPermissionsError) {
        return;
    }

    loadingStart();
    $('[id^=content-]').hide();
    $('#content-' + page).html('Loading ' + page + '...').show();
    $('[id^=menu-]').removeClass('active');
    $('#menu-' + page).addClass('active');

    $.ajax({
        type: 'POST',
        url: '../ajax/' + page + '.php',
        data: '&m=init&page=' + page,
        success: function (resultData) {
            $('#content-' + page).html(resultData);
            loadingStop();
        }
    });

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
function loadingStop()
{
    setTimeout(function () {
        $('#loading-modal').modal('hide');
    }, 500);

}
// -------------------------------------------------------------------------------------------
