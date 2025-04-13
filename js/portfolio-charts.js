/**
 * Chart initialization and configuration for portfolio view
 */

async function createPriceChart(symbol) {
    try {
        const response = await fetch(`datacsv.php?q=secprices&symbol=${symbol}`);
        const data = await response.json();
        
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

        // Calculate appropriate y-axis minimum
        const minValue = Math.min(...close);
        const yAxisMin = Math.max(0, minValue * 0.95);

        const chartdata = {
            labels: date,
            datasets: [{
                label: 'Price',
                backgroundColor: bgstring,
                borderWidth: 1.1,
                borderColor: 'rgb(8, 8, 8)',
                radius: 0,
                spanGaps: true,
                data: close,
            },
            {
                label: 'Mean',
                data: Array(date.length).fill(avg),
                borderColor: 'rgb(16, 16, 240)',
                borderWidth: 1,
                borderDash: [5, 5],
                fill: false,
                radius: 0
            },
            {
                label: '+1σ',
                data: Array(date.length).fill(std1),
                borderColor: 'rgb(232, 32, 32)',
                borderWidth: 1,
                borderDash: [5, 5],
                fill: false,
                radius: 0
            },
            {
                label: '-1σ',
                data: Array(date.length).fill(std1L),
                borderColor: 'rgb(232, 32, 32)',
                borderWidth: 1,
                borderDash: [5, 5],
                fill: false,
                radius: 0
            },
            {
                label: '-2σ',
                data: Array(date.length).fill(std2L),
                borderColor: 'rgb(24, 240, 24)',
                borderWidth: 1,
                borderDash: [5, 5],
                fill: false,
                radius: 0
            }]
        };

        const ctx = document.getElementById('chart').getContext('2d');
        const dv = Math.min(...close) < std3L ? 1 : 0;
        const h2 = Math.max(...close) > std2H ? 1 : 0;

        // Add conditional datasets for -3σ and +2σ
        if (dv) {
            chartdata.datasets.push({
                label: '-3σ',
                data: Array(date.length).fill(std3L),
                borderColor: 'rgb(64, 64, 192)',
                borderWidth: 1,
                borderDash: [5, 5],
                fill: false,
                radius: 0
            });
        }

        if (h2) {
            chartdata.datasets.push({
                label: '+2σ',
                data: Array(date.length).fill(std2H),
                borderColor: 'rgb(24, 240, 24)',
                borderWidth: 1,
                borderDash: [5, 5],
                fill: false,
                radius: 0
            });
        }

        new Chart(ctx, {
            type: 'line',
            data: chartdata,
            options: {
                fill: true,
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    annotation: {
                        annotations: {}
                    },
                    title: {
                        display: true,
                        text: `Price History - ${symbol}`,
                        font: {
                            size: 16
                        },
                        padding: 10
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'line'
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'time'
                    },
                    y: {
                        min: yAxisMin
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating price chart:', error);
    }
}

async function createReturnChart(symbol) {
    try {
        const response = await fetch(`datacsv.php?symreturn=${symbol}&tf=180`);
        const data = await response.json();
        
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
                    borderWidth: 1.25,
                    fill: true
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
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: `Returns - ${symbol}`,
                        font: {
                            size: 16
                        },
                        padding: 10
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating return chart:', error);
    }
}

async function createYieldChart(symbol) {
    try {
        const response = await fetch(`utils/api2.php?symbol=${symbol}`);
        const data = await response.json();
        
        const ctx = document.getElementById('yieldchart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => item.declare_date),
                datasets: [{
                    label: 'Yield',
                    data: data.map(item => item.yield),
                    borderColor: 'rgb(16, 128, 16)',
                    backgroundColor: 'rgba(16, 128, 16, 0.1)',
                    radius: 1.95,
                    tension: .4,
                    borderWidth: 1.5,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: `Yield History - ${symbol}`,
                        font: {
                            size: 16
                        },
                        padding: 10
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating yield chart:', error);
    }
}

async function createCBPSChart(symbol) {
    try {
        const response = await fetch(`api/security_values.php?symbol=${symbol}&period=1y`);
        const data = await response.json();
        
        const ctx = document.getElementById('cbpschart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(row => row.timestamp),
                datasets: [
                    {
                        label: 'Price',
                        data: data.map(row => ({
                            x: row.timestamp,
                            y: parseFloat(row.close)
                        })),
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1,
                        yAxisID: 'y',
                        borderWidth: 1.5,
                        radius: 0
                    },
                    {
                        label: 'Cost Basis/Share',
                        data: data.map(row => ({
                            x: row.timestamp,
                            y: parseFloat(row.cbps)
                        })),
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.1,
                        yAxisID: 'y',
                        borderWidth: 1.5,
                        radius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    title: {
                        display: true,
                        text: `Cost Basis vs Price - ${symbol}`,
                        font: {
                            size: 16
                        },
                        padding: 10
                    },
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'line'
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'month',
                            displayFormats: {
                                month: 'MMM yyyy'
                            }
                        },
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Price ($)'
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating CBPS chart:', error);
    }
}

async function initializeCharts(symbol) {
    try {
        await Promise.all([
            createPriceChart(symbol),
            createReturnChart(symbol),
            createYieldChart(symbol),
            createCBPSChart(symbol)
        ]);
    } catch (error) {
        console.error('Error initializing charts:', error);
    }
}

function getStandardDeviation(array) {
    const n = array.length;
    const mean = array.reduce((a, b) => a + b) / n;
    return Math.sqrt(array.map(x => Math.pow(x - mean, 2)).reduce((a, b) => a + b) / n);
} 