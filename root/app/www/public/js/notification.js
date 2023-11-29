function saveNotificationSettings()
{
    let requiredError = false;
    let params = '';
    $.each($('[id^=notifications-]'), function () {
        const required = $(this).attr('data-required');
        let val = '';
        if ($(this).is(':checkbox') || $(this).is(':radio')) {
            val = $(this).prop('checked') ? 1 : 0;
        } else {
            val = $(this).val();
        }

        if (required && val == '') {
            requiredError = true;
        }

        params += '&' + $(this).attr('id') + '=' + val;
    });

    if (requiredError) {
        toast('Notifications', 'Required fields can not be empty', 'error');
        return;
    }

    loadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/notification.php',
        data: '&m=saveNotificationSettings' + params,
        dataType: 'json',
        success: function (resultData) {
            if (resultData.error) {
                toast('Notifications', resultData.error, 'error');
            } else {
                toast('Notifications', 'Notification settings saved on server ' + resultData.server, 'success');
            }
            loadingStop();
        }
    });

}
// ---------------------------------------------------------------------------------------------
function testNotify(platform)
{
    loadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/notification.php',
        data: '&m=testNotify&platform=' + platform,
        dataType: 'json',
        success: function (resultData) {
            if (resultData.error) {
                toast('Notifications', resultData.error, 'error');
            } else {
                toast('Notifications', resultData.result, 'success');
            }
            loadingStop();
        }
    });
}
// ---------------------------------------------------------------------------------------------
