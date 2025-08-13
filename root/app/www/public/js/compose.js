function composeAdd()
{
    if (!$('#new-compose-name').val() || !$('#new-compose-data').val()) {
        toast('Compose', 'The name and compose data are required to save.', 'error');
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'ajax/compose.php',
        data: '&m=composeAdd&name=' + fixedEncodeURIComponent($('#new-compose-name').val()) + '&compose=' + fixedEncodeURIComponent($('#new-compose-data').val()),
        success: function (resultData) {
            initPage('compose');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function composeSave(composePath, value)
{
    if (!value) {
        toast('Compose', 'The compose data is required to save.', 'error');
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'ajax/compose.php',
        data: '&m=composeSave&composePath=' + composePath + '&compose=' + fixedEncodeURIComponent(value),
        success: function (resultData) {
            if (resultData.startsWith('Failed')) {
                toast('Compose', resultData, 'error');
                return;
            }

            $('#compose-data-preview').html(resultData);
            $('#compose-data-preview').show();
            $('#compose-data').hide();
            toast('Compose', 'Compose changes saved, you can close the popup if you are done editing', 'success');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function composePull(composePath)
{
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/compose.php',
        data: '&m=composePull&composePath=' + composePath,
        success: function (resultData) {
            if (resultData == 'pulled') {
                toast('Compose', 'docker-compose pull was completed', 'success');
            } else {
                toast('Compose', 'docker-compose pull failed<br>' + resultData, 'error');
            }
            pageLoadingStop();
        },
        error: function(jqhdr, textStatus, errorThrown) {
            toast('Compose', 'Ajax error (likely a timeout), open the dev console with F12 and try again to try and reproduce', 'error');
            pageLoadingStop();
        }
    });
}
// ---------------------------------------------------------------------------------------------
function composeUp(composePath)
{
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/compose.php',
        data: '&m=composeUp&composePath=' + composePath,
        success: function (resultData) {
            if (resultData == 'up') {
                toast('Compose', 'docker-compose up -d was completed', 'success');
            } else {
                toast('Compose', 'docker-compose up -d failed<br>' + resultData, 'error');
            }
            pageLoadingStop();
        },
        error: function(jqhdr, textStatus, errorThrown) {
            toast('Compose', 'Ajax error (likely a timeout), open the dev console with F12 and try again to try and reproduce', 'error');
            pageLoadingStop();
        }
    });
}
// ---------------------------------------------------------------------------------------------
function composeModify(composePath)
{
    $.ajax({
        type: 'POST',
        url: 'ajax/compose.php',
        data: '&m=composeModify&composePath=' + composePath,
        success: function (resultData) {
            dialogOpen({
                id: 'composeModify',
                title: 'Modify compose',
                size: 'lg',
                body: resultData,
                onOpen: function () {

                }
            });
        }
    });
}
// ---------------------------------------------------------------------------------------------
function composeDelete(composePath)
{
    if (confirm('Are you sure you want to delete the compose located at ' + composePath + '? This can not be reversed.')) {
        $.ajax({
            type: 'POST',
            url: 'ajax/compose.php',
            data: '&m=composeDelete&composePath=' + composePath,
            success: function (resultData) {
                initPage('compose');
            }
        });
    }
}
// ---------------------------------------------------------------------------------------------