function drawOverview()
{
    $('#chart-cpu-container').html('<canvas id="chart-cpu" class="bg-secondary"><i class="fas fa-chart-line fa-spin fa-lg"></i> Loading graph...</canvas>');
    drawLineChart('cpu');
    $('#chart-memoryPercent-container').html('<canvas id="chart-memoryPercent" class="bg-secondary"><i class="fas fa-chart-line fa-spin fa-lg"></i> Loading graph...</canvas>');
    drawLineChart('memoryPercent');
    $('#chart-memorySize-container').html('<canvas id="chart-memorySize" class="bg-secondary p-2"><i class="fas fa-chart-line fa-spin fa-lg"></i> Loading graph...</canvas>');
    drawPieChart('memorySize');
}
// -------------------------------------------------------------------------------------------
function drawPieChart(type)
{
    let typeLabels = typeData = typeColors = [];
    let typeLabel = '';
    const typeContainer = 'chart-' + type;

    if (!$('#' + typeContainer).length) {
        return;
    }

    switch (type) {
        case 'memorySize':
            typeLabel   = 'Memory Usage - MiB';
            typeLabels  = JSON.parse(GRAPH_UTILIZATION_MEMORY_SIZE_LABELS);
            typeData    = JSON.parse(GRAPH_UTILIZATION_MEMORY_SIZE_DATA);
            typeColors  = JSON.parse(GRAPH_UTILIZATION_MEMORY_SIZE_COLORS);
            break;
    }

    let data = {
        labels: typeLabels,
        datasets: [{
            label: typeLabel,
            data: typeData,
            backgroundColor: typeColors,
            borderWidth: 1
        }]
    };

    let options = {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            datalabels: {
                display: data.labels.length < 10 ? true : false,
                formatter: (value) => {
                    return value + 'MiB';
                },
                color: '#ffffff',
                font: {
                    weight: 'bold',
                    size: 16
                }
            },
            legend: {
                display: false,
                position: data.labels.length < 10 ? 'bottom' : 'left',
                fullSize: true
            },
            title: {
                display: false,
                text: typeLabel
            }
        },
    };

    let chart = new Chart(typeContainer, {
        type: 'doughnut',
        plugins: [ChartDataLabels],
        data: data,
        options: options
    });

    const legendContainer = document.getElementById('chart-memorySizeLegend-container');
    legendContainer.innerHTML = '';

    data.labels.forEach((label, index) => {
        const legendItem = document.createElement('div');
        legendItem.id = label+index+"@parent";
        legendItem.style.opacity = 1;
        legendItem.style.display = 'flex';
        legendItem.style.alignItems = 'center';
        legendItem.style.cursor = 'pointer';
        legendItem.style.marginBottom = '8px';

        const colorBox = document.createElement('span');
        colorBox.id = label+index+"@box";
        colorBox.style.width = '16px';
        colorBox.style.height = '16px';
        colorBox.style.backgroundColor = data.datasets[0].backgroundColor[index];
        colorBox.style.marginRight = '8px';
        colorBox.style.borderRadius = '4px';
        legendItem.appendChild(colorBox);

        const labelText = document.createElement('span');
        labelText.id = label+index+"@text";
        labelText.textContent = label;
        legendItem.appendChild(labelText);

        legendItem.addEventListener('click', (e) => {
            let targetElem = document.getElementById(e.target.id.split("@")[0]+"@parent");
            if (targetElem.style.opacity == 1) {
                targetElem.style.opacity = 0.5;
            } else {
                targetElem.style.opacity = 1;
            }

            const meta = chart.getDatasetMeta(0);
            meta.data[index].hidden = !meta.data[index].hidden;
            chart.update();
        });

        legendContainer.appendChild(legendItem);
    });
}
// -------------------------------------------------------------------------------------------
function drawLineChart(type)
{
    let typeLabels = typeData = [];
    let typeLabel = '';
    const typeContainer = 'chart-' + type;

    if (!$('#' + typeContainer).length) {
        return;
    }

    switch (type) {
        case 'cpu':
            typeLabel   = 'CPU Usage %';
            typeLabels  = JSON.parse(GRAPH_UTILIZATION_CPU_LABELS);
            typeData    = JSON.parse(GRAPH_UTILIZATION_CPU_DATA);
            break;
        case 'memoryPercent':
            typeLabel   = 'Memory Usage %';
            typeLabels  = JSON.parse(GRAPH_UTILIZATION_MEMORY_PERCENT_LABELS);
            typeData    = JSON.parse(GRAPH_UTILIZATION_MEMORY_PERCENT_DATA);
            break;
    }

    const rootStyles = getComputedStyle(document.documentElement);
    const backgroundColor = rootStyles.getPropertyValue('--bs-primary').trim();

    let data = {
        labels: typeLabels,
        datasets: [{
            label: typeLabel,
            data: typeData,
            borderWidth: 1,
            borderColor: backgroundColor,
            backgroundColor: backgroundColor
        }]
    };

    let options = {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: typeLabels.length > 10 ? true : false,
        indexAxis: 'y',
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    display: true,
                    autoSkip: false
                }
            },
            x: {
                ticks: {
                    display: true,
                    autoSkip: false
                }
            }
        }
    };

    new Chart(typeContainer, {
        type: 'bar',
        data: data,
        options: options
    });
}
// -------------------------------------------------------------------------------------------
function toggleOverviewView()
{
    $.ajax({
        type: 'POST',
        url: 'ajax/overview.php',
        data: '&m=toggleOverviewView&enabled=' + ($('#overviewDetailed').prop('checked') ? 1 : 0),
        success: function (resultData) {
            initPage('overview');
        }
    });
}
// -------------------------------------------------------------------------------------------
