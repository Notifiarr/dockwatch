function viewLog(name, hash)
{
    loadingStart();

    $('[id^=logList-]').removeClass('*').addClass('text-info');
    $('#logList-' + hash).removeClass('text-info').addClass('text-success');

    $.ajax({
        type: 'POST',
        url: '../ajax/logs.php',
        data: '&m=viewLog&name=' + name,
        dataType: 'json',
        success: function (resultData) {
            if (resultData.error) {
                $('#logHeader').html('');
                $('#logViewer').html(resultData.error);
            } else {
                $('#logHeader').html(resultData.header);
                $('#logViewer').html(resultData.log);
            }
            loadingStop();
        }
    });

}
// ---------------------------------------------------------------------------------------------
