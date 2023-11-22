function saveContainerSettings()
{
    loadingStart();

    let params = '';
    $.each($('[id^=containers-]'), function () {
        let val = '';
        if ($(this).is(':checkbox') || $(this).is(':radio')) {
            val = $(this).prop('checked') ? 1 : 0;
        } else {
            val = $(this).val();
        }

        params += '&' + $(this).attr('id') + '=' + val;
    });

    $.ajax({
        type: 'POST',
        url: '../ajax/containers.php',
        data: '&m=saveContainerSettings' + params,
        success: function (resultData) {
            loadingStop();
            toast('Containers', 'Container settings saved', 'success');
        }
    });

}
// ---------------------------------------------------------------------------------------------
function controlContainer(containerHash, action)
{
    loadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/containers.php',
        data: '&m=controlContainer&hash=' + containerHash + '&action=' + action,
        dataType: 'json',
        success: function (resultData) {
            $('#' + containerHash + '-control').html(resultData.control);
            $('#' + containerHash + '-update').html(resultData.update);
            $('#' + containerHash + '-state').html(resultData.state);
            $('#' + containerHash + '-running').html(resultData.running);
            $('#' + containerHash + '-status').html(resultData.status);
            $('#' + containerHash + '-cpu').html(resultData.cpu);
            $('#' + containerHash + '-mem').html(resultData.mem);

            loadingStop();
            toast('Containers', 'Request processed: ' + action, 'success');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function massApplyContainerTrigger()
{
    let selected = 0;
    let params = '';
    $.each($('[id^=massTrigger-]'), function () {
        if ($(this).prop('checked')) {
            selected++;
        }
    });

    if (!selected || $('#massContainerTrigger').val() == 0) {
        return;
    }

    $('#massTrigger-modal').modal({
        keyboard: false,
        backdrop: 'static'
    });

    $('#massTrigger-modal').hide();
    $('#massTrigger-modal').modal('show');

    $('#massTrigger-header').html('Containers to apply trigger to: ' + selected + '<br>');
    $('#massTrigger-spinner').show();
    $('#massTrigger-results').html('');
    let counter = 1;
    let firstCompose = '';

    //-- DO COMPOSE ALL AT ONCE
    if (parseInt($('#massContainerTrigger').val()) == 6) {
        let hashes = '';
        $.each($('[id^=massTrigger-]'), function () {
            if ($(this).prop('checked')) {
                const containerHash = $(this).attr('id').replace('massTrigger-', '');
                hashes += (hashes ? ',' : '') + containerHash;
            }
        });

        $.ajax({
            type: 'POST',
            url: '../ajax/containers.php',
            data: '&m=massApplyContainerTrigger&trigger=' + $('#massContainerTrigger').val() + '&hash=' + hashes,
            dataType: 'json',
            async: 'global',
            success: function (resultData) {
                $('#massTrigger-results').append(resultData.result);
                $('#massContainerTrigger').val('0');
                $('.containers-check').prop('checked', false);
                $('#massTrigger-close-btn').show();
                $('#massTrigger-spinner').hide();
            }
        });
    } else {
        $.each($('[id^=massTrigger-]'), function () {
            if ($(this).prop('checked')) {
                const containerHash = $(this).attr('id').replace('massTrigger-', '');
    
                $.ajax({
                    type: 'POST',
                    url: '../ajax/containers.php',
                    data: '&m=massApplyContainerTrigger&trigger=' + $('#massContainerTrigger').val() + '&hash=' + containerHash,
                    dataType: 'json',
                    async: 'global',
                    success: function (resultData) {
                        if (parseInt($('#massContainerTrigger').val()) == 5) {
                            $('#massTrigger-results').append(resultData.result + "\n");
                        } else {
                            $('#' + containerHash + '-control').html(resultData.control);
                            $('#' + containerHash + '-update').html(resultData.update);
                            $('#' + containerHash + '-state').html(resultData.state);
                            $('#' + containerHash + '-running').html(resultData.running);
                            $('#' + containerHash + '-status').html(resultData.status);
                            $('#' + containerHash + '-cpu').html(resultData.cpu);
                            $('#' + containerHash + '-mem').html(resultData.mem);
        
                            $('#massTrigger-results').prepend(counter + ': ' + resultData.result);
                        }
    
                        if (counter == selected) {
                            $('#massContainerTrigger').val('0');
                            $('.containers-check').prop('checked', false);
                            $('#massTrigger-close-btn').show();
                            $('#massTrigger-spinner').hide();
                        }
                        counter++;
                    }
                });
            }
        });
    }
}
// ---------------------------------------------------------------------------------------------