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
