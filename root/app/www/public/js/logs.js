function viewLog(name, hash)
{
    pageLoadingStart();

    $('[id^=logList-]').removeClass('*').addClass('text-secondary');
    $('#logList-' + hash).removeClass('text-secondary').addClass('text-warning');
    $('#logViewer').html('Fetching log...');

    $.ajax({
        type: 'POST',
        url: '../ajax/logs.php',
        data: '&m=viewLog&name=' + name,
        dataType: 'json',
        success: function (resultData) {
            pageLoadingStop();

            if (resultData.error) {
                $('#logHeader').html('');
                $('#logViewer').html(resultData.error);
            } else {
                $('#logHeader').html(resultData.header);
                $('#logViewer').html(resultData.log);
            }
        }
    });

}
// ---------------------------------------------------------------------------------------------
function purgeLogs(group)
{
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/logs.php',
        data: '&m=purgeLogs&group=' + group,
        success: function (resultData) {
            initPage('logs');
            pageLoadingStop();
        }
    });

}
// ---------------------------------------------------------------------------------------------
function deleteLog(log)
{
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/logs.php',
        data: '&m=deleteLog&log=' + log,
        success: function (resultData) {
            initPage('logs');
            pageLoadingStop();
        }
    });

}
// ---------------------------------------------------------------------------------------------