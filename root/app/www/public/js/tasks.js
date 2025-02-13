function runTask(task)
{
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/tasks.php',
        data: '&m=runTask&task=' + task,
        dataType: 'json',
        success: function (resultData) {
            if (resultData.error) {
                toast('Tasks', resultData.error, 'error');
            } else {
                toast('Tasks', 'Task request sent to server ' + resultData.server, 'success');
                $('#taskViewer').html(resultData.result);
            }
            pageLoadingStop();
        }
    });

}
// ---------------------------------------------------------------------------------------------
function updateTaskDisabled(task, state)
{
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/tasks.php',
        data: '&m=updateTaskDisabled&task=' + task + '&disabled=' + state,
        dataType: 'json',
        success: function (resultData) {
            if (resultData.error) {
                toast('Tasks', resultData.error, 'error');
            } else {
                toast('Tasks', 'Task disabled state updated on server ' + resultData.server, 'success');
            }
            pageLoadingStop();
        }
    });
}
// ---------------------------------------------------------------------------------------------