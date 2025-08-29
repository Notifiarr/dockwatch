function serverListToggle()
{
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/servers.php',
        data: '&m=serverListToggle',
        success: function (resultData) {
            new popup('#left-slider', {
                content: resultData,
                duration: 500,
                classes: 'bg-secondary p-2'
            }).popupLeft();

            pageLoadingStop();
        }
    });
}
// -------------------------------------------------------------------------------------------
