function removeOrphans()
{
    if ($('#massOrphanTrigger').val() == '0') {
        return;
    }

    loadingStart();

    let action = '';
    if ($('#massOrphanTrigger').val() == '1') {
        action = 'remove';
    } else if ($('#massOrphanTrigger').val() == '2') {
        action = 'prune';
    }

    let params = '';
    $.each($('.orphan'), function () {
        if ($(this).prop('checked')) {
            const orphan    = $(this).attr('id');
            const split     = orphan.split('-');
            let type        = '';

            if (split[0] == 'orphanImage') {
                type = 'image';
            } else if (split[0] == 'orphanVolume') {
                type = 'volume';
            }

            $.ajax({
                type: 'POST',
                url: '../ajax/orphans.php',
                data: '&m=removeOrphans&orphan=' + split[1] + '&action=' + action + '&type=' + type,
                async: 'global',
                success: function (resultData) {
                    if (resultData) {
                        toast('Orphans', 'Failed to ' + action + ' ' + type + ' orphan ' + split[1] + '. ' + resultData, 'error');
                        return;
                    }

                    toast('Orphans', 'Orphan ' + type + ' ' + action + ' completed', 'success');
                    $('#' + type + '-' + split[1]).remove();
                }
            });
        }
    });

    $('#massOrphanTrigger').val('0');
    $('.orphans').prop('checked', false);
    loadingStop();
}
// ---------------------------------------------------------------------------------------------
