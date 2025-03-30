/**
 * Chart initialization and configuration for portfolio view
 */

function initializeCharts(symbol) {
    // Price Chart
    $.ajax({
        url: 'datacsv.php?q=secprices&symbol=' + symbol,
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            initializePriceChart(data, symbol);
        }
    });

    // Return Chart
    fetch('datacsv.php?symreturn=' + symbol + '&tf=180')
        .then(response => response.json())
        .then(data => {
            initializeReturnChart(data, symbol);
        });

    // Yield Chart
    fetch('utils/api2.php?symbol=' + symbol)
        .then(response => response.json())
        .then(data => {
            initializeYieldChart(data, symbol);
        });
}

function initializePriceChart(data, symbol) {
    const rvalue = Math.floor(Math.random() * 235);
    const gvalue = Math.floor(Math.random() * 235);
    const bvalue = Math.floor(Math.random() * 235);
    const bgstring = 'rgba(' + rvalue + ',' + gvalue + ',' + bvalue + ',.5)';
    
    Chart.defaults.datasets.line.borderWidth = .2;
    Chart.defaults.animation.duration = 225;
    Chart.overrides.line.tension = 0.1;

    const close = data.map(item => parseFloat(item.posvalue));
    const date = data.map(item => item.date);

    const totalSum = close.reduce((a, b) => a + b, 0);
    const std = getStandardDeviation(close);
    const numsCnt = close.length;
    const avg = (totalSum / numsCnt);
    
    const std1 = (avg + (std * 1));
    const std2H = (avg + (std * 2));
    const std1L = (avg - (std * 1));
    const std2L = (avg - (std * 2));
    const std3L = (avg - (std * 3));

    const chartdata = {
        labels: date,
        datasets: [{
            label: 'Value',
            backgroundColor: bgstring,
            borderWidth: 1.1,
            borderColor: 'rgb(8, 8, 8)',
            radius: 0,
            spanGaps: true,
            data: close,
        }]
    };

    const ctx = $(chart);
    const dv = Math.min(...close) < std3L ? 1 : 0;
    const h2 = Math.max(...close) > std2H ? 1 : 0;

    new Chart(ctx, {
        type: 'line',
        data: chartdata,
        options: {
            fill: true,
            maintainAspectRatio: false,
            plugins: {
                annotation: {
                    annotations: {
                        line2: {
                            type: 'line',
                            borderColor: 'rgb(16, 16, 240)',
                            borderWidth: .75,
                            enabled: true,
                            scaleID: 'y',
                            value: avg,
                            label: {
                                backgroundColor: 'rgba(0,0,255,.9)',
                                padding: 2,
                                content: 'mean',
                                position: 'start',
                                enabled: true,
                                borderRadius: 2
                            },
                        },
                        line3: {
                            type: 'line',
                            borderColor: 'rgb(232, 32, 32)',
                            borderWidth: .75,
                            enabled: true,
                            scaleID: 'y',
                            value: std1,
                            label: {
                                backgroundColor: 'rgba(255,0,0,.5)',
                                content: '+1σ',
                                padding: 2,
                                position: 'start',
                                enabled: true,
                                borderRadius: 2
                            },
                        },
                        line4: {
                            type: 'line',
                            borderColor: 'rgb(232, 32, 32)',
                            borderWidth: .75,
                            enabled: true,
                            scaleID: 'y',
                            value: std1L,
                            label: {
                                backgroundColor: 'rgba(255,0,0,.5)',
                                content: '-1σ',
                                padding: 1,
                                position: 'start',
                                enabled: true,
                                borderRadius: 2
                            },
                        },
                        line5: {
                            type: 'line',
                            borderColor: 'rgb(24, 240, 24)',
                            borderWidth: .75,
                            enabled: true,
                            scaleID: 'y',
                            value: std2L,
                            label: {
                                backgroundColor: 'rgba(24, 140, 24,.5)',
                                content: '-2σ',
                                padding: 1,
                                position: 'start',
                                enabled: true
                            },
                        },
                        line6: {
                            type: 'line',
                            display: dv,
                            borderColor: 'rgb(64, 64, 192)',
                            borderWidth: 1,
                            enabled: dv,
                            scaleID: 'y',
                            value: std3L,
                            label: {
                                backgroundColor: 'rgba(64, 64, 128,.5)',
                                content: '-3σ',
                                position: 'start',
                                enabled: true
                            },
                        },
                        line7: {
                            type: 'line',
                            display: h2,
                            borderColor: 'rgb(64, 64, 192)',
                            borderWidth: 1,
                            padding: 1,
                            enabled: h2,
                            scaleID: 'y',
                            value: std2H,
                            label: {
                                backgroundColor: 'rgba(24, 140, 24,.5)',
                                content: '+2σ',
                                position: 'start',
                                padding: 1,
                                enabled: true
                            },
                        },
                    }
                },
                title: {
                    text: symbol,
                    display: true
                },
                legend: {
                    display: false
                },
                responsive: true,
                scales: {
                    x: {
                        type: 'time'
                    },
                    y: {
                        min: 0
                    }
                }
            }
        }
    });
}

function initializeReturnChart(data, symbol) {
    const ctx = document.getElementById('returnchart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.date),
            datasets: [{
                label: 'Return',
                data: data.map(item => item.rtn),
                borderColor: 'rgb(75, 75, 255)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                radius: 1,
                borderWidth: 1.25
            },
            {
                label: 'Zero Line',
                data: Array(data.length).fill(0),
                borderColor: 'rgb(255, 0, 0)',
                backgroundColor: 'rgba(255, 0, 0, 0.1)',
                borderWidth: 3,
                radius: 0
            }]
        },
        options: {
            maintainAspectRatio: true,
            responsive: true,
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
}

function initializeYieldChart(data, symbol) {
    const ctx = document.getElementById('yieldchart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.declare_date),
            datasets: [{
                label: 'Yield',
                data: data.map(item => item.yield),
                borderColor: 'rgb(16, 128, 16)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                radius: 1.95,
                tension: .4,
                borderWidth: 1.5
            }]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                }
            },
            maintainAspectRatio: true,
            responsive: true,
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
}

function getStandardDeviation(array) {
    const n = array.length;
    const mean = array.reduce((a, b) => a + b) / n;
    return Math.sqrt(array.map(x => Math.pow(x - mean, 2)).reduce((a, b) => a + b) / n);
}

// WinBox initialization
function initializeWinBoxes(symbol) {
    const returnContainer = document.getElementById("returnContainer");
    if (returnContainer) {
        new WinBox({
            class: "white",
            header: 25,
            title: "Return for " + symbol,
            mount: returnContainer,
            y: "bottom",
            width: "500px",
            height: "280px"
        });
    }

    const yieldContainer = document.getElementById("yieldContainer");
    if (yieldContainer) {
        new WinBox({
            class: "white",
            header: 25,
            title: "Yield for " + symbol,
            mount: yieldContainer,
            y: 725,
            width: "500px",
            height: "280px"
        });
    }
} 