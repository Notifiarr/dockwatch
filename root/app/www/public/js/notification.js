function saveNotificationSettings()
{
    loadingStart();

    let params = '';
    $.each($('[id^=notifications-]'), function () {
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
        url: '../ajax/notification.php',
        data: '&m=saveNotificationSettings' + params,
        success: function (resultData) {
            loadingStop();
            toast('Notifications', 'Notification settings saved', 'success');
        }
    });

}
// ---------------------------------------------------------------------------------------------
