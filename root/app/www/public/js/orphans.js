function removeOrphans()
{
    if ($('#massOrphanTrigger').val() == '0') {
        return;
    }

    loadingStart();

    let params = '';
    $.each($('[id^=orphan-]'), function () {
        if ($(this).prop('checked')) {
            const orphan = $(this).attr('id').replace('orphan-', '');

            $.ajax({
                type: 'POST',
                url: '../ajax/orphans.php',
                data: '&m=removeOrphans&orphan=' + orphan + '&trigger=' + $('#massOrphanTrigger').val(),
                async: 'global',
                success: function (resultData) {
                    $('#massOrphanTrigger').val('0');

                    if (resultData) {
                        toast('Orphans', 'Orphan ' + orphan + ' failed to remove. ' + resultData, 'error');
                        return;
                    }

                    toast('Orphans', 'Orphan ' + orphan + ' has been removed', 'success');
                    $('#' + orphan).remove();
                }
            });
        }
    });

    $('.orphans-check').prop('checked', false);
    loadingStop();
}
// ---------------------------------------------------------------------------------------------
