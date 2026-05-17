function openComposeAdd()
{
    $.ajax({
        type: 'POST',
        url: 'ajax/compose.php',
        data: '&m=composeAddForm',
        success: function (resultData) {
            dialogOpen({
                id: 'composeAdd',
                title: 'Add compose',
                size: 'lg',
                body: resultData
            });
        }
    });
}
// ---------------------------------------------------------------------------------------------
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
function compose(path, action)
{
    if (action == 'composeDown' && !confirm('Are you sure you want to run docker-compose down for ' + path + '? This will stop and remove the stack containers.')) {
        return;
    }

    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/compose.php',
        data: '&m=' + action + '&composePath=' + path,
        success: function (resultData) {
            switch (action) {
                case 'composePull':
                    if (resultData == 'pulled') {
                        toast('Compose', 'docker-compose pull was completed', 'success');
                    } else {
                        toast('Compose', 'docker-compose pull failed<br>' + resultData, 'error');
                    }
                    break;
                case 'composeUp':
                    if (resultData == 'up') {
                        toast('Compose', 'docker-compose up -d was completed', 'success');
                    } else {
                        toast('Compose', 'docker-compose up -d failed<br>' + resultData, 'error');
                    }
                    break;
                case 'composeStop':
                    if (resultData == 'stopped') {
                        toast('Compose', 'docker-compose stop was completed', 'success');
                    } else {
                        toast('Compose', 'docker-compose stop failed<br>' + resultData, 'error');
                    }
                    break;
                case 'composeDown':
                    if (resultData == 'down') {
                        toast('Compose', 'docker-compose down was completed', 'success');
                    } else {
                        toast('Compose', 'docker-compose down failed<br>' + resultData, 'error');
                    }
                    break;
                case 'composeRestart':
                    if (resultData == 'restarted') {
                        toast('Compose', 'docker compose restart was completed', 'success');
                    } else {
                        toast('Compose', 'docker compose restart failed<br>' + resultData, 'error');
                    }
                    break;
                case 'composePs':
                    composeOutputDialog('ps', path, resultData, true);
                    break;
                case 'composeLogs':
                    composeOutputDialog('logs', path, resultData);
                    break;
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
function composeOutputDialog(command, path, output, isHtml)
{
    const output = isHtml
        ? '<div class="mt-2" style="max-height: 70vh; overflow: auto;">' + (output || '<p class="text-muted mb-0">(no output)</p>') + '</div>'
        : '<pre class="small-text text-muted mb-0 mt-2" style="white-space: pre-wrap; max-height: 70vh; overflow: auto;">' + $('<div>').text(output || '(no output)').html() + '</pre>';

    const body = '<code class="small-text text-muted">' + $('<div>').text(path).html() + '</code>' + output;

    dialogOpen({
        id: 'compose' + command,
        title: 'docker compose ' + command,
        size: 'xl',
        body: body
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
