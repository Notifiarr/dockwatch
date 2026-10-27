var browseDatabaseTable = '';
var browseDatabasePage = 1;
var browseDatabasePages = 1;

function backupDatabase()
{
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/database.php',
        data: '&m=backup',
        dataType: 'json',
        success: function (resultData) {
            pageLoadingStop();
            if (resultData.error) {
                toast('Database backup', resultData.error, 'error');
            } else {
                toast('Database backup', 'Backup written to ' + resultData.path, 'success');
                loadBackupList();
            }
        },
        error: function () {
            pageLoadingStop();
            toast('Database backup', 'Request failed', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function loadBackupList()
{
    $.ajax({
        type: 'POST',
        url: 'ajax/database.php',
        data: '&m=listBackups',
        dataType: 'json',
        success: function (resultData) {
            if (resultData && resultData.html) {
                $('#backupList').html(resultData.html);
            }
        }
    });
}
// ---------------------------------------------------------------------------------------------
function downloadBackup(folder, run)
{
    window.location = 'ajax/database.php?m=downloadBackup&folder=' + encodeURIComponent(folder) + '&run=' + encodeURIComponent(run);
}
// ---------------------------------------------------------------------------------------------
function deleteBackup(folder, run)
{
    if (!confirm('Delete this backup?')) {
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'ajax/database.php',
        data: '&m=deleteBackup&folder=' + encodeURIComponent(folder) + '&run=' + encodeURIComponent(run),
        dataType: 'json',
        success: function (resultData) {
            if (resultData.error) {
                toast('Database backup', resultData.error, 'error');
                return;
            }
            toast('Database backup', 'Backup deleted', 'success');
            loadBackupList();
        },
        error: function () {
            toast('Database backup', 'Request failed', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function restoreBackup(folder, run)
{
    if (!confirm('Restore this backup? The database will be replaced with this dump. Config files and compose stacks in the folder are not restored automatically.')) {
        return;
    }

    pageLoadingStart();
    $.ajax({
        type: 'POST',
        url: 'ajax/database.php',
        data: '&m=restoreBackup&folder=' + encodeURIComponent(folder) + '&run=' + encodeURIComponent(run),
        dataType: 'json',
        success: function (resultData) {
            pageLoadingStop();
            if (resultData.error) {
                toast('Database backup', resultData.error, 'error');
                return;
            }
            toast('Database backup', 'Database restored', 'success');
            initPage('settings');
        },
        error: function () {
            pageLoadingStop();
            toast('Database backup', 'Request failed', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function viewDatabaseBrowse(table, page)
{
    table = table || browseDatabaseTable || '';
    if (!table) {
        return;
    }

    page = parseInt(page, 10) || 1;
    if (page < 1) {
        page = 1;
    }

    browseDatabaseTable = table;
    pageLoadingStart();
    $.ajax({
        type: 'POST',
        url: 'ajax/database.php',
        data: '&m=browseDatabase&view=table&table=' + encodeURIComponent(table) + '&page=' + encodeURIComponent(page),
        dataType: 'json',
        success: function (response) {
            pageLoadingStop();

            if (!response || response.error) {
                toast('Browse', (response && response.error) || 'Could not browse that table', 'error');
                return;
            }

            browseDatabasePage = parseInt(response.page, 10) || 1;
            browseDatabasePages = parseInt(response.pages, 10) || 1;

            if ($('#browse-database-dialog').length && $('#browse-database-dialog').is(':visible')) {
                $('#browse-database-dialog .modal-title').text(table);
                $('#browse-database-dialog .modal-body').html(response.html || '');
                return;
            }

            dialogOpen({
                id: 'browse-database-dialog',
                title: table,
                body: response.html || '',
                footer: false,
                size: 'xxl',
                onOpen: function () {
                    $('#browse-database-dialog .modal-dialog').removeClass('modal-dialog-scrollable');
                    $('#browse-database-dialog .modal-body').addClass('browse-database-modal-body');
                }
            });
        },
        error: function () {
            pageLoadingStop();
            toast('Browse', 'Could not browse that table', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function viewDatabaseSchema(table)
{
    table = table || '';
    if (!table) {
        return;
    }

    pageLoadingStart();
    $.ajax({
        type: 'POST',
        url: 'ajax/database.php',
        data: '&m=browseDatabase&view=schema&table=' + encodeURIComponent(table),
        dataType: 'json',
        success: function (response) {
            pageLoadingStop();

            if (!response || response.error) {
                toast('Schema', (response && response.error) || 'Could not load the table schema', 'error');
                return;
            }

            dialogOpen({
                id: 'browse-database-schema-dialog',
                title: 'Schema: ' + table,
                body: response.html || '',
                footer: false,
                size: 'lg'
            });
        },
        error: function () {
            pageLoadingStop();
            toast('Schema', 'Could not load the table schema', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function browseDatabasePrev()
{
    if (browseDatabasePage <= 1) {
        return;
    }
    viewDatabaseBrowse(browseDatabaseTable, browseDatabasePage - 1);
}
// ---------------------------------------------------------------------------------------------
function browseDatabaseNext()
{
    if (browseDatabasePage >= browseDatabasePages) {
        return;
    }
    viewDatabaseBrowse(browseDatabaseTable, browseDatabasePage + 1);
}
// ---------------------------------------------------------------------------------------------
function runDatabaseQuery()
{
    let sql = ($('#browseDatabaseQuery').val() || '').trim();
    if (!sql) {
        toast('Query', 'Enter a query first', 'error');
        return;
    }

    pageLoadingStart();
    $.ajax({
        type: 'POST',
        url: 'ajax/database.php',
        data: '&m=browseDatabase&view=query&sql=' + encodeURIComponent(sql),
        dataType: 'json',
        success: function (response) {
            pageLoadingStop();

            if (!response || response.error) {
                toast('Query', (response && response.error) || 'Query failed', 'error');
                return;
            }

            $('#browseDatabaseQuery').val('');
            $('#browseDatabaseQueryResult').html(response.html || '');
        },
        error: function () {
            pageLoadingStop();
            toast('Query', 'Query failed', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
