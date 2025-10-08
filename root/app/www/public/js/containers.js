function toggleAllContainers()
{
    $('.containers-check').prop('checked', $('#containers-toggle-all').prop('checked'));
    $('[attr-dockwatch=true]').prop('checked', false);
}
// ---------------------------------------------------------------------------------------------
function updateContainerOption(option, hash, setting)
{
    if (setting == '-1') {
        setting = $('#' + option).val();

        if (option == 'shutdownDelaySeconds' && (isNaN(parseInt(setting)) || parseInt(setting) < 5)) {
            toast('Container option', 'Shutdown delay needs to be a number and atleast 5 seconds long', 'error');
            return;
        }
    }

    $.ajax({
        type: 'POST',
        url: 'ajax/containers.php',
        data: '&m=updateContainerOption&hash=' + hash + '&option=' + option + '&setting=' + setting,
        success: function (resultData) {
            toast('Container option', 'The container option setting has been saved', 'success');

            switch (option) {
                case 'blacklist':
                    $('#restart-btn-' + hash + ', #stop-btn-' + hash).show();

                    if ($('#' + option + '-' + hash).prop('checked')) {
                        $('#start-btn-' + hash + ', #restart-btn-' + hash + ', #stop-btn-' + hash).hide();
                    }
                    break;
                case 'restartUnhealthy':
                    $('.restartUnhealthy-icon-' + hash).removeClass('text-success').addClass('text-warning');

                    if ($('#' + option + '-' + hash).prop('checked')) {
                        $('.restartUnhealthy-icon-' + hash).removeClass('text-warning').addClass('text-success');
                    }
                    break;
                case 'disableNotifications':
                    $('#disableNotifications-icon-' + hash).hide();

                    if ($('#disableNotifications-' + hash).prop('checked')) {
                        $('#disableNotifications-icon-' + hash).show();
                    }
                    break;
                case 'shutdownDelay':
                    $('#shutdownDelay-input-' + hash).prop('readonly', $('#shutdownDelay-input-' + hash).prop('readonly'));
                    break;
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

            if (hash == "autoRestart") {
                $(`#autoRestartFrequency`).val(expression);
                $(`#autoRestartFrequency`).change();
                return;
            }

            if (hash.startsWith("list-command")) {
                $(`#${hash}`).val(expression);
                $(`#${hash}`).change();
                return;
            }

            $(`#container-frequency-${hash}`).val(expression);
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
    //-- MENU OPTIONS
    $('[id^=' + hash + '-menu-]').hide();
    $('#' + hash + '-menu-start').html(data.start);
    $('#' + hash + '-menu-stop').html(data.stop);
    $('#' + hash + '-menu-restart').html(data.restart);
    $('#' + hash + '-menu-gui').html(data.gui);
    if (data.start) {
        $('#' + hash + '-menu-start').show();
    }
    if (data.stop) {
        $('#' + hash + '-menu-stop').show();
    }
    if (data.restart) {
        $('#' + hash + '-menu-restart').show();
    }
    if (data.gui) {
        $('#' + hash + '-menu-gui').show();
    }
    $('#' + hash + '-onlineIcon').removeClass().addClass(data.onlineClass).show();

    //-- ROW VALUES
    $('#' + hash + '-update').html(data.update);
    $('#' + hash + '-state').html(data.state);
    $('#' + hash + '-mounts').html(data.mounts);
    $('#' + hash + '-ports').html(data.ports);
    $('#' + hash + '-env').html(data.env);
    $('#' + hash + '-length').html(data.length);
    $('#' + hash + '-usage').html(data.cpu + "<br>" + data.mem);
    $('#' + hash + '-health').html(data.health);

    hideContainerMounts(hash);
    hideContainerPorts(hash);
    hideContainerEnv(hash);
}
// ---------------------------------------------------------------------------------------------
function controlContainer(containerHash, action)
{
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/containers.php',
        data: '&m=controlContainer&hash=' + containerHash + '&action=' + action,
        dataType: 'json',
        success: function (resultData) {
            updateContainerRowText(containerHash, resultData);
            pageLoadingStop();
            toast('Containers', 'Request processed: ' + action, 'success');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function massApplyContainerTrigger(dependencyTrigger = false, action = 0, container = '')
{
    if (action) {
        $('#massContainerTrigger').val(action);
    }

    if (container) {
        $.each($('[id^=massTrigger-]'), function () {
            $(this).prop('checked', false)
        });

        $('#massTrigger-' + container).prop('checked', true);
    }

    let retries = 0;
    let dependencies = [];
    let selected = 0;
    $.each($('[id^=massTrigger-]'), function () {
        if ($(this).prop('checked')) {
            selected++;
        }
    });

    if (!selected) {
        toast('Container Actions', 'Please select at least one container before trying to do this.', 'error');
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
            url: 'ajax/containers.php',
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
                url: 'ajax/containers.php',
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
                    setScreenSizeVars();
                },
                error: function(jqhdr, textStatus, errorThrown) {
                    $('#massTrigger-results').prepend((c + 1) + '/' + selectedContainers.length + ': ' + containerName + ' ajax error (' + (errorThrown ? errorThrown : 'timeout') + ', check the console for more information using F12) ... Retrying in 5s<br>');

                    if (retries <= 3) {
                        setTimeout(function () {
                            retries++;
                            runTrigger();
                        }, 5000);
                    } else {
                        $('#massContainerTrigger').val('0');
                        $('.containers-check').prop('checked', false);
                        $('#massTrigger-close-btn').show();
                        $('#massTrigger-spinner').hide();
                    }

                    setScreenSizeVars();
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
        url: 'ajax/containers.php',
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
    if ($('#groupSelection').val() != 0) {
        $('#deleteGroupContainer').show();
        $('#groupName').val($('#groupSelection option:selected').text());
    } else {
        $('#groupName').val('');
    }

    $.ajax({
        type: 'POST',
        url: 'ajax/containers.php',
        data: '&m=loadContainerGroup&groupId=' + $('#groupSelection').val(),
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
        params += '&' + $(this).attr('id') + '=' + ($(this).prop('checked') ? 1 : 0);

        if ($(this).prop('checked')) {
            groupItemCount++;
        }
    });

    if (groupItemCount === 0 && !$('#groupDelete').prop('checked')) {
        toast('Group Management', 'At least one container needs to be assigned to a group', 'error');
        return;
    }

    pageLoadingStart();
    $.ajax({
        type: 'POST',
        url: 'ajax/containers.php',
        data: '&m=saveContainerGroup&groupId=' + $('#groupSelection').val() + '&name=' + $('#groupName').val() + '&delete=' + ($('#groupDelete').prop('checked') ? 1 : 0) + params,
        success: function (resultData) {
            pageLoadingStop();

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
function openUpdateOptions()
{
    $('#updateOptions-containers').html('Fetching container update settings...');

    $('#updateOptions-modal').modal({
        keyboard: false,
        backdrop: 'static'
    });

    $('#updateOptions-modal').hide();
    $('#updateOptions-modal').modal('show');

    pageLoadingStart();
    $.ajax({
        type: 'POST',
        url: 'ajax/containers.php',
        data: '&m=openUpdateOptions',
        success: function (resultData) {
            $('#updateOptions-containers').html(resultData);
            pageLoadingStop();
        }
    });
}
// ---------------------------------------------------------------------------------------------
function saveUpdateOptions()
{
    pageLoadingStart();

    let params = '';
    $.each($('[id^=container-]'), function () {
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
        url: 'ajax/containers.php',
        data: '&m=saveUpdateOptions' + params,
        success: function (resultData) {
            pageLoadingStop();
            toast('Update options', 'Update settings saved', 'success');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function showContainerMounts(containerHash)
{
    $('#hide-mount-btn-' + containerHash + ', #mount-list-full-' + containerHash).show();
    $('#show-mount-btn-' + containerHash + ', #mount-list-preview-' + containerHash).hide();
    // <td>
    $('#' + containerHash + '-usage, #' + containerHash + '-env-td, #' + containerHash + '-ports-td').hide();
    $('#' + containerHash + '-mounts-td').attr('colspan', 5);
}
// ---------------------------------------------------------------------------------------------
function hideContainerMounts(containerHash)
{
    $('#show-mount-btn-' + containerHash + ', #mount-list-preview-' + containerHash).show();
    $('#hide-mount-btn-' + containerHash + ', #mount-list-full-' + containerHash).hide();
    // <td>
    $('#' + containerHash + '-usage, #' + containerHash + '-env-td, #' + containerHash + '-ports-td').show();
    $('#' + containerHash + '-mounts-td').attr('colspan', 0);
}
// ---------------------------------------------------------------------------------------------
function showContainerEnv(containerHash)
{
    $('#hide-env-btn-' + containerHash + ', #env-list-full-' + containerHash).show();
    $('#show-env-btn-' + containerHash + ', #env-list-preview-' + containerHash).hide();
    // <td>
    $('#' + containerHash + '-usage, #' + containerHash + '-ports-td').hide();
    $('#' + containerHash + '-env-td').attr('colspan', 5);
}
// ---------------------------------------------------------------------------------------------
function hideContainerEnv(containerHash)
{
    $('#show-env-btn-' + containerHash + ', #env-list-preview-' + containerHash).show();
    $('#hide-env-btn-' + containerHash + ', #env-list-full-' + containerHash).hide();
    // <td>
    $('#' + containerHash + '-usage, #' + containerHash + '-ports-td').show();
    $('#' + containerHash + '-env-td').attr('colspan', 0);
}
// ---------------------------------------------------------------------------------------------
function showContainerPorts(containerHash)
{
    $('#hide-port-btn-' + containerHash + ', #port-list-full-' + containerHash).show();
    $('#show-port-btn-' + containerHash + ', #port-list-preview-' + containerHash).hide();
    // <td>
    $('#' + containerHash + '-usage').hide();
    $('#' + containerHash + '-ports-td').attr('colspan', 5);
}
// ---------------------------------------------------------------------------------------------
function hideContainerPorts(containerHash)
{
    $('#show-port-btn-' + containerHash + ', #port-list-preview-' + containerHash).show();
    $('#hide-port-btn-' + containerHash + ', #port-list-full-' + containerHash).hide();
    // <td>
    $('#' + containerHash + '-usage').show();
    $('#' + containerHash + '-ports-td').attr('colspan', 0);
}
// ---------------------------------------------------------------------------------------------
function containerLogs(container)
{
    pageLoadingStart();
    $.ajax({
        type: 'POST',
        url: 'ajax/containers.php',
        data: '&m=containerLogs&container=' + container,
        success: function (resultData) {
            dialogOpen({
                id: 'containerLogs',
                title: 'Container Logs',
                size: 'xl',
                body: `<pre class="bg-dark primary p-3 rounded" style="color: white; max-height: 500px; overflow: auto; white-space: pre-wrap;">${resultData}</pre>`,
                onOpen: function () {
                    pageLoadingStop();
                }
            });
        }
    });
}
// ---------------------------------------------------------------------------------------------
function massChangeContainerUpdates(option)
{
    const selected = $('#container-update-all').val();

    switch (option) {
        case 1: //-- SELECTED
            $.each($('.container-update-checkbox'), function () {
                if ($(this).prop('checked')) {
                    const hash = $(this).attr('id').match(/container-update-(.+)-checkbox/);
                    $('#container-update-' + hash[1]).val(selected);
                }
            });
            break;
        case 2: //-- ALL
            $.each($('.container-update'), function () {
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
    const frequency = $('#container-frequency-all').val();

    switch (option) {
        case 1: //-- SELECTED
            $.each($('.container-update-checkbox'), function () {
                if ($(this).prop('checked')) {
                    const hash = $(this).attr('id').match(/container-update-(.+)-checkbox/);
                    $('#container-frequency-' + hash[1]).val(frequency);
                }
            });
            break;
        case 2: //-- ALL
            $('.container-frequency').val(frequency)
            break;
    }
}
// ---------------------------------------------------------------------------------------------
function massChangeMinAge(option)
{
    const minAge = $('#container-update-minage-all').val();

    switch (option) {
        case 1: //-- SELECTED
            $.each($('.container-update-checkbox'), function () {
                if ($(this).prop('checked')) {
                    const hash = $(this).attr('id').match(/container-update-(.+)-checkbox/);
                    $('#container-update-minage-' + hash[1]).val(minAge);
                }
            });
            break;
        case 2: //-- ALL
            $('.container-update-minage').val(minAge);
            break;
    }
}
// ---------------------------------------------------------------------------------------------
function containerInfo(hash)
{
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/containers.php',
        data: '&m=containerInfo&hash=' + hash,
        success: function (resultData) {
            new popup('#left-slider', {
                content: resultData,
                duration: 500,
                classes: 'bg-secondary p-2',
            }).popupLeft();

            pageLoadingStop();
        }
    });
}
// -------------------------------------------------------------------------------------------
function containerShell(container)
{
    $.ajax({
        type: 'POST',
        url: 'ajax/containers.php',
        data: '&m=containerShell&container=' + container,
        success: function (resultData) {
            dialogOpen({
                id: 'xtermShell',
                title: `Container Shell (${container})`,
                size: 'xl',
                body: $('#xtermShellDiv').html()
            });

            const terminal = new Terminal({
                cursorBlink: true,
                theme: {
                    background: '#212529',
                    foreground: '#fff'
                },
                fontSize: 14,
                fontFamily: 'JetBrains Mono',
                convertEol: true,
                scrollback: 1000,
                rendererType: 'canvas'
            });

            let fitAddon = null;
            if (window.FitAddon) {
                fitAddon = new window.FitAddon.FitAddon();
                terminal.loadAddon(fitAddon);
            }

            const writeMotd = (terminal) => {
                terminal.writeln(`\x1B[1;34mDockwatch Shell - Container: ${container}\x1B[0m\r\n`);
            };

            const terminalContainer = document.getElementById('terminalContainer');
            terminal.open(terminalContainer);
            writeMotd(terminal);

            terminal.element.style.padding = '12px';
            terminal.writeln('Connecting to container shell... This can take a few moments!\r\n');

            //-- FIT TERMINAL FUNC (THIS TIME IT SHOULD WORK)
            const fitTerminal = () => {
                if (fitAddon) {
                    //-- SPAM IT
                    for (let i = 0; i < 3; i++) {
                        fitAddon.fit();
                    }
                    //-- TRIGGER AGAIN AFTER A MOMENT
                    setTimeout(() => fitAddon.fit(), 100);
                    setTimeout(() => fitAddon.fit(), 300);
                }
            }

            const socket = new WebSocket(`${JSON.parse(resultData)['url']}`);
            let msgCount = 0;

            socket.onopen = () => {
                terminal.writeln('WebSocket connection established');
                setTimeout(() => {
                    if (socket.readyState === WebSocket.OPEN) {
                        terminal.focus();
                        fitTerminal();
                    }
                }, 500);
            };

            socket.onclose = () => {
                terminal.writeln(`\r\nWebSocket connection closed.`);
                if (msgCount === 0) {
                    terminal.writeln(`Possible reasons:\n- WebSocket Port (default :9910) not reachable\n- Connect URL is incorrect\n- Socket token is invalid`);
                }
            };

            socket.onmessage = (event) => {
                msgCount++;
                try {
                    const data = JSON.parse(event.data);
                    if (data.error) {
                        terminal.writeln(`\r\nError: ${data.error}\r\n`);
                        return;
                    }
                    if (data.success) {
                        terminal.writeln(`${data.message}`);
                        if (data.message.toString().startsWith("Connected to container")) {
                            fitTerminal();

                            setTimeout(() => {
                                socket.send(JSON.stringify({
                                    action: 'resize',
                                    cols: terminal.cols,
                                    rows: terminal.rows
                                }));

                                fitTerminal();
                            }, 1000);
                        }
                        return;
                    }
                    if (data.type === 'pwd') {
                        currentWorkingDir = data.path;
                        return;
                    }
                    if (data.type === 'stdout' || data.type === 'stderr') {
                        if (!atob(data.data).toString().includes("stty")) {
                            terminal.write(atob(data.data));
                        }
                    }
                    if (data.type === 'exit') {
                        terminal.writeln(`\r\nContainer shell exited with code ${data.code}\r\n`);
                        if (data.message) {
                            terminal.writeln(data.message);
                        }
                        dialogClose('xtermShell');
                    }
                } catch (e) {
                    terminal.writeln(`\r\nError processing server message: ${e.message}\r\n`);
                }
            };

            const dataHandler = terminal.onData(data => {
                if (socket.readyState === WebSocket.OPEN) {
                    socket.send(JSON.stringify({
                        action: 'command',
                        command: data
                    }));
                }
            });

            terminal.attachCustomKeyEventHandler((e) => {
                if (e.ctrlKey && e.shiftKey && e.key === 'C') {
                    const selection = terminal.getSelection();
                    if (selection) {
                        navigator.clipboard.writeText(selection);
                    }
                    return false;
                }
                if (e.ctrlKey && e.shiftKey && e.key === 'V') {
                    navigator.clipboard.readText().then(text => {
                        if (socket.readyState === WebSocket.OPEN) {
                            socket.send(JSON.stringify({
                                action: 'command',
                                command: text
                            }));
                        }
                    });
                    return false;
                }
                return true;
            });

            const resizeHandler = terminal.onResize(size => {
                if (socket.readyState === WebSocket.OPEN) {
                    socket.send(JSON.stringify({
                        action: 'resize',
                        cols: size.cols,
                        rows: size.rows
                    }));
                }
                fitTerminal();
            });

            window.addEventListener('resize', () => {
                fitTerminal();
            });

            //-- TRY TO FIT WHEN MODAL IS SHOWN
            $('#xtermShell').on('shown.bs.modal', function () {
                fitTerminal();
            });

            window.activeTerminalHandlers = {
                dataHandler: dataHandler,
                resizeHandler: resizeHandler
            };

            $('#xtermShell').on('hidden.bs.modal', function () {
                if (socket.readyState === WebSocket.OPEN) {
                    socket.close();
                }
                if (window.activeTerminalHandlers.dataHandler) {
                    window.activeTerminalHandlers.dataHandler.dispose();
                }
                if (window.activeTerminalHandlers.resizeHandler) {
                    window.activeTerminalHandlers.resizeHandler.dispose();
                }
                window.activeTerminalHandlers = {};
                terminal.dispose();
            });
        }
    });
}
// -------------------------------------------------------------------------------------------
function registryLogin(registry)
{
    const resetFields = () => {
        $('#registryUsername').val('');
        $('#registryPassword').val('');
    };

    const getFieldValues = () => {
        return {
            url: $('#registryUrl').val(),
            username: $('#registryUsername').val(),
            password: $('#registryPassword').val()
        };
    };

    if (registry) {
        dialogOpen({
            id: 'registryLogin',
            title: 'Registry Login (' + registry + ')',
            size: 'md',
            body: $('#registryLoginDiv').html(),
            onOpen: function () {
                $('#registryUrl').val(registry);
                resetFields();
            }
        });
        return;
    }

    let fields = getFieldValues();
    if (!fields['username']) {
        toast('Registry Login', 'Username is required', 'error');
        return;
    }

    if (!fields['password']) {
        toast('Registry Login', 'Password is required', 'error');
        return;
    }

    dialogClose('registryLogin');

    pageLoadingStart();
    $.ajax({
        type: 'POST',
        url: 'ajax/containers.php',
        data: '&m=registryLogin&registry=' + fields['url'] + '&username=' + fields['username'] + '&password=' + fields['password'],
        success: function (resultData) {
            pageLoadingStop();
            resetFields();

            if (resultData.match(/unauthorized/)) {
                toast('Registry Login', 'Unauthorized login, wrong username or password', 'error');
                return;
            }

            if (resultData.match(/Login Succeeded/)) {
                toast('Registry Login', 'Saved login credentials for registry ' + fields['url'], 'success');
                return;
            }

            console.log(`Failed to login to docker registry: ${resultData}`);
            toast('Registry Login', 'Unknown error check F12 console for more info', 'error');
        }
    });
}
// -------------------------------------------------------------------------------------------
function saveContainerGuiLink(hash)
{
    $.ajax({
        type: 'POST',
        url: 'ajax/containers.php',
        data: '&m=saveContainerGuiLink&hash=' + hash + '&link=' + $('#containerGuiLink').val(),
        success: function (resultData) {
            toast('Container GUI', 'Custom GUI link saved', 'info');
        }
    });
}
// -------------------------------------------------------------------------------------------
