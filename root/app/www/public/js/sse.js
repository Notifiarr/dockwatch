function sse(reconnectDelay = 60)
{
    console.log('SSE connection started');
    var es = new EventSource('sse.php');
    es.addEventListener('message', function (e) {
        let sseResponse = JSON.parse(e.data);

        if (sseResponse) {
            let message = sseResponse.message;
            let title   = sseResponse.title;

            if (title == 'dockerProcessList' && $('#menu-containers').hasClass('active')) {
                $.each(message, function (index, container) {
                    updateContainerRowText(container.hash, container.row);
                });
            }
        }
    }, false);

    es.addEventListener('error', function (e) {
        setTimeout(function () {
            console.log('SSE connection closed, retry in ' + reconnectDelay);
            console.log(e);
            es.close();
            sse(reconnectDelay);
            return;
        }, (reconnectDelay * 1000));
    }, false);

}
// ---------------------------------------------------------------------------------------------
