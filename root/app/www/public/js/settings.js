function saveGlobalSettings()
{
    pageLoadingStart();

    let params = '';
    $.each($('[id^=globalSetting-]'), function () {
        let val = '';
        if ($(this).is(':checkbox') || $(this).is(':radio')) {
            val = $(this).prop('checked') ? 1 : 0;
        } else {
            val = $(this).val();
        }

        params += '&' + $(this).attr('id').replace('globalSetting-', '') + '=' + val;
    });

    $.ajax({
        type: 'POST',
        url: 'ajax/settings.php',
        data: '&m=saveGlobalSettings' + params,
        dataType: 'json',
        success: function (resultData) {
            if (resultData.error) {
                toast('Settings', resultData.error, 'error');
            } else  {
                toast('Settings', 'Global settings saved on server ' + resultData.server, 'success');
                initPage('settings');
            }

            pageLoadingStop();
        }
    });

}
// ---------------------------------------------------------------------------------------------
function unlinkRemoteServer(serverId)
{
    if (confirm('Are you sure you want to remove this remote server?')) {
        $.ajax({
            type: 'POST',
            url: 'ajax/settings.php',
            data: '&m=unlinkRemoteServer&id=' + serverId,
            success: function (resultData) {
                $('#remoteServer-' + serverId).remove();
                toast('Remote server', 'The remote server has been removed', 'success');
            }
        });
    }
}
// ---------------------------------------------------------------------------------------------
function updateSetting(setting, value)
{
    $.ajax({
        type: 'POST',
        url: 'ajax/settings.php',
        data: '&m=updateSetting&setting=' + setting + '&value=' + value,
        success: function (resultData) {
            if (setting == 'defaultTheme') {
                reload();
            } else {
                toast('Settings', 'The setting has been updated', 'success');
            }
        }
    });
}
// ---------------------------------------------------------------------------------------------
function freshStartMigration()
{
    if (!confirm('Are you sure you want to fresh start? If you confirm, once saved you need to restart the container to apply the migration.')) {
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'ajax/settings.php',
        data: '&m=updateSetting&setting=migration&value=022',
        success: function () {
            $('#globalSetting-migration').val('022');
            toast('Settings', 'Migration set to 022, restart the container to apply the migration', 'success');
            initPage('settings');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function backupDatabase()
{
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/database.php',
        data: '&m=backup',
        dataType: 'json',
        success: function (resultData) {
            pageLoadingStop();
            if (resultData.error) {
                toast('Database backup', resultData.error, 'error');
            } else {
                toast('Database backup', 'Backup written to ' + resultData.path, 'success');
            }
        },
        error: function () {
            pageLoadingStop();
            toast('Database backup', 'Request failed', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function cleanupContainers()
{
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/settings.php',
        data: '&m=previewCleanupContainers',
        dataType: 'json',
        success: function (resultData) {
            pageLoadingStop();
            let containers = resultData.containers;

            if (containers.length === 0) {
                toast('Containers clean up', 'No dead containers found', 'info');
                return;
            }
            let containerList = containers.map(hash => `<li>${hash}</li>`).join('');

            dialogOpen({
                id: 'cleanupContainersModal',
                title: 'Confirm Cleanup',
                body: `<p>The following containers will be removed from the database:</p><ul>${containerList}</ul>`,
                footer: '<button type="button" class="btn btn-outline-danger" id="cleanupContainersConfirm">Remove</button><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>',
                onOpen: function() {
                    $('#cleanupContainersConfirm').on('click', function() {
                        dialogClose('cleanupContainersModal');
                        pageLoadingStart();

                        $.ajax({
                            type: 'POST',
                            url: 'ajax/settings.php',
                            data: '&m=cleanupContainers',
                            dataType: 'json',
                            success: function (resultData) {
                                pageLoadingStop();
                                toast('Containers clean up', `Removed ${resultData.removed} dead container(s) from the database`, 'success');
                                initPage('settings');
                            }
                        });
                    });
                }
            });
        }
    });
}
// ---------------------------------------------------------------------------------------------
