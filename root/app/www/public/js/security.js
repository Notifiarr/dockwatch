let securityTableDrawn = false;
let securitySortBy = 'date';
const severityOrder = {
    'CRITICAL': 0,
    'HIGH': 1,
    'MEDIUM': 2,
    'LOW': 3,
    'UNKNOWN': 4
};
// ---------------------------------------------------------------------------------------------
function initSecurityTable() {
    const table = $('#security-table');
    if (!table.length) {
        securityTableDrawn = false;
        return;
    }

    if ($.fn.DataTable.isDataTable('#security-table')) {
        return;
    }

    securityTableDrawn = false;

    $('#security-table').dataTable({
        dom: 'lfBrtip',
        stateSave: false,
        paging: false,
        ordering: true,
        order: [[2, 'asc']],
        columnDefs: [
            { targets: [0, 1, 3, 8, 9], orderable: false }
        ],
        buttons: [
            'colvis'
        ],
        initComplete: function () {
            $('#security-table_filter label').addClass('text-secondary');
            $('#security-table_filter input').attr('placeholder', 'Search').removeClass('form-control').addClass('text-muted form-control-sm');

            $('.buttons-colvis').on('click', function () {
                $('.dt-button-collection').addClass('bg-secondary');
            });

            $('.dt-buttons').prepend($('#check-all-security-btn')).append($('#security-scan-btn'));

            $('.dataTables_filter').addClass('dt-buttons');

            $('.sorting_disabled').removeClass('sorting_asc');
        }
    });

    securityTableDrawn = true;
    setScreenSizeVars();
}
// ---------------------------------------------------------------------------------------------
function toggleSecurityCheckAll() {
    const checkboxes = $('.security-check');
    const allChecked = checkboxes.length > 0 && $('.security-check:checked').length === checkboxes.length;
    $('#security-toggle-all').prop('checked', allChecked);
}
// ---------------------------------------------------------------------------------------------
function toggleAllSecurity() {
    const isChecked = $('#security-toggle-all').prop('checked');
    $('.security-check').prop('checked', isChecked);
}
// ---------------------------------------------------------------------------------------------
function toggleSecurityScans(hash) {
    const row = $('#security-row-' + hash);
    const icon = row.find('.security-expand');
    const imageName = row.data('image');
    const containerName = row.data('name');
    const existingRows = $('.security-expanded-row[data-parent="' + hash + '"]');

    if (existingRows.length > 0) {
        const firstRow = existingRows.first();
        if (firstRow.hasClass('d-none')) {
            existingRows.removeClass('d-none');
            icon.removeClass('fa-plus-square text-info').addClass('fa-minus-square text-muted');
        } else {
            existingRows.addClass('d-none');
            icon.removeClass('fa-minus-square text-muted').addClass('fa-plus-square text-info');
        }
        return;
    }

    $.post('ajax/security.php', { m: 'getRecentScans', image: imageName, limit: 3 }, function(scans) {
        if (!scans || scans.length === 0) {
            return;
        }

        scans.forEach(function(scan) {
            const tr = $('<tr>', {
                class: 'security-expanded-row',
                'data-parent': hash
            });
            tr.html(
                '<td class="bg-primary"></td>' +
                '<td class="bg-primary"></td>' +
                '<td class="bg-primary small-text">' + containerName + '<br>Previous scan</td>' +
                '<td class="bg-primary"></td>' +
                '<td class="bg-primary text-center">' + (scan.counts.critical > 0 ? '<span class="badge bg-danger">' + scan.counts.critical + '</span>' : '<span class="text-muted">0</span>') + '</td>' +
                '<td class="bg-primary text-center">' + (scan.counts.high > 0 ? '<span class="badge bg-warning text-dark">' + scan.counts.high + '</span>' : '<span class="text-muted">0</span>') + '</td>' +
                '<td class="bg-primary text-center">' + (scan.counts.medium > 0 ? '<span class="badge bg-info text-dark">' + scan.counts.medium + '</span>' : '<span class="text-muted">0</span>') + '</td>' +
                '<td class="bg-primary text-center">' + (scan.counts.low > 0 ? '<span class="badge bg-secondary">' + scan.counts.low + '</span>' : '<span class="text-muted">0</span>') + '</td>' +
                '<td class="bg-primary"><span class="small-text text-muted">' + scan.date + '</span></td>' +
                '<td class="bg-primary"><button type="button" class="btn btn-outline-light bg-secondary btn-sm" title="View Scan" onclick="viewSecurityScanByFile(\'' + hash + '\', \'' + imageName.replace(/'/g, '\\\'') + '\', \'' + scan.file + '\')"><i class="fas fa-eye fa-xs"></i></button></td>'
            );
            row.after(tr);
        });

        icon.removeClass('fa-plus-square text-info').addClass('fa-minus-square text-muted');
    }, 'json');
}
// ---------------------------------------------------------------------------------------------
function getSelectedSecurityContainers() {
    const selected = [];
    $('.security-check:checked').each(function() {
        const row = $(this).closest('tr');
        selected.push({
            hash: row.data('hash'),
            image: row.data('image'),
            name: row.data('name')
        });
    });
    return selected;
}
// ---------------------------------------------------------------------------------------------
function massApplySecurityScan() {
    const selected = getSelectedSecurityContainers();

    if (selected.length === 0) {
        toast('Security Scan', 'Please select at least one container to scan', 'error');
        return;
    }

    $('#securityScanModal').modal({
        keyboard: false,
        backdrop: 'static'
    });

    $('#securityScanModal').hide();
    $('#securityScanModal').modal('show');

    $('#securityScan-header').text('Scanning ' + selected.length + ' container(s)');

    $('#securityScan-spinner').show();
    $('#securityScan-close-btn').hide();
    $('#securityScan-results').html('');

    let c = 0;

    function runScan()
    {
        if (c == selected.length) {
            $('#securityScan-spinner').hide();
            $('#securityScan-close-btn').show();
            initPage('security');
            return;
        }

        const container = selected[c];

        $.ajax({
            type: 'POST',
            url: 'ajax/security.php',
            dataType: 'json',
            timeout: 600000,
            data: { m: 'runScan', image: container.image, name: container.name },
            success: function(response) {
                let result = (c + 1) + '/' + selected.length + ': ' + container.name + ': ';
                if (response.success) {
                    result += 'Critical: ' + response.counts.critical + ', High: ' + response.counts.high + ', Medium: ' + response.counts.medium + ', Low: ' + response.counts.low;
                } else {
                    result += 'Scan failed';
                }

                $('#securityScan-results').prepend(result + '<br>');

                c++;
                runScan();
            },
            error: function(jqhdr, textStatus, errorThrown) {
                $('#securityScan-results').prepend((c + 1) + '/' + selected.length + ': ' + container.name + ': ajax error (' + (errorThrown ? errorThrown : 'timeout') + ')<br>');
                c++;
                runScan();
            }
        });
    }

    runScan();
}
// ---------------------------------------------------------------------------------------------
function viewSecurityScan(hash, image, type) {
    viewSecurityScanByFile(hash, image, null);
}

function viewSecurityScanByFile(hash, image, file) {
    pageLoadingStart();

    const data = { m: 'getVulns', image: image };
    if (file) {
        data.file = file;
    }

    $.ajax({
        type: 'POST',
        url: 'ajax/security.php',
        dataType: 'json',
        data: data,
        success: function(vulns) {
            pageLoadingStop();

            if (!Array.isArray(vulns)) {
                vulns = [];
            }

            const containerName = $('#security-row-' + hash).data('name');
            $('#securityModalTitle').text('Security Scan - ' + containerName);

            let html = '';
            if (vulns.length === 0) {
                html = '<div class="text-center py-4 text-muted">No vulnerabilities found</div>';
            } else {
                html = `
                    <div class="security-content">
                        <div class="table-responsive">
                            <table class="table table-no-squish" id="security-vulns-table">
                                <thead>
                                    <tr>
                                        <th class="rounded-top-left-1 bg-primary ps-3">Library</th>
                                        <th class="bg-primary ps-3">Vulnerability</th>
                                        <th class="bg-primary ps-3" style="cursor: pointer; white-space: nowrap;" onclick="toggleSecuritySortModal()">Severity <i class="fas fa-sort security-sort-icon"></i></th>
                                        <th class="bg-primary ps-3">Status</th>
                                        <th class="bg-primary ps-3">Installed</th>
                                        <th class="bg-primary ps-3">Fixed</th>
                                        <th class="rounded-top-right-1 bg-primary ps-3" style="width: 25%;">Title</th>
                                    </tr>
                                </thead>
                                <tbody class="container-table-row">
                `;

                const sortedVulns = [...vulns].sort((a, b) => {
                    if (securitySortBy === 'severity') {
                        const severityA = severityOrder[a.severity?.toUpperCase()] ?? 4;
                        const severityB = severityOrder[b.severity?.toUpperCase()] ?? 4;
                        if (severityA !== severityB) {
                            return severityA - severityB;
                        }
                        const dateA = a.published ? new Date(a.published) : new Date(0);
                        const dateB = b.published ? new Date(b.published) : new Date(0);
                        return dateB - dateA;
                    }
                    const dateA = a.published ? new Date(a.published) : new Date(0);
                    const dateB = b.published ? new Date(b.published) : new Date(0);
                    return dateB - dateA;
                });

                updateSecuritySortIcon();

                const pkgGroups = {};
                sortedVulns.forEach(function(vuln) {
                    const displayPkg = vuln.pkg || vuln.title || 'Unknown';
                    if (!pkgGroups[displayPkg]) {
                        pkgGroups[displayPkg] = [];
                    }
                    pkgGroups[displayPkg].push(vuln);
                });

                Object.keys(pkgGroups).forEach(function(pkgName) {
                    const vulnsInGroup = pkgGroups[pkgName];
                    const rowspan = vulnsInGroup.length;

                    vulnsInGroup.forEach(function(vuln, idx) {
                        html += '<tr>';

                        if (idx === 0) {
                            html += '<td class="bg-secondary" rowspan="' + rowspan + '">' + pkgName.slice(0, 32) + "..." + '</td>';
                        }

                        html += `
                            <td class="bg-secondary"><a href="${vuln.source}" target="_blank">${vuln.id}</a></td>
                            <td class="bg-secondary"><span class="badge ${getSeverityBadgeClass(vuln.severity)}">${vuln.severity.toUpperCase() || 'UNKNOWN'}</span></td>
                            <td class="bg-secondary">${vuln.status || '-'}</td>
                            <td class="bg-secondary">${vuln.installed || '-'}</td>
                            <td class="bg-secondary">${vuln.fixed || '-'}</td>
                            <td class="bg-secondary">${vuln.title || vuln.pkg || '-'}</td>
                        </tr>`;
                    });
                });

                html += '</tbody></table></div></div>';
            }

            $('#securityModalBody').html(html);
            $('#securityModal').data('currentHash', hash);
            $('#securityModal').data('currentImage', image);
            $('#securityModal').data('currentFile', file);
            $('#securityModal').modal('show');
        },
        error: function() {
            pageLoadingStop();
            toast('Security', 'Failed to load vulnerabilities', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function viewSecurityScanHistory(hash, image) {
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/security.php',
        dataType: 'json',
        data: { m: 'getScanHistory', image: image },
        success: function(history) {
            pageLoadingStop();

            const containerName = $('#security-row-' + hash).data('name');
            $('#securityModalTitle').text('Security Scan History - ' + containerName);

            let html = '';
            if (!history || history.length === 0) {
                html = '<div class="text-center py-4 text-muted">No scan history available</div>';
            } else {
                html = `
                    <div class="table-responsive">
                        <table class="table table-no-squish">
                            <thead>
                                <tr>
                                    <th class="rounded-top-left-1 bg-primary ps-3">Date</th>
                                    <th class="bg-primary ps-3 text-center">Critical</th>
                                    <th class="bg-primary ps-3 text-center">High</th>
                                    <th class="bg-primary ps-3 text-center">Medium</th>
                                    <th class="bg-primary ps-3 text-center">Low</th>
                                    <th class="rounded-top-right-1 bg-primary ps-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                history.forEach(function(scan) {
                    html += `
                        <tr>
                            <td class="bg-secondary">${scan.date}</td>
                            <td class="bg-secondary text-center">${scan.counts.critical > 0 ? '<span class="badge bg-danger">' + scan.counts.critical + '</span>' : '0'}</td>
                            <td class="bg-secondary text-center">${scan.counts.high > 0 ? '<span class="badge bg-warning text-dark">' + scan.counts.high + '</span>' : '0'}</td>
                            <td class="bg-secondary text-center">${scan.counts.medium > 0 ? '<span class="badge bg-info text-dark">' + scan.counts.medium + '</span>' : '0'}</td>
                            <td class="bg-secondary text-center">${scan.counts.low > 0 ? '<span class="badge bg-secondary">' + scan.counts.low + '</span>' : '0'}</td>
                            <td class="bg-secondary">
                                <button class="btn btn-outline-light btn-sm" onclick="viewSecurityScanByFile('${hash}', '${image}', '${scan.file}')">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
            }

            $('#securityModalBody').html(html);
            $('#securityModal').modal('show');
        },
        error: function() {
            pageLoadingStop();
            toast('Security', 'Failed to load scan history', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function runSecurityScan(hash, image, name) {
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/security.php',
        dataType: 'json',
        data: { m: 'runScan', image: image, name: name },
        success: function(response) {
            pageLoadingStop();

            if (response.success) {
                toast('Security Scan Complete', name + ': Critical: ' + response.counts.critical + ', High: ' + response.counts.high + ', Medium: ' + response.counts.medium + ', Low: ' + response.counts.low, 'success');
                initPage('security');
            } else {
                toast('Security Scan Failed', response.result || 'Unknown error', 'error');
            }
        },
        error: function() {
            pageLoadingStop();
            toast('Security Scan', 'Failed to run scan', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function toggleSecuritySortModal() {
    securitySortBy = securitySortBy === 'date' ? 'severity' : 'date';
    const hash = $('#securityModal').data('currentHash');
    const image = $('#securityModal').data('currentImage');
    const file = $('#securityModal').data('currentFile');
    if (hash && image) {
        viewSecurityScanByFile(hash, image, file);
    }
}
// ---------------------------------------------------------------------------------------------
function updateSecuritySortIcon() {
    const icon = $('.security-sort-icon');
    if (!icon.length) return;

    if (securitySortBy === 'severity') {
        icon.removeClass('fa-sort').addClass('fa-sort-down');
    } else {
        icon.removeClass('fa-sort-down').addClass('fa-sort');
    }
}
// ---------------------------------------------------------------------------------------------
function getSeverityClass(severity) {
    if (!severity) return 'severity-none';
    switch (severity.toUpperCase()) {
        case 'CRITICAL':
            return 'severity-critical';
        case 'HIGH':
            return 'severity-high';
        case 'MEDIUM':
            return 'severity-medium';
        case 'LOW':
            return 'severity-low';
        default:
            return 'severity-none';
    }
}
// ---------------------------------------------------------------------------------------------
function getSeverityBadgeClass(severity) {
    if (!severity) return 'bg-secondary';
    switch (severity.toUpperCase()) {
        case 'CRITICAL':
            return 'bg-danger';
        case 'HIGH':
            return 'bg-warning text-dark';
        case 'MEDIUM':
            return 'bg-info text-dark';
        case 'LOW':
            return 'bg-secondary';
        default:
            return 'bg-secondary';
    }
}
// ---------------------------------------------------------------------------------------------
