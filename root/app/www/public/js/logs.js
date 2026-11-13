let currentLog      = null;
let logAutoRefresh  = true;
// ---------------------------------------------------------------------------------------------
function setRefreshButton(button, enabled)
{
    $(button).find('i').toggleClass('fa-sync', enabled).toggleClass('fa-pause', !enabled);
    $(button).attr('title', enabled ? 'Pause auto-refresh' : 'Resume auto-refresh');
}
// ---------------------------------------------------------------------------------------------
function highlightLog(log)
{
    //-- MATCH THE LOG NAME STORED IN THE SPAN'S DATA ATTRIBUTE
    $('[id^=logList-]').removeClass('text-warning').addClass('text-secondary').filter(function () {
        return $(this).attr('data-log') === log;
    }).removeClass('text-secondary').addClass('text-warning');
}
// ---------------------------------------------------------------------------------------------
function keepLogScroll(update)
{
    const viewer    = $('#logViewer');
    const follow    = viewer.scrollTop() + viewer.innerHeight() >= viewer[0].scrollHeight - 5;
    const scrollTop = viewer.scrollTop();

    update();

    viewer.scrollTop(follow ? viewer[0].scrollHeight : scrollTop);
}
// ---------------------------------------------------------------------------------------------
function subscribeLogs()
{
    console.log('SSE: Starting..');
    if (!USE_SSE) {
        console.log('SSE: Disabled');
        return;
    }
    closeSubscription('logs');

    if (!logAutoRefresh) {
        return;
    }

    const handlers = {
        logList: function (html) {
            $('#log-list').html(html);

            if (currentLog) {
                highlightLog(currentLog.log);
            }
        }
    };

    if (currentLog) {
        handlers.log = function (result) {
            if (result.error) {
                return;
            }

            $('#logHeader').html(result.header);

            keepLogScroll(function () {
                $('#logViewer').html(result.log);
            });
        };
    }

    sseSubscribe('logs', handlers, currentLog ? { log: currentLog.log } : {});

    console.log('SSE: Started');
}
// ---------------------------------------------------------------------------------------------
function toggleLogAutoRefresh()
{
    logAutoRefresh = !logAutoRefresh;

    subscribeLogs();
    setRefreshButton('#auto-refresh-toggle', logAutoRefresh);
    $('#auto-refresh-status').text(logAutoRefresh ? '3s' : 'off');
}
// ---------------------------------------------------------------------------------------------
function viewLog(name, hash)
{
    pageLoadingStart();

    highlightLog(name);
    currentLog = { log: name };

    $.ajax({
        type: 'POST',
        url: 'ajax/logs.php',
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

            $('#logViewer').scrollTop(999999);
            subscribeLogs();
        }
    });
}
// ---------------------------------------------------------------------------------------------
function downloadLog(name, hash)
{
    pageLoadingStart();

    highlightLog(name);
    $('#logViewer').html('Fetching log...');

    $.ajax({
        type: 'POST',
        url: 'ajax/logs.php',
        data: '&m=viewLog&name=' + name,
        dataType: 'json',
        success: function (resultData) {
            pageLoadingStop();
            if (!resultData.error) {
                //-- CLEAN UP LOG
                let content = resultData.log;
                content = content.replace(/<[^>]*>/g, '');
                content = content.replace(/&quot;/g, '"');
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
        url: 'ajax/logs.php',
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

    if (currentLog && currentLog.log === log) {
        currentLog = null;
    }

    $.ajax({
        type: 'POST',
        url: 'ajax/logs.php',
        data: '&m=deleteLog&log=' + log,
        success: function (resultData) {
            initPage('logs');
            pageLoadingStop();
        }
    });

}
// ---------------------------------------------------------------------------------------------
