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
        success: function (resultData) {
            loadingStop();
            toast('Notifications', 'Notification settings saved', 'success');
        }
    });

}
// ---------------------------------------------------------------------------------------------
