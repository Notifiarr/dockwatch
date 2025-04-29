function loadCommand(command, container, parameters, servers, unload = false) {
    $('#command').val(!unload ? command : 'docker-inspect');
    $('#command-container').val(!unload ? container : '');
    $('#command-parameters').val(!unload ? parameters : '');
    $.each($('[id^=command-]'), function() {
        if (servers == $(this).prop('id').replace('command-', '') && !unload) {
            $(this).prop('checked', true);
            return;
        }

        $(this).prop('checked', false);
    });
}
// ---------------------------------------------------------------------------------------------
function runCommand(payload)
{
    if (payload) {
        dialogClose('listCommands');

        const { command, container, parameters, servers } = payload;
        loadCommand(command, container, parameters, servers);
    }

    let servers = '';
    $.each($('[id^=command-]'), function() {
        if ($(this).prop('checked')) {
            const serverIndex = $(this).prop('id').replace('command-', '');
            servers += (servers ? ',' : '') + serverIndex;
        }
    });

    if (!servers || !$('#command').val()) {
        toast('Commands', 'The command and at least one server selection is required', 'error');
        return;
    }

    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/commands.php',
        data: '&m=runCommand&command=' + $('#command').val() + '&container=' + $('#command-container').val() + '&parameters=' + $('#command-parameters').val() + '&servers=' + servers,
        success: function (resultData) {
            $('#commandResults').html(resultData);
            pageLoadingStop();
        }
    });
}
// ---------------------------------------------------------------------------------------------
function saveCommand(id)
{
    if (id) {
        $.ajax({
            type: 'POST',
            url: '../ajax/commands.php',
            data: '&m=saveCommand&id=' + id + '&parameters=' + $(`#list-command-${id}-parameters`).val() + '&servers=' + $(`#list-command-${id}-servers`).val() + '&cron=' + $(`#list-command-${id}-cron`).val(),
            success: function (resultData) {
                if (JSON.parse(resultData)) {
                    return toast('Commands', 'The command was successfully saved', 'success');
                }
                toast('Commands', 'Failed to save command', 'error');
            }
        });
        return;
    }

    let servers = '';
    $.each($('[id^=command-]'), function() {
        if ($(this).prop('checked')) {
            const serverIndex = $(this).prop('id').replace('command-', '');
            servers += (servers ? ',' : '') + serverIndex;
        }
    });

    if (!servers || !$('#command').val()) {
        toast('Commands', 'The command and at least one server selection is required', 'error');
        return;
    }

    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/commands.php',
        data: '&m=saveCommand&command=' + $('#command').val() + '&container=' + $('#command-container').val() + '&parameters=' + $('#command-parameters').val() + '&servers=' + servers,
        success: function (resultData) {
            pageLoadingStop();

            if (JSON.parse(resultData)) {
                return toast('Commands', 'The command was successfully saved', 'success');
            }
            toast('Commands', 'Failed to save command', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function listCommand()
{
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/commands.php',
        data: '&m=listCommand',
        success: function (resultData) {
            pageLoadingStop();

            dialogOpen({
                id: 'listCommands',
                title: 'Saved commands list',
                size: 'xl',
                body: resultData
            });
        }
    });
}
// ---------------------------------------------------------------------------------------------
function editCommand(id)
{
    let editable = [
        `list-command-${id}-parameters`,
        `list-command-${id}-servers`,
        `list-command-${id}-cron`,
        `list-command-${id}-cron-clear`,
    ];

    let buttons = $(`#list-command-${id}-buttons`);
    let saveButton = $(`#list-command-${id}-save`);
    let hideElements = (show = false) => {
        $.each(editable, function() {
            console.log(this);
            if (this.includes('cron-clear')) {
                $(`#${this}`).css('display', show ? 'none' : '');
                return;
            }

            $(`#${this}`).prop('disabled', show);
        });
    };

    if (!$(`#list-command-${id}-buttons`).is(`:visible`)) {
        buttons.show();
        saveButton.hide();
        hideElements(true);
        saveCommand(id);
        return;
    }

    buttons.hide();
    saveButton.show();
    hideElements();
}
// ---------------------------------------------------------------------------------------------
function deleteCommand(id)
{
    $.ajax({
        type: 'POST',
        url: '../ajax/commands.php',
        data: '&m=deleteCommand&id=' + id,
        success: function (resultData) {
            if (JSON.parse(resultData)) {
                $(`#list-command-${id}`).remove();
                return toast('Commands', 'The command was successfully deleted', 'success');
            }
            toast('Commands', 'Failed to delete command', 'error');
        }
    });
}
