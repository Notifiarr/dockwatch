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
function downloadLog(name, hash)
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
            if (!resultData.error) {
                //-- CLEAN UP LOG
                let content = resultData.log;
                content = content.replace(/<[^>]*>/g, '');
                content = content.replace(/&quot;/g, '\"');
                content = content.replace(/&#039;/g, '\'');
                content = content.replace(/&lt;/g, '<');
                content = content.replace(/&gt;/g, '>');

                //-- CREATE BLOB
                const blob = new Blob([content], { type: 'text/plain' });

                //-- DOWNLOAD LINK
                const downloadLink = document.createElement('a');
                downloadLink.href = URL.createObjectURL(blob);
                downloadLink.download = name.replace(/\/+/g, '_');

                //-- APPEND DL DIV AND REMOVE IT AFTERWARDS
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);

                $('#logViewer').html('Log download started');

                //-- CLEAN UP
                URL.revokeObjectURL(downloadLink.href);
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