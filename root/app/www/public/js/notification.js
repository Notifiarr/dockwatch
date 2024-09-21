function saveNotification(platformId, linkId)
{
    let requiredError = false;
    let params = '';
    $.each($('[id^=notificationTrigger-]'), function () {
        let val = '';
        if ($(this).is(':checkbox') || $(this).is(':radio')) {
            val = $(this).prop('checked') ? 1 : 0;
        } else {
            val = $(this).val();
        }

        params += '&' + $(this).attr('id') + '=' + val;
    });

    $.each($('[id^=notificationPlatformParameter-]'), function () {
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
        data: '&m=saveNotification&platformId=' + platformId + '&linkId=' + linkId + params,
        dataType: 'json',
        success: function (resultData) {
            loadingStop();
            if (resultData.error) {
                toast('Notifications', resultData.error, 'error');
                return;
            }

            dialogClose('openNotificationTriggers');
            toast('Notifications', 'Notification changes have been saved', 'success');
            initPage('notification');
        }
    });

}
// ---------------------------------------------------------------------------------------------
function addNotification(platformId)
{
    let requiredError = false;
    let params = '';
    $.each($('[id^=notificationTrigger-]'), function () {
        let val = '';
        if ($(this).is(':checkbox') || $(this).is(':radio')) {
            val = $(this).prop('checked') ? 1 : 0;
        } else {
            val = $(this).val();
        }

        params += '&' + $(this).attr('id') + '=' + val;
    });

    $.each($('[id^=notificationPlatformParameter-]'), function () {
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
        data: '&m=addNotification&platformId=' + platformId + params,
        dataType: 'json',
        success: function (resultData) {
            loadingStop();
            if (resultData.error) {
                toast('Notifications', resultData.error, 'error');
                return;
            }

            dialogClose('openNotificationTriggers');
            toast('Notifications', 'Notification has been added', 'success');
            initPage('notification');
        }
    });

}
// ---------------------------------------------------------------------------------------------
function deleteNotification(linkId)
{
    if (confirm('Are you sure you want to delete this notification?')) {
        loadingStart();
        $.ajax({
            type: 'POST',
            url: '../ajax/notification.php',
            data: '&m=deleteNotification&linkId=' + linkId,
            success: function (resultData) {
                loadingStop();

                dialogClose('openNotificationTriggers');
                toast('Notifications', 'Notification changes have been saved', 'success');
                initPage('notification');
            }
        });
    }
}
// ---------------------------------------------------------------------------------------------
function openNotificationTriggers(platformId, linkId = 0)
{
    $.ajax({
        type: 'POST',
        url: '../ajax/notification.php',
        data: '&m=openNotificationTriggers&platformId=' + platformId + '&linkId=' + linkId,
        success: function (resultData) {
            dialogOpen({
                id: 'openNotificationTriggers',
                title: 'Notification triggers - ' + (linkId ? 'Edit' : 'Add'),
                size: 'lg',
                body: resultData
            });           
        }
    });
}
// ---------------------------------------------------------------------------------------------
function testNotify(linkId, name)
{
    loadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/notification.php',
        data: '&m=testNotify&linkId=' + linkId + '&name=' + name,
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
