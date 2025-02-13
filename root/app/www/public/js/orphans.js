function removeOrphans()
{
    if ($('#massOrphanTrigger').val() == '0') {
        return;
    }

    $('.orphan-checkall').prop('checked', false);

    pageLoadingStart();

    let action = '';
    if ($('#massOrphanTrigger').val() == '1') {
        action = 'remove';
    } else if ($('#massOrphanTrigger').val() == '2') {
        action = 'prune';
    }

    let selectedOrphans = [];
    $.each($('.orphan'), function () {
        if ($(this).prop('checked')) {
            const orphan    = $(this).attr('id');
            const split     = orphan.split('-');
            let type        = '';

            if (split[0] == 'orphanImage') {
                type = 'image';
            } else if (split[0] == 'orphanVolume') {
                type = 'volume';
            } else if (split[0] == 'orphanNetwork') {
                type = 'network';
            } else if (split[0] == 'unusedContainer') {
                type = 'unused';
            }

            selectedOrphans.push({'orphan': split[1], 'type': type});
        }
    });

    let o = 0;
    function runOrphanTrigger(action)
    {
        if (o == selectedOrphans.length) {
            $('#massOrphanTrigger').val('0');
            $('.orphans').prop('checked', false);
            pageLoadingStop();
            return;
        }

        const orphan = selectedOrphans[o]['orphan'];
        const type = selectedOrphans[o]['type'];

        $.ajax({
            type: 'POST',
            url: '../ajax/orphans.php',
            data: '&m=removeOrphans&orphan=' + orphan + '&action=' + action + '&type=' + type,
            timeout: 600000,
            success: function (resultData) {
                if (resultData) {
                    toast('Orphans', 'Failed to ' + action + ' ' + type + ' orphan ' + orphan + '. ' + resultData, 'error');
                } else {
                    toast('Orphans', 'Orphan ' + type + ' ' + action + ' completed', 'success');
                    $('#' + type + '-' + orphan).remove();
                }

                o++;

                runOrphanTrigger(action);
            },
            error: function(jqhdr, textStatus, errorThrown) {
                toast('Orphans', 'Failed to ' + action + ' ' + type + ' orphan ' + orphan + '. ' + resultData, 'error');
                o++;

                runOrphanTrigger(action);
            }
        });
    }

    runOrphanTrigger(action);
}
// ---------------------------------------------------------------------------------------------
