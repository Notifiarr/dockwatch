const sseTimer    = 57;
const sseInterval = 3;
let sseCountdown  = 57;
let sseSubscriptions = {};
// -------------------------------------------------------------------------------------------
function hasSubscription(name)
{
    return Object.prototype.hasOwnProperty.call(sseSubscriptions, name);
}
// -------------------------------------------------------------------------------------------
function closeSubscription(name)
{
    const subscription = sseSubscriptions[name];

    if (!subscription) {
        return;
    }

    subscription.close();
    delete sseSubscriptions[name];
}
// -------------------------------------------------------------------------------------------
function closeAllSubscriptions()
{
    console.log('SSE: Closed');
    Object.keys(sseSubscriptions).forEach((name) => closeSubscription(name));
}
// -------------------------------------------------------------------------------------------
//-- Subscribes to named events from sse.php, handlers is { eventName: function (data) {} } and params go into the url
//-- sse.php answers once per request and the browser reconnects on its own, the subscription closes while the tab is hidden
//-- A name is a channel key, subscribing the same name again replaces the previous stream
function sseSubscribe(name, handlers, params = {}, onOpen = null)
{
    closeSubscription(name);

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

    sseSubscriptions[name] = {
        name,
        close: function () {
            document.removeEventListener('visibilitychange', reconnect);

            if (source) {
                source.close();
                source = null;
            }
        }
    };

    return sseSubscriptions[name];
}
// -------------------------------------------------------------------------------------------
//-- The sse cron updates sse.json every minute, a poll only answers when the row data changed
function subscribeContainers()
{
    if (!USE_SSE) {
        console.log('SSE: Disabled');
        return;
    }

    if (hasSubscription('containers')) {
        return;
    }

    console.log('SSE: Starting...');
    $('#sse-timer').html(sseTimer);

    sseSubscribe('containers', {
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
