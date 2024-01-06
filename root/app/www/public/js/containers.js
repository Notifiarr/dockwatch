function updateContainerRowText(hash, data, refresh = false)
{
    $('#' + hash + '-control').html((refresh ? 'Updating' : data.control));
    $('#' + hash + '-update').html((refresh ? 'Updating' : data.update));
    $('#' + hash + '-state').html((refresh ? 'Updating' : data.state));
    $('#' + hash + '-running').html((refresh ? 'Updating' : data.running));
    $('#' + hash + '-status').html((refresh ? 'Updating' : data.status));
    $('#' + hash + '-cpu').html((refresh ? 'Updating' : data.cpu));
    $('#' + hash + '-cpu').prop('title', (refresh ? 'Updating' : data.cpu));
    $('#' + hash + '-mem').html((refresh ? 'Updating' : data.mem));
    $('#' + hash + '-health').html((refresh ? 'Updating' : data.health));
}
// ---------------------------------------------------------------------------------------------
function updateContainerRows()
{
    $.ajax({
        type: 'POST',
        url: '../ajax/containers.php',
        data: '&m=updateContainerRows',
        dataType: 'json',
        success: function (resultData) {
            $.each(resultData, function() {
                updateContainerRowText(this['hash'], this['row']);
            });
        }
    });
}
// ---------------------------------------------------------------------------------------------
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
            updateContainerRowText(containerHash, resultData);
            loadingStop();
            toast('Containers', 'Request processed: ' + action, 'success');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function massApplyContainerTrigger()
{
    let selected = 0;
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
        let selectedContainers = [];
        $.each($('[id^=massTrigger-]'), function () {
            if ($(this).prop('checked')) {
                const containerName = $(this).attr('data-name');
                const containerHash = $(this).attr('id').replace('massTrigger-', '');
                selectedContainers.push({'containerName': containerName, 'containerHash': containerHash});
            }
        });

        let c = 0;
        function runTrigger()
        {
            if (c == selectedContainers.length) {
                $('#massContainerTrigger').val('0');
                $('.containers-check').prop('checked', false);
                $('#massTrigger-close-btn').show();
                $('#massTrigger-spinner').hide();
                return;
            }

            const containerName = selectedContainers[c]['containerName'];
            const containerHash = selectedContainers[c]['containerHash'];

            $.ajax({
                type: 'POST',
                url: '../ajax/containers.php',
                data: '&m=massApplyContainerTrigger&trigger=' + $('#massContainerTrigger').val() + '&hash=' + containerHash,
                dataType: 'json',
                timeout: 600000,
                success: function (resultData) {
                    if (parseInt($('#massContainerTrigger').val()) == 5) {
                        $('#massTrigger-results').append(resultData.result + "\n");
                    } else {
                        updateContainerRowText(containerHash, resultData);
                        $('#massTrigger-results').prepend((c + 1) + '/' + selectedContainers.length + ': ' + resultData.result);
                    }

                    c++;

                    runTrigger();
                },
                error: function(jqhdr, textStatus, errorThrown) {
                    $('#massTrigger-results').prepend((c + 1) + '/' + selectedContainers.length + ': ' + containerName + ' ajax error (' + errorThrown + ')<br>');
                    c++;

                    runTrigger();
                }
            });
        }
        runTrigger();
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