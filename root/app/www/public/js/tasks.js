function runTask(task)
{
    loadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/tasks.php',
        data: '&m=runTask&task=' + task,
        success: function (resultData) {
            $('#taskViewer').html(resultData);
            loadingStop();
        }
    });

}
// ---------------------------------------------------------------------------------------------
