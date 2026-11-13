const sseTimer    = 57;
const sseInterval = 3;
let sseCountdown  = 57;
let containersSubscription = null;
// -------------------------------------------------------------------------------------------
//-- Subscribes to named events from sse.php, handlers is { eventName: function (data) {} } and params go into the url
//-- sse.php answers once per request and the browser reconnects on its own, the subscription closes while the tab is hidden
function sseSubscribe(handlers, params = {}, onOpen = null)
{
    const url = 'sse.php?' + $.param(Object.assign({ events: Object.keys(handlers).join(',') }, params));
    let source = null;

    function reconnect()
    {
        if (source) {
            source.close();
            source = null;
        }

        if (document.hidden) {
            return;
        }

        source = new EventSource(url);
        if (onOpen) {
            source.onopen = onOpen;
        }
        $.each(handlers, function (event, handler) {
            source.addEventListener(event, function (message) {
                handler(JSON.parse(message.data));
            });
        });
    }

    document.addEventListener('visibilitychange', reconnect);
    reconnect();

    return {
        close: function () {
            document.removeEventListener('visibilitychange', reconnect);

            if (source) {
                source.close();
                source = null;
            }
        }
    };
}
// -------------------------------------------------------------------------------------------
function openContainersSubscription()
{
    //-- The sse cron updates sse.json every minute, a poll only answers when the row data changed
    if (containersSubscription || !USE_SSE) {
        if (!USE_SSE) {
            console.log('SSE: Disabled');
        }

        return;
    }

    console.log('SSE: Starting...');
    $('#sse-timer').html(sseTimer);

    containersSubscription = sseSubscribe({
        containers: function (processList) {
            $.each(processList, function (hash, processData) {
                updateContainerRowText(hash, processData);
            });

            setScreenSizeVars();

            sseCountdown = sseTimer;
            $('#sse-timer').html(sseCountdown);
        }
    }, {}, function () {
        if (sseCountdown > 0) {
            sseCountdown = sseCountdown - sseInterval;
            $('#sse-timer').html(sseCountdown);
        }
    });
    console.log('SSE: Started');
}
// -------------------------------------------------------------------------------------------
function closeContainersSubscription()
{
    if (containersSubscription) {
        containersSubscription.close();
        containersSubscription = null;
    }
}
// -------------------------------------------------------------------------------------------
