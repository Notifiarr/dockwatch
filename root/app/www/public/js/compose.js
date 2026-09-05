let composeEditor   = null;
let composeGenSocket = null;
// ---------------------------------------------------------------------------------------------
function initComposeEditor(id, content, readOnly = false)
{
    if (composeEditor) {
        composeEditor.destroy();
    }

    const isDark = document.body.dataset.bsTheme !== 'light';
    const editor = ace.edit(id);

    editor.session.setMode('ace/mode/yaml');
    editor.setTheme(isDark ? 'ace/theme/github_dark' : 'ace/theme/github');
    editor.setValue(content || '', -1);
    editor.session.getUndoManager().reset();
    editor.setReadOnly(readOnly);

    if (!readOnly) {
        ace.require('ace/ext/language_tools');
        editor.setOptions({
            fontSize: '13px',
            tabSize: 2,
            useSoftTabs: true,
            wrap: true,
            showPrintMargin: false,
            enableBasicAutocompletion: true,
            enableLiveAutocompletion: true,
            minLines: 20,
            maxLines: 40
        });
    } else {
        editor.setOptions({
            fontSize: '13px',
            wrap: true,
            showPrintMargin: false,
            maxLines: 40
        });
    }

    composeEditor = editor;
}
// ---------------------------------------------------------------------------------------------
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
                body: resultData,
                onOpen: function () {
                    initComposeEditor('compose-add-editor', $('#compose-add-data').val());
                },
                onClose: function () {
                    if (composeEditor) {
                        composeEditor.destroy();
                        composeEditor = null;
                    }
                }
            });
        }
    });
}
// ---------------------------------------------------------------------------------------------
function composeAdd()
{
    const name  = $('#new-compose-name').val();
    const value = composeEditor ? composeEditor.getValue() : '';

    if (!name || !value) {
        toast('Compose', 'The name and compose data are required to save.', 'error');
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'ajax/compose.php',
        data: '&m=composeAdd&name=' + fixedEncodeURIComponent(name) + '&compose=' + fixedEncodeURIComponent(value),
        success: function (resultData) {
            if (resultData.startsWith('Failed')) {
                toast('Compose', resultData, 'error');
                return;
            }

            if (composeEditor) {
                composeEditor.destroy();
                composeEditor = null;
            }
            dialogClose('composeAdd');
            initPage('compose');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function composeSave(composePath)
{
    const value = composeEditor ? composeEditor.getValue() : '';

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

    //-- WEBSOCKET STREAM SUPPORTED ACTIONS
    if (['composeUp', 'composeDown', 'composePull', 'composeStop', 'composeRestart'].includes(action)) {
        composeStream(path, action);
        return;
    }

    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/compose.php',
        data: '&m=' + action + '&composePath=' + path,
        success: function (resultData) {
            switch (action) {
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
function composeStream(path, action)
{
    let label = '';
    path = path.match(/[^/]+$/)[0];

    switch (action) {
        case 'composeUp':
            label = 'Up stack ' + path;
            break;
        case 'composeDown':
            label = 'Down stack ' + path;
            break;
        case 'composePull':
            label = 'Pull stack ' + path;
            break;
        case 'composeStop':
            label = 'Stop stack ' + path;
            break;
        case 'composeRestart':
            label = 'Restart stack ' + path;
            break;
    }

    dialogOpen({
        id: 'composeStream',
        title: 'Compose: ' + label,
        size: 'xl',
        body: '<pre class="bg-dark primary p-3 rounded" style="color: white; max-height: 500px; overflow: auto; white-space: pre-wrap;" id="compose-stream-output">(running...)\n</pre>'
    });

    $.ajax({
        type: 'POST',
        url: 'ajax/compose.php',
        data: '&m=composeToken',
        dataType: 'json',
        success: function (resultData) {
            if (resultData.error) {
                toast('Compose', label + ' failed<br>' + resultData.error, 'error');
                $('#compose-stream-output').text('Error: ' + resultData.error);
                return;
            }

            const socket = new WebSocket(resultData.url, ['dockwatch-ws', resultData.token]);

            socket.onopen = function () {
                socket.send(JSON.stringify({ action: 'compose', composeAction: action, path }));
            };

            socket.onmessage = function (event) {
                const outputEl = document.getElementById('compose-stream-output');

                try {
                    const data = JSON.parse(event.data);
                    if (data.type === 'done') {
                        socket.close();
                        if (data.code === 0) {
                            toast('Compose', label + ' was completed', 'success');
                            $('#compose-stream-output').text($('#compose-stream-output').text() + '\n[complete]');

                            setTimeout(() => dialogClose('composeStream'), 300);
                        } else {
                            toast('Compose', label + ' failed<br>' + (data.message || 'unknown error'), 'error');
                            $('#compose-stream-output').text($('#compose-stream-output').text() + '\n[failed]');
                        }
                        return;
                    }
                    if (data.type === 'error') {
                        socket.close();
                        toast('Compose', label + ' failed<br>' + data.message, 'error');
                        $('#compose-stream-output').text($('#compose-stream-output').text() + '\nError: ' + data.message);
                        return;
                    }
                } catch (e) {
                    //-- RAW STREAMED TEXT
                    if (outputEl) {
                        outputEl.textContent += event.data;
                        outputEl.scrollTop = outputEl.scrollHeight;
                    }
                }
            };

            socket.onerror = function () {
                toast('Compose', label + ' websocket error, see dev console', 'error');
            };

            socket.onclose = function () {
                initPage('compose');
            };
        },
        error: function () {
            toast('Compose', label + ' could not obtain a websocket token', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function composeGenerate(containerNames)
{
    const label = 'Generate compose';

    //-- RENDER INSIDE THE ALREADY-OPEN MASS TRIGGER MODAL
    $('#massTrigger-results').html(
        '<pre class="bg-dark primary p-3 rounded" style="color: white; max-height: 500px; overflow: auto; white-space: pre-wrap;" id="compose-gen-output">(running...)\n</pre>'
        + '<div id="compose-gen-editor-wrap" style="display: none; margin-top: 8px;">'
        + '<div id="compose-gen-editor" style="height: 500px;"></div>'
        + '<div class="text-center mt-3">'
        + '<button id="compose-gen-copy-btn" class="btn btn-outline-success" onclick="composeGenCopy()">Copy to clipboard</button>'
        + '</div></div>'
    );

    $.ajax({
        type: 'POST',
        url: 'ajax/compose.php',
        data: '&m=composeToken',
        dataType: 'json',
        success: function (resultData) {
            if (resultData.error) {
                toast('Compose', label + ' failed<br>' + resultData.error, 'error');
                $('#compose-gen-output').text('Error: ' + resultData.error);
                return;
            }

            composeGenSocket = new WebSocket(resultData.url, ['dockwatch-ws', resultData.token]);
            const socket    = composeGenSocket;

            socket.onopen = function () {
                socket.send(JSON.stringify({ action: 'compose', composeAction: 'composeGenerate', container: containerNames }));
            };

            socket.onmessage = function (event) {
                const outputEl = document.getElementById('compose-gen-output');

                try {
                    const data = JSON.parse(event.data);

                    if (data.type === 'compose-gen') {
                        $('#compose-gen-output').hide();
                        $('#compose-gen-editor-wrap').show();
                        initComposeEditor('compose-gen-editor', data.compose || '', true);
                        return;
                    }

                    if (data.type === 'done') {
                        socket.close();
                        composeGenSocket = null;
                        if (data.code !== 0) {
                            toast('Compose', label + ' failed<br>' + (data.message || 'unknown error'), 'error');
                        }
                        return;
                    }

                    if (data.type === 'error') {
                        socket.close();
                        composeGenSocket = null;
                        toast('Compose', label + ' failed<br>' + data.message, 'error');
                        if (outputEl) {
                            outputEl.textContent += '\nError: ' + data.message;
                        }
                        return;
                    }
                } catch (e) {
                    //-- RAW STREAMED TEXT
                    if (outputEl) {
                        outputEl.textContent += event.data;
                        outputEl.scrollTop = outputEl.scrollHeight;
                    }
                }
            };

            socket.onerror = function () {
                composeGenSocket = null;
                toast('Compose', label + ' websocket error, see dev console', 'error');
            };
        },
        error: function () {
            toast('Compose', label + ' could not obtain a websocket token', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
$(document).on('hidden.bs.modal', '#massTrigger-modal', function () {
    if (composeGenSocket) {
        composeGenSocket.close();
        composeGenSocket = null;
    }

    if (composeEditor) {
        composeEditor.destroy();
        composeEditor = null;
    }
});
// ---------------------------------------------------------------------------------------------
function composeGenCopy()
{
    const value = composeEditor ? composeEditor.getValue() : '';

    if (!value) {
        toast('Compose', 'Nothing to copy', 'error');
        return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(value).then(function () {
            toast('Compose', 'Compose copied to clipboard', 'success');
        }, function () {
            toast('Compose', 'Failed to copy to clipboard', 'error');
        });
    } else {
        const textArea     = document.createElement('textarea');
        textArea.value     = value;
        textArea.style.cssText = 'position:fixed;opacity:0;';
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        toast('Compose', 'Compose copied to clipboard', 'success');
    }
}
// ---------------------------------------------------------------------------------------------
function composeOutputDialog(command, path, output, isHtml)
{
    const outputHTML = isHtml
        ? '<div class="mt-2" style="max-height: 70vh; overflow: auto;">' + (output || '<p class="bg-dark primary p-3 rounded" style="color: white; max-height: 500px; overflow: auto; white-space: pre-wrap;">(no output)</p>') + '</div>'
        : '<pre class="bg-dark primary p-3 rounded" style="color: white; max-height: 500px; overflow: auto; white-space: pre-wrap;">' + $('<div>').text(output || '(no output)').html() + '</pre>';

    const body = '<code class="small-text text-muted">' + $('<div>').text(path).html() + '</code>' + outputHTML;

    dialogOpen({
        id: 'compose' + command,
        title: 'Compose: ' + command,
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
                    initComposeEditor('compose-data-editor', $('#compose-data').val());
                },
                onClose: function () {
                    if (composeEditor) {
                        composeEditor.destroy();
                        composeEditor = null;
                    }
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
