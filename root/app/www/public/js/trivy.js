let selectedContainer = null;
// ---------------------------------------------------------------------------------------------
function initTrivyDropdown() {
    const $selector = $('#container-selector');
    if ($selector.length === 0) return;

    const $searchInput = $('#container-search');
    if ($selector.find('.search-filter').length > 0) return;

    $(document).on('click', function(e) {
        if ($selector.hasClass('visible') && !$selector[0].contains(e.target)) {
            $selector.removeClass('visible');
        }
    });

    $selector.on('click', '.option-item', function() {
        selectContainer($(this));
    });

    $selector.on('click', '.search-wrapper', function(e) {
        e.stopPropagation();
        $selector.addClass('visible');
    });

    const $hiddenSearch = $('<input class="search-input search-filter" type="text" placeholder="Search containers..." aria-label="Search containers" />');
    $searchInput.after($hiddenSearch);
    $hiddenSearch.on('input', function() {
        filterContainers($(this).val());
    });
    $hiddenSearch.on('focus', function() {
        $selector.addClass('visible');
    });
}
// ---------------------------------------------------------------------------------------------
function selectContainer($option) {
    const $selector = $('#container-selector');
    const $options = $selector.find('.option-item');

    $options.removeClass('selected').attr('aria-selected', 'false');
    $option.addClass('selected').attr('aria-selected', 'true');

    const containerId = $option.data('id');
    const containerName = $option.data('name');
    const containerImage = $option.data('image');

    $('#container-search').val(containerName);
    $selector.removeClass('visible');

    pageLoadingStart();

    $.ajax({
        type: 'POST',
        url: 'ajax/trivy.php',
        dataType: 'json',
        data: { m: 'getVulns', image: containerImage },
        success: function(vulns) {
            pageLoadingStop();

            if (!Array.isArray(vulns)) {
                vulns = [];
            }

            selectedContainer = {
                id: containerId,
                name: containerName,
                image: containerImage,
                vulns: vulns
            };

            loadTrivyVulnerabilities(selectedContainer);
        },
        error: function() {
            pageLoadingStop();
            const $tbody = $('#trivy-tbody');
            $tbody.html('<tr><td colspan="7" class="text-center py-4 text-danger">Failed to load vulnerabilities</td></tr>');
        }
    });
}
// ---------------------------------------------------------------------------------------------
function filterContainers(query) {
    const $options = $('.option-item');
    const lower = query.toLowerCase();

    $options.each(function() {
        const $opt = $(this);
        const name = $opt.data('name') || '';
        const image = $opt.data('image') || '';

        if (name.toLowerCase().includes(lower) || image.toLowerCase().includes(lower)) {
            $opt.show();
        } else {
            $opt.hide();
        }
    });
}
// ---------------------------------------------------------------------------------------------
function loadTrivyVulnerabilities(container) {
    const $tbody = $('#trivy-tbody');
    $tbody.empty();

    const vulns = container.vulns;

    if (!vulns || vulns.length === 0) {
        $tbody.html('<tr><td colspan="7" class="text-center py-4 text-muted bg-secondary">No vulnerabilities found</td></tr>');
        return;
    }

    const sortedVulns = [...vulns].sort((a, b) => {
        const dateA = a.published ? new Date(a.published) : new Date(0);
        const dateB = b.published ? new Date(b.published) : new Date(0);
        return dateB - dateA;
    });

    let currentPkg = null;
    sortedVulns.forEach(function(vuln, index) {
        const row = $('<tr>');
        const nextVuln = sortedVulns[index + 1];
        const displayPkg = vuln.pkg || vuln.title || 'Unknown';
        const nextDisplayPkg = nextVuln ? (nextVuln.pkg || nextVuln.title || 'Unknown') : null;

        if (displayPkg !== currentPkg) {
            row.append($('<td class="bg-secondary">').text(displayPkg));
            currentPkg = displayPkg;
        } else {
            row.append($('<td class="bg-secondary">'));
        }

        if (nextDisplayPkg === displayPkg) {
            row.addClass('group-start');
        }

        row.append($('<td class="bg-secondary">').append($('<a>')
            .attr('href', 'https://nvd.nist.gov/vuln/detail/' + vuln.id)
            .attr('target', '_blank')
            .text(vuln.id)));
        row.append($('<td class="bg-secondary">').append($('<span>')
            .addClass('severity-badge ' + getSeverityClass(vuln.severity))
            .text(vuln.severity)));
        row.append($('<td class="bg-secondary">').text(vuln.status));
        row.append($('<td class="bg-secondary">').text(vuln.installed));
        row.append($('<td class="bg-secondary">').text(vuln.fixed));
        row.append($('<td class="bg-secondary">').text(vuln.title || vuln.pkg));
        $tbody.append(row);
    });
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
$(document).ready(function() {
    initTrivyDropdown();
});
// ---------------------------------------------------------------------------------------------
$(document).ajaxComplete(function() {
    initTrivyDropdown();
});
