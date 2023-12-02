function runCommand()
{
    let servers = '';
    $.each($('[id^=command-]'), function() {
        if ($(this).prop('checked')) {
            const serverIndex = $(this).prop('id').replace('command-', '');
            servers += (servers ? ',' : '') + serverIndex;
        }
    });

    if (!servers || !$('#command').val()) {
        toast('Commands', 'The command and at least one server selection is required', 'error');
        return;
    }

    loadingStart();

    $.ajax({
        type: 'POST',
        url: '../ajax/commands.php',
        data: '&m=runCommand&command=' + $('#command').val() + '&container=' + $('#command-container').val() + '&parameters=' + $('#command-parameters').val() + '&servers=' + servers,
        success: function (resultData) {
            $('#commandResults').html(resultData);
            loadingStop();
        }
    });

}
// ---------------------------------------------------------------------------------------------