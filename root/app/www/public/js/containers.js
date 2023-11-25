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
            $('#' + containerHash + '-cpu').prop('title', resultData.cpuTitle);
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
function openContainerGroups()
{
    $('#containerGroup-modal').modal({
        keyboard: false,
        backdrop: 'static'
    });

    $('#containerGroup-modal').hide();
    $('#containerGroup-modal').modal('show');

    $.ajax({
        type: 'POST',
        url: '../ajax/containers.php',
        data: '&m=openContainerGroups',
        success: function (resultData) {
            $('#containerGroup-containers').html(resultData);
        }
    });
}
// ---------------------------------------------------------------------------------------------
function loadContainerGroup()
{
    $('#deleteGroupContainer').hide();
    if ($('#groupSelection').val() != 1) {
        $('#deleteGroupContainer').show();
        $('#groupName').val($('#groupSelection option:selected').text());
    } else {
        $('#groupName').val('');
    }

    $.ajax({
        type: 'POST',
        url: '../ajax/containers.php',
        data: '&m=loadContainerGroup&groupHash=' + $('#groupSelection').val(),
        success: function (resultData) {
            $('#containerGroupRows').html(resultData);
        }
    });

}
// ---------------------------------------------------------------------------------------------
function saveContainerGroup()
{
    if (!$('#groupName').val()) {
        toast('Group Management', 'A group name is require to save a group', 'error');
        return;
    }

    let groupItemCount = 0;
    let params = '';
    $.each($('[id^=groupContainer-]'), function() {
        if ($(this).prop('checked')) {
            groupItemCount++;
            params += '&' + $(this).attr('id') + '=1';
        }
    });

    if (groupItemCount === 0 && !$('#groupDelete').prop('checked')) {
        toast('Group Management', 'At least one container needs to be assigned to a group', 'error');
        return;
    }

    loadingStart();
    $.ajax({
        type: 'POST',
        url: '../ajax/containers.php',
        data: '&m=saveContainerGroup&selection=' + $('#groupSelection').val() + '&name=' + $('#groupName').val() + '&delete=' + ($('#groupDelete').prop('checked') ? 1 : 0) + params,
        success: function (resultData) {
            if (resultData) {
                toast('Group Management', 'Error saving group: ' + resultData, 'error');
                loadingStop();
                return;
            }

            toast('Group Management', 'Group changes saved', 'success');
            $('#groupCloseBtn').click();
            initPage('containers');
        }
    });

}
// ---------------------------------------------------------------------------------------------