let trivyTableDrawn = false;
let trivySortBy = 'date';
const severityOrder = {
    'CRITICAL': 0,
    'HIGH': 1,
    'MEDIUM': 2,
    'LOW': 3,
    'UNKNOWN': 4
};
// ---------------------------------------------------------------------------------------------
function initTrivyTable() {
    const table = document.getElementById('trivy-table');
    if (!table) {
        trivyTableDrawn = false;
        return;
    }

    if ($.fn.DataTable.isDataTable('#trivy-table')) {
        return;
    }

    trivyTableDrawn = false;

    $('#trivy-table').dataTable({
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
            $('#trivy-table_filter label').addClass('text-secondary');
            $('#trivy-table_filter input').attr('placeholder', 'Search').removeClass('form-control').addClass('text-muted form-control-sm');

            $('.buttons-colvis').on('click', function () {
                $('.dt-button-collection').addClass('bg-secondary');
            });

            $('.dt-buttons').prepend($('#check-all-trivy-btn')).append($('#trivy-scan-btn'));

            $('.dataTables_filter').addClass('dt-buttons');

            $('.sorting_disabled').removeClass('sorting_asc');
        }
    });

    trivyTableDrawn = true;
    setScreenSizeVars();
}
// ---------------------------------------------------------------------------------------------
function toggleTrivyCheckAll() {
    const checkboxes = document.querySelectorAll('.trivy-check');
    const allChecked = checkboxes.length > 0 && document.querySelectorAll('.trivy-check:checked').length === checkboxes.length;
    document.getElementById('trivy-toggle-all').checked = allChecked;
}
// ---------------------------------------------------------------------------------------------
function toggleAllTrivy() {
    const isChecked = document.getElementById('trivy-toggle-all').checked;
    document.querySelectorAll('.trivy-check').forEach(function(cb) {
        cb.checked = isChecked;
    });
}
// ---------------------------------------------------------------------------------------------
function toggleTrivyScans(hash) {
    const row = document.getElementById('trivy-row-' + hash);
    const icon = row.querySelector('.trivy-expand');
    const imageName = row.dataset.image;
    const containerName = row.dataset.name;
    const existingRows = document.querySelectorAll('.trivy-expanded-row[data-parent="' + hash + '"]');

    if (existingRows.length > 0) {
        const firstRow = existingRows[0];
        if (firstRow.classList.contains('d-none')) {
            existingRows.forEach(function(r) {
                r.classList.remove('d-none');
            });
            icon.classList.remove('fa-plus-square', 'text-info');
            icon.classList.add('fa-minus-square', 'text-muted');
        } else {
            existingRows.forEach(function(r) {
                r.classList.add('d-none');
            });
            icon.classList.remove('fa-minus-square', 'text-muted');
            icon.classList.add('fa-plus-square', 'text-info');
        }
        return;
    }

    $.post('ajax/trivy.php', { m: 'getRecentScans', image: imageName, limit: 3 }, function(scans) {
        if (!scans || scans.length === 0) {
            return;
        }

        scans.forEach(function(scan) {
            const tr = document.createElement('tr');
            tr.className = 'trivy-expanded-row';
            tr.dataset.parent = hash;
            tr.innerHTML =
                '<td class="bg-primary"></td>' +
                '<td class="bg-primary"></td>' +
                '<td class="bg-primary small-text">' + containerName + '<br>Previous scan</td>' +
                '<td class="bg-primary"></td>' +
                '<td class="bg-primary text-center">' + (scan.counts.critical > 0 ? '<span class="badge bg-danger">' + scan.counts.critical + '</span>' : '<span class="text-muted">0</span>') + '</td>' +
                '<td class="bg-primary text-center">' + (scan.counts.high > 0 ? '<span class="badge bg-warning text-dark">' + scan.counts.high + '</span>' : '<span class="text-muted">0</span>') + '</td>' +
                '<td class="bg-primary text-center">' + (scan.counts.medium > 0 ? '<span class="badge bg-info text-dark">' + scan.counts.medium + '</span>' : '<span class="text-muted">0</span>') + '</td>' +
                '<td class="bg-primary text-center">' + (scan.counts.low > 0 ? '<span class="badge bg-secondary">' + scan.counts.low + '</span>' : '<span class="text-muted">0</span>') + '</td>' +
                '<td class="bg-primary"><span class="small-text text-muted">' + scan.date + '</span></td>' +
                '<td class="bg-primary"><button type="button" class="btn btn-outline-light bg-secondary btn-sm" title="View Scan" onclick="viewTrivyScanByFile(\'' + hash + '\', \'' + imageName.replace(/'/g, '\\\'') + '\', \'' + scan.file + '\')"><i class="fas fa-eye fa-xs"></i></button></td>';
            row.parentNode.insertBefore(tr, row.nextSibling);
        });

        icon.classList.remove('fa-plus-square', 'text-info');
        icon.classList.add('fa-minus-square', 'text-muted');
    }, 'json');
}
// ---------------------------------------------------------------------------------------------
function getSelectedTrivyContainers() {
    const selected = [];
    document.querySelectorAll('.trivy-check:checked').forEach(function(cb) {
        const row = cb.closest('tr');
        selected.push({
            hash: row.dataset.hash,
            image: row.dataset.image,
            name: row.dataset.name
        });
    });
    return selected;
}
// ---------------------------------------------------------------------------------------------
function massApplyTrivyScan() {
    const selected = getSelectedTrivyContainers();

    if (selected.length === 0) {
        toast('Trivy Scan', 'Please select at least one container to scan', 'error');
        return;
    }

    $('#trivyScanModal').modal({
        keyboard: false,
        backdrop: 'static'
    });

    $('#trivyScanModal').hide();
    $('#trivyScanModal').modal('show');

    document.getElementById('trivyScan-header').textContent = 'Scanning ' + selected.length + ' container(s)';
    document.getElementById('trivyScan-spinner').style.display = 'block';
    document.getElementById('trivyScan-results').innerHTML = '';
    document.getElementById('trivyScan-close-btn').style.display = 'none';

    let c = 0;

    function runScan()
    {
        if (c == selected.length) {
            document.getElementById('trivyScan-spinner').style.display = 'none';
            document.getElementById('trivyScan-close-btn').style.display = 'inline-block';
            initPage('trivy');
            return;
        }

        const container = selected[c];

        $.ajax({
            type: 'POST',
            url: 'ajax/trivy.php',
            dataType: 'json',
            timeout: 600000,
            data: { m: 'runScan', image: container.image, name: container.name },
            success: function(response) {
                let resultHtml = (c + 1) + '/' + selected.length + ': ' + container.name + ': ';
                if (response.success) {
                    resultHtml += 'Critical: ' + response.counts.critical + ', High: ' + response.counts.high + ', Medium: ' + response.counts.medium + ', Low: ' + response.counts.low;
                } else {
                    resultHtml += 'Scan failed';
                }

                const resultsDiv = document.getElementById('trivyScan-results');
                resultsDiv.insertAdjacentHTML('afterbegin', resultHtml + '<br>');
                c++;
                runScan();
            },
            error: function(jqhdr, textStatus, errorThrown) {
                const resultsDiv = document.getElementById('trivyScan-results');
                resultsDiv.insertAdjacentHTML('afterbegin', (c + 1) + '/' + selected.length + ': ' + container.name + ': ajax error (' + (errorThrown ? errorThrown : 'timeout') + ')<br>');
                c++;
                runScan();
            }
        });
    }

    runScan();
}
// ---------------------------------------------------------------------------------------------
function viewTrivyScan(hash, image, type) {
    viewTrivyScanByFile(hash, image, null);
}

function viewTrivyScanByFile(hash, image, file) {
    pageLoadingStart();

    const data = { m: 'getVulns', image: image };
    if (file) {
        data.file = file;
    }

    $.ajax({
        type: 'POST',
        url: 'ajax/trivy.php',
        dataType: 'json',
        data: data,
        success: function(vulns) {
            pageLoadingStop();

            if (!Array.isArray(vulns)) {
                vulns = [];
            }

            const containerName = $('#trivy-row-' + hash).data('name');
            document.getElementById('trivyModalTitle').textContent = 'Trivy Scan - ' + containerName;

            let html = '';
            if (vulns.length === 0) {
                html = '<div class="text-center py-4 text-muted">No vulnerabilities found</div>';
            } else {
                html = `
                    <div class="trivy-content">
                        <div class="table-responsive">
                            <table class="table" id="trivy-vulns-table">
                                <thead>
                                    <tr>
                                        <th class="rounded-top-left-1 bg-primary ps-3">Library</th>
                                        <th class="bg-primary ps-3">Vulnerability</th>
                                        <th class="bg-primary ps-3" style="cursor: pointer; white-space: nowrap;" onclick="toggleTrivySortModal()">Severity <i class="fas fa-sort trivy-sort-icon"></i></th>
                                        <th class="bg-primary ps-3">Status</th>
                                        <th class="bg-primary ps-3">Installed</th>
                                        <th class="bg-primary ps-3">Fixed</th>
                                        <th class="rounded-top-right-1 bg-primary ps-3" style="width: 25%;">Title</th>
                                    </tr>
                                </thead>
                                <tbody class="container-table-row">
                `;

                const sortedVulns = [...vulns].sort((a, b) => {
                    if (trivySortBy === 'severity') {
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

                updateTrivySortIcon();

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
                            html += '<td class="bg-secondary" rowspan="' + rowspan + '">' + pkgName + '</td>';
                        }

                        html += `
                            <td class="bg-secondary"><a href="https://nvd.nist.gov/vuln/detail/${vuln.id}" target="_blank">${vuln.id}</a></td>
                            <td class="bg-secondary"><span class="badge ${getSeverityBadgeClass(vuln.severity)}">${vuln.severity || 'UNKNOWN'}</span></td>
                            <td class="bg-secondary">${vuln.status || '-'}</td>
                            <td class="bg-secondary">${vuln.installed || '-'}</td>
                            <td class="bg-secondary">${vuln.fixed || '-'}</td>
                            <td class="bg-secondary">${vuln.title || vuln.pkg || '-'}</td>
                        </tr>`;
                    });
                });

                html += '</tbody></table></div></div>';
            }

            $('#trivyModalBody').html(html);
            $('#trivyModal').data('currentHash', hash);
            $('#trivyModal').data('currentImage', image);
            $('#trivyModal').data('currentFile', file);
            $('#trivyModal').modal('show');
        },
        error: function() {
            pageLoadingStop();
            toast('Trivy', 'Failed to load vulnerabilities', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function viewTrivyScanHistory(hash, image) {
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/trivy.php',
        dataType: 'json',
        data: { m: 'getScanHistory', image: image },
        success: function(history) {
            pageLoadingStop();

            const containerName = $('#trivy-row-' + hash).data('name');
            document.getElementById('trivyModalTitle').textContent = 'Trivy Scan History - ' + containerName;

            let html = '';
            if (!history || history.length === 0) {
                html = '<div class="text-center py-4 text-muted">No scan history available</div>';
            } else {
                html = `
                    <div class="table-responsive">
                        <table class="table">
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
                                <button class="btn btn-outline-light btn-sm" onclick="viewTrivyScanByFile('${hash}', '${image}', '${scan.file}')">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
            }

            $('#trivyModalBody').html(html);
            $('#trivyModal').modal('show');
        },
        error: function() {
            pageLoadingStop();
            toast('Trivy', 'Failed to load scan history', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function runTrivyScan(hash, image, name) {
    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/trivy.php',
        dataType: 'json',
        data: { m: 'runScan', image: image, name: name },
        success: function(response) {
            pageLoadingStop();

            if (response.success) {
                toast('Trivy Scan Complete', name + ': Critical: ' + response.counts.critical + ', High: ' + response.counts.high + ', Medium: ' + response.counts.medium + ', Low: ' + response.counts.low, 'success');
                initPage('trivy');
            } else {
                toast('Trivy Scan Failed', response.result || 'Unknown error', 'error');
            }
        },
        error: function() {
            pageLoadingStop();
            toast('Trivy Scan', 'Failed to run scan', 'error');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function toggleTrivySortModal() {
    trivySortBy = trivySortBy === 'date' ? 'severity' : 'date';
    const hash = $('#trivyModal').data('currentHash');
    const image = $('#trivyModal').data('currentImage');
    const file = $('#trivyModal').data('currentFile');
    if (hash && image) {
        viewTrivyScanByFile(hash, image, file);
    }
}
// ---------------------------------------------------------------------------------------------
function updateTrivySortIcon() {
    const icon = document.querySelector('.trivy-sort-icon');
    if (!icon) return;

    if (trivySortBy === 'severity') {
        icon.classList.remove('fa-sort');
        icon.classList.add('fa-sort-down');
    } else {
        icon.classList.remove('fa-sort-down');
        icon.classList.add('fa-sort');
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
$(document).ready(function() {
    initTrivyTable();
});
// ---------------------------------------------------------------------------------------------
$(document).ajaxComplete(function() {
    initTrivyTable();
});
