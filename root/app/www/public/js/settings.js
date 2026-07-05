function countActiveUsers()
{
    let count = 0;

    $('[id^=globalSetting-userList-active-]').not('#globalSetting-userList-active-new').each(function () {
        if ($(this).prop('checked')) {
            count++;
        }
    });

    if ($('#globalSetting-userList-username-new').val() && $('#globalSetting-userList-active-new').prop('checked')) {
        count++;
    }

    return count;
}
// ---------------------------------------------------------------------------------------------
function confirmUserAuthChange(activeUsers)
{
    if (activeUsers > 0) {
        return true;
    }

    if ($('#settings-hasLoginFile').val() == 1) {
        return confirm('No active database users will remain. Login will still be required via /config/logins. Continue?');
    }

    return confirm('This will disable login authentication. Continue?');
}
// ---------------------------------------------------------------------------------------------
function saveGlobalSettings()
{
    if (!confirmUserAuthChange(countActiveUsers())) {
        return;
    }

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
            if (resultData.requireLogin) {
                window.location.href = 'login.php';
                return;
            }

            if (resultData.authDisabled) {
                reload();
                return;
            }

            if (resultData.error) {
                toast('Settings', resultData.error, 'error');
                pageLoadingStop();
                return;
            }

            toast('Settings', 'Global settings saved on server ' + resultData.server, 'success');
            initPage('settings');
            pageLoadingStop();
        }
    });

}
// ---------------------------------------------------------------------------------------------
function removeUser(userId)
{
    let rowActive   = $('#globalSetting-userList-active-' + userId).prop('checked');
    let activeAfter = countActiveUsers() - (rowActive ? 1 : 0);

    if (activeAfter > 0) {
        if (!confirm('Are you sure you want to remove this user from the database?')) {
            return;
        }
    } else if (!confirmUserAuthChange(activeAfter)) {
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'ajax/settings.php',
        data: '&m=removeUser&id=' + userId,
        dataType: 'json',
        success: function (resultData) {
            if (resultData.requireLogin) {
                window.location.href = 'login.php';
                return;
            }

            if (resultData.authDisabled) {
                reload();
                return;
            }

            if (resultData.error) {
                toast('Users', resultData.error, 'error');
                return;
            }

            $('#userRow-' + userId).remove();
            toast('Users', 'The user has been removed', 'success');
            initPage('settings');
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
