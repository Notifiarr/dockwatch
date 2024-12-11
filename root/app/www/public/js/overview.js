function drawOverview() 
{
    $('#chart-cpu-container').html('<canvas id="chart-cpu" class="bg-secondary"><i class="fas fa-chart-line fa-spin fa-lg"></i> Loading graph...</canvas>');
    drawLineChart('cpu');
    $('#chart-memoryPercent-container').html('<canvas id="chart-memoryPercent" class="bg-secondary"><i class="fas fa-chart-line fa-spin fa-lg"></i> Loading graph...</canvas>');
    drawLineChart('memoryPercent');
    $('#chart-memorySize-container').html('<center style="height:75vh;"><canvas id="chart-memorySize" class="bg-secondary"><i class="fas fa-chart-line fa-spin fa-lg"></i> Loading graph...</canvas></center>');
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
        plugins: {
            datalabels: {
                display: data.labels.length < 10 ? true : false,
                backgroundColor: '#ccc',
                formatter: (value) => {
                    return value + 'MiB';
                }
            },
            legend: {
                display: true,
                position: data.labels.length < 10 ? 'bottom' : 'left'
            },
            title: {
                display: true,
                text: typeLabel
            }
        },
    };

    new Chart(typeContainer, {
        type: 'doughnut',
        plugins: [ChartDataLabels],
        data: data,
        options: options
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

    let data = {
        labels: typeLabels,
        datasets: [{
            label: typeLabel,
            data: typeData,
            borderColor: 'rgba(255, 0, 0, 0.4)',
            borderWidth: 1
        }]
    };

    let options = {
        aspectRatio: typeLabels.length > 10 ? true : false,
        responsive: true,
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
        url: '../ajax/overview.php',
        data: '&m=toggleOverviewView&enabled=' + ($('#overviewDetailed').prop('checked') ? 1 : 0),
        success: function (resultData) {
            initPage('overview');
        }
    });
}
// -------------------------------------------------------------------------------------------
