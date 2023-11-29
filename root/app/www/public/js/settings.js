function saveGlobalSettings()
{
    loadingStart();

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
        url: '../ajax/settings.php',
        data: '&m=saveGlobalSettings' + params,
        dataType: 'json',
        success: function (resultData) {
            if (resultData.error) {
                toast('Settings', resultData.error, 'error');
            } else  {
                toast('Settings', 'Global settings saved on server ' + resultData.server, 'success');
                initPage('settings');
                $('#activeServer').html(resultData.serverList);
            }

            loadingStop();
        }
    });

}
// ---------------------------------------------------------------------------------------------
