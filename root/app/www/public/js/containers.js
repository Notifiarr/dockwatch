function toggleAllContainers()
{
    $('.containers-check').prop('checked', $('#containers-toggle-all').prop('checked'));
    $('[attr-dockwatch=true]').prop('checked', false);
}
// ---------------------------------------------------------------------------------------------
function updateContainerOption(option, hash)
{
    $.ajax({
        type: 'POST',
        url: 'ajax/containers.php',
        data: '&m=updateContainerOption&hash=' + hash + '&option=' + option + '&setting=' + ($('#' + option + '-' + hash).prop('checked') ? 1 : 0),
        success: function (resultData) {
            toast('Container option', 'The container option setting has been saved', 'success');

            if (option == 'blacklist') {
                if ($('#' + option + '-' + hash).prop('checked')) {
                    $('#start-btn-' + hash + ', #restart-btn-' + hash + ', #stop-btn-' + hash).hide();
                } else {
                    $('#restart-btn-' + hash + ', #stop-btn-' + hash).show();
                }
            }

            if (option == 'restartUnhealthy') {
                if ($('#' + option + '-' + hash).prop('checked')) {
                    $('.restartUnhealthy-icon-' + hash).removeClass('text-warning').addClass('text-success');
                } else {
                    $('.restartUnhealthy-icon-' + hash).removeClass('text-success').addClass('text-warning');
                }
            }

            if (option == 'disableNotifications') {
                if ($('#disableNotifications-' + hash).prop('checked')) {
                    $('#disableNotifications-icon-' + hash).show();
                } else {
                    $('#disableNotifications-icon-' + hash).hide();
                }
            }
        }
    });
}
// ---------------------------------------------------------------------------------------------
function restoreContainerGroups()
{
    $('#group-restore-btn').hide();
    restoreGroups = true;
    initPage('containers');
}
// ---------------------------------------------------------------------------------------------
function containerFrequencyHelp()
{
    dialogOpen({
        id: 'containerFrequencyHelp',
        title: 'Frequency Help',
        size: 'lg',
        body: $('#containerFrequencyHelpDiv').html()
    });
}
// ---------------------------------------------------------------------------------------------
function frequencyCronEditor(frequency, hash, name)
{
    dialogOpen({
        id: "frequencyCronEditor",
        title: `Frequency Cron Editor (${name})`,
        size: "lg",
        body: $("#frequencyCronEditorDiv").html(),
    });

    $(`.modal-body #cron`).cron({
        expression: frequency,
        hash: hash,
        name: name,
        onChange: function (expression) {
            if (hash == "global") {
                $(`#globalSetting-updatesFrequency`).val(expression);
                return;
            }

            $(`#containers-frequency-${hash}`).val(expression);
        },
    });
}
// ---------------------------------------------------------------------------------------------
function openEditContainer(hash)
{
    $.ajax({
        type: 'POST',
        url: 'ajax/containers.php',
        data: '&m=openEditContainer&hash=' + hash,
        success: function (resultData) {
            dialogOpen({
                id: 'openEditContainer',
                title: 'Edit Container',
                size: 'xl',
                body: resultData
            });
        }
    });
}
// ---------------------------------------------------------------------------------------------
function applyContainerAction(hash, action)
{
    $('#massTrigger-' + hash).prop('checked', true);
    $('#massContainerTrigger').val(action);
    massApplyContainerTrigger();
}
// ---------------------------------------------------------------------------------------------
function updateContainerRowText(hash, data)
{
    $('#' + hash + '-control').html(data.control);
    $('#' + hash + '-update').html(data.update);
    $('#' + hash + '-state').html(data.state);
    $('#' + hash + '-mounts').html( data.mounts);
    $('#' + hash + '-length').html(data.length);
    $('#' + hash + '-cpu').html(data.cpu);
    $('#' + hash + '-cpu').prop('title', data.cpuTitle);
    $('#' + hash + '-mem').html(data.mem);
    $('#' + hash + '-health').html(data.health);

    hideContainerMounts(hash);
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
function massApplyContainerTrigger(dependencyTrigger = false)
{
    let retries = 0;
    let dependencies = [];
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

    $('#massTrigger-header').html((dependencyTrigger ? 'Dependency containers' : 'Containers') + ' to apply trigger to: ' + selected + '<br>');
    $('#massTrigger-spinner').show();
    $('#massTrigger-results').html('');

    $('#triggerAction').html($('#massContainerTrigger option:selected').text());

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
                $('#massTrigger-results').prepend(resultData.result);
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
            let massTriggerOption = $('#massContainerTrigger').val();

            if (c == selectedContainers.length) {
                if (massTriggerOption == 9) {
                    initPage('containers');
                } else {
                    $('#massContainerTrigger').val('0');
                    $('.containers-check').prop('checked', false);
                }

                $('#massTrigger-close-btn').show();
                $('#massTrigger-spinner').hide();

                if (dependencies.length) {
                    $('#massTrigger-results').prepend('Found some dependencies, applying trigger to them...<br>');

                    $.each(dependencies, function (index, dependency) {
                        $('[data-name=' + dependency + ']').prop('checked', true);
                    });

                    setTimeout(function () {
                        //-- IF THE PARENT WAS UPDATED, RE-CREATE THE DEPENDENCIES
                        if (massTriggerOption == '7') {
                            massTriggerOption = '12';
                        }

                        $('#massContainerTrigger').val(massTriggerOption);
                        $('#massTrigger-close-btn').click();

                        setTimeout(function () {
                            massApplyContainerTrigger(true);
                        }, 500);
                    }, 1000);
                }
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
                    retries = 0;
                    if (parseInt($('#massContainerTrigger').val()) == 5) {
                        $('#massTrigger-results').prepend(resultData.result + "\n");
                    } else {
                        updateContainerRowText(containerHash, resultData);
                        $('#massTrigger-results').prepend((c + 1) + '/' + selectedContainers.length + ': ' + resultData.result);
                    }

                    if (resultData.dependencies) {
                        $.each(resultData.dependencies, function (index, dependency) {
                            dependencies.push(dependency);
                        });
                    }
                    c++;

                    runTrigger();
                },
                error: function(jqhdr, textStatus, errorThrown) {
                    $('#massTrigger-results').prepend((c + 1) + '/' + selectedContainers.length + ': ' + containerName + ' ajax error (' + (errorThrown ? errorThrown : 'timeout') + ', check the console for more information using F12) ... Retrying in 5s<br>');

                    if (retries < 3) {
                        setTimeout(function () {
                            retries++;
                            runTrigger();
                        }, 5000);
                    }
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
            loadingStop();

            if (resultData) {
                toast('Group Management', 'Error saving group: ' + resultData, 'error');
                return;
            }

            toast('Group Management', 'Group changes saved', 'success');
            $('#groupCloseBtn').click();
            initPage('containers');
        }
    });

}
// ---------------------------------------------------------------------------------------------
function showContainerMounts(containerHash)
{
    $('#hide-mount-btn-' + containerHash + ', #mount-list-full-' + containerHash).show();
    $('#show-mount-btn-' + containerHash + ', #mount-list-preview-' + containerHash).hide();
    // <td>
    $('#' + containerHash + '-cpu, #' + containerHash + '-mem, #' + containerHash + '-update-td, #' + containerHash + '-frequency-td, #' + containerHash + '-hour-td').hide();
    $('#' + containerHash + '-mounts-td').attr('colspan', 5);
}
// ---------------------------------------------------------------------------------------------
function hideContainerMounts(containerHash)
{
    $('#show-mount-btn-' + containerHash + ', #mount-list-preview-' + containerHash).show();
    $('#hide-mount-btn-' + containerHash + ', #mount-list-full-' + containerHash).hide();
    // <td>
    $('#' + containerHash + '-cpu, #' + containerHash + '-mem, #' + containerHash + '-update-td, #' + containerHash + '-frequency-td, #' + containerHash + '-hour-td').show();
    $('#' + containerHash + '-mounts-td').attr('colspan', 0);
}
// ---------------------------------------------------------------------------------------------
function containerLogs(container)
{
    $.ajax({
        type: 'POST',
        url: '../ajax/containers.php',
        data: '&m=containerLogs&container=' + container,
        success: function (resultData) {
            dialogOpen({
                id: 'containerLogs',
                title: 'Container Logs',
                size: 'xl',
                body: resultData,
                onOpen: function () {

                }
            });
        }
    });
}
// ---------------------------------------------------------------------------------------------
function massChangeContainerUpdates(option)
{
    const selected = $('#container-updates-all').val();

    switch (option) {
        case 1: //-- SELECTED
            $.each($('.containers-check'), function () {
                if ($(this).prop('checked')) {
                    const hash = $(this).attr('id').replace('massTrigger-', '');
                    $('#containers-update-' + hash).val(selected)        
                }
            });
            break;
        case 2: //-- ALL
            $.each($('.container-updates'), function () {
                if ($('option[value=' + selected + ']', this).length) {
                    $(this).val(selected);
                }
            });
            break;
    }
}
// ---------------------------------------------------------------------------------------------
function massChangeFrequency(option)
{
    const frequency = $('#containers-frequency-all').val();

    switch (option) {
        case 1: //-- SELECTED
            $.each($('.containers-check'), function () {
                if ($(this).prop('checked')) {
                    const hash = $(this).attr('id').replace('massTrigger-', '');
                    $('#containers-frequency-' + hash).val(frequency)        
                }
            });
            break;
        case 2: //-- ALL
            $('.container-frequency').val(frequency)
            break;
    }
}
// ---------------------------------------------------------------------------------------------
