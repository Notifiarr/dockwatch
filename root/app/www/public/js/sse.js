const sseTimer = 57;
const sseInterval = 3;
let sseCountdown = 57;
let sseUpdated = 0;
let sseSource = '';

function initializeSSE()
{
    if (!USE_SSE) {
        console.log('SSE: Disabled');
    }

    console.log('SSE: Starting...');
    sseSource = new EventSource('sse.php');
    console.log('SSE: Started');
    $('#sse-timer').html(sseTimer);

    sseSource.onmessage = (event) => {
        const processList = JSON.parse(event.data);

        if (processList['updated'] != sseUpdated) {
            if (sseUpdated == 0) {
                sseUpdated = processList['updated'];
            } else {
                console.log('SSE: Changes found, updating');

                $.each(processList, function (hash, processData) {
                    if (hash != 'updated' && hash != 'pushed') {
                        updateContainerRowText(hash, processData);
                        sseCountdown = sseTimer;
                        $('#sse-timer').html(sseTimer);
                    }
                });
            }
        } else {
            if (sseCountdown > 0) {
                sseCountdown = sseCountdown - sseInterval;
                $('#sse-timer').html(sseCountdown);
            }
        }
    };
}
// -------------------------------------------------------------------------------------------