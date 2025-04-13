<!--Copyright (C) 2022,2024 Mike Roetto <mike@roetto.org>
SPDX-License-Identifier: GPL-3.0-or-later-->
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" type="text/css" href="main.css">
    <link rel="stylesheet" type="text/css" href="nav.css">
    <title>Position Values over Time</title>
    <script src="/js/jquery-3.1.1.min.js"></script>
    <script type="text/javascript" src="/js/chart.js"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/luxon@^2"></script> -->
    <script type="text/javascript" src="/js/luxon.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@^1"></script>
    <style>
        .time-slider-container {
            padding: 15px 20px;
            background: #1a1a1a;
            margin: 40px 10px 40px 10px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .time-slider {
            width: 100%;
            height: 20px;
            -webkit-appearance: none;
            background: #333;
            border-radius: 10px;
            outline: none;
            margin: 0;  /* Remove margin */
            position: relative;
            z-index: 1;
        }
        .time-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            background: #4CAF50;
            border-radius: 50%;
            cursor: pointer;
            position: relative;
            z-index: 2;
        }
        .time-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            background: #4CAF50;
            border-radius: 50%;
            cursor: pointer;
            border: none;
            position: relative;
            z-index: 2;
        }
        .time-display {
            color: white;
            text-align: center;
            margin-top: 5px;
            font-family: monospace;
            font-size: 12px;
        }
        .tick-marks {
            display: flex;
            justify-content: space-between;
            padding: 0 10px;
            color: #888;
            font-size: 11px;
            font-family: monospace;
            position: absolute;
            top: 8px;
            left: 0;
            right: 0;
            pointer-events: none;
            z-index: 1;
        }
        .tick-mark {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .tick-mark::before {
            content: '';
            position: absolute;
            top: 18px;
            width: 1px;
            height: 6px;
            background: #555;
        }
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            padding: 10px;
            margin-top: 40px;  /* Increased top margin */
        }
        .chart-container {
            position: relative;
            height: 200px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 5px;
        }
        @media (max-width: 1200px) {
            .chart-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 900px) {
            .chart-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 600px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php
    include ("nav.php");
    $dir = 'sqlite:portfolio.sqlite';
    $dbh = new PDO($dir) or die("cannot open the database");
    ?>

    <div class="time-slider-container">
        <input type="range" min="30" max="1095" value="730" class="time-slider" id="timeSlider">
        <div class="tick-marks">
            <div class="tick-mark">30d</div>
            <div class="tick-mark">6m</div>
            <div class="tick-mark">1y</div>
            <div class="tick-mark">2y</div>
            <div class="tick-mark">3y</div>
        </div>
        <div class="time-display" id="timeDisplay">2 Years</div>
    </div>

    <div class="chart-grid" id="chartGrid">
        <!-- Charts will be dynamically inserted here -->
    </div>

</body>

<script>
    Chart.defaults.interaction.mode = 'nearest';
    Chart.defaults.datasets.line.fill = true;
    Chart.defaults.datasets.line.spanGaps = 1;
    Chart.defaults.datasets.line.borderWidth = .9;
    Chart.defaults.datasets.line.pointRadius = 0;

    const symbols = ["AMX", "ASML", "ANGL", "AVGO", "BNDX", "BRKB", "BRT",  "CARR", "BAH",
        "D", "DBB", "DFIN", "DGX",  "EVC", "EWJ", "FAF", "FAGIX", "FDGFX", "FNBGX", "FTS", "GAMB", "HPK", 
        "HUN", "IESC", "IMKTA",  "IPAR", "IRMD", "JPIB", "KMB", "LKOR", "LYB", , "MPW", "NICE",
        "NVS", "NXST",  "NVDA","PANW", "PBR", "PDBC", "PGHY", "PLD", "TXNM", "REM", "RMD",
        "SCI", "SGOL", "SIVR", "SJNK", "SKY", "SMCI", 
        "TAIT", "TDTF", "TGS", "TSLA", "ULTA", "USLM", "VALE", "VCSH", "VMC", "WDFC", "XAG"];

    // Store all chart instances
    const charts = {};

    // Function to generate a random color with opacity
    function getRandomColor() {
        // Use HSL for more control over vibrancy
        // Hue: 0-360 (full color spectrum)
        // Saturation: 70-100% (high saturation for vivid colors)
        // Lightness: 45-65% (bright enough to see, not too washed out)
        const hue = Math.floor(Math.random() * 360);
        const saturation = Math.floor(Math.random() * 30) + 70; // 70-100%
        const lightness = Math.floor(Math.random() * 20) + 45;  // 45-65%
        
        // Convert HSL to RGB for the rgba format
        const h = hue / 360;
        const s = saturation / 100;
        const l = lightness / 100;

        let r, g, b;

        if (s === 0) {
            r = g = b = l;
        } else {
            const hue2rgb = (p, q, t) => {
                if (t < 0) t += 1;
                if (t > 1) t -= 1;
                if (t < 1/6) return p + (q - p) * 6 * t;
                if (t < 1/2) return q;
                if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
                return p;
            };

            const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
            const p = 2 * l - q;

            r = hue2rgb(p, q, h + 1/3);
            g = hue2rgb(p, q, h);
            b = hue2rgb(p, q, h - 1/3);
        }

        // Convert to 0-255 range
        r = Math.round(r * 255);
        g = Math.round(g * 255);
        b = Math.round(b * 255);

        return `rgba(${r},${g},${b},0.8)`; // Increased opacity to 0.8 for better visibility
    }

    // Function to create a chart container
    function createChartContainer(symbol) {
        const container = document.createElement('div');
        container.className = 'chart-container';
        container.innerHTML = `<canvas id="${symbol}"></canvas>`;
        return container;
    }

    // Function to update time display
    function updateTimeDisplay(days) {
        const display = document.getElementById('timeDisplay');
        if (days >= 365) {
            display.textContent = `${Math.round(days/365)} Years`;
        } else {
            display.textContent = `${days} Days`;
        }
    }

    // Function to update all charts with new time range
    function updateCharts(days) {
        const now = new Date();
        const startDate = new Date(now.getTime() - (days * 24 * 60 * 60 * 1000));
        
        Object.values(charts).forEach(chart => {
            chart.options.scales.x.min = startDate.toISOString();
            chart.options.scales.x.max = now.toISOString();
            chart.update('none'); // Update without animation
        });
    }

    // Function to create a chart
    function createChart(symbol, data) {
        const ctx = document.getElementById(symbol).getContext('2d');
        
        // Calculate the date range for initial display (2 years)
        const now = new Date();
        const twoYearsAgo = new Date(now.getTime() - (2 * 365 * 24 * 60 * 60 * 1000));
        
        const chartData = {
            labels: data.map(d => d.date),
            datasets: [{
                label: 'Value',
                backgroundColor: getRandomColor(),
                spanGaps: true,
                data: data.map(d => d.posvalue)
            }]
        };

        const chart = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                animation: false,
                maintainAspectRatio: false,
                plugins: {
                    title: { 
                        text: symbol, 
                        display: true,
                        color: '#ffffff'
                    },
                    legend: { display: false }
                },
                responsive: true,
                scales: {
                    x: { 
                        type: 'time',
                        grid: {
                            color: 'rgba(255, 255, 255, 0.2)',
                            drawBorder: true,
                            borderColor: 'rgba(255, 255, 255, 0.3)'
                        },
                        ticks: {
                            color: '#ffffff'
                        },
                        min: twoYearsAgo.toISOString(),
                        max: now.toISOString()
                    },
                    y: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.2)',
                            drawBorder: true,
                            borderColor: 'rgba(255, 255, 255, 0.3)'
                        },
                        ticks: {
                            color: '#ffffff'
                        }
                    }
                }
            }
        });

        // Store the chart instance
        charts[symbol] = chart;
        return chart;
    }

    // Initialize the chart grid
    function initializeCharts() {
        const grid = document.getElementById('chartGrid');
        
        // Create containers for all symbols
        symbols.forEach(symbol => {
            grid.appendChild(createChartContainer(symbol));
        });

        // Load data and create charts
        symbols.forEach(symbol => {
            $.ajax({
                url: 'datacsv.php?q=posvalues&symbol=' + symbol,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    createChart(symbol, data);
                },
                error: function(xhr, status, error) {
                    console.error(`Error loading data for ${symbol}:`, error);
                    const container = document.getElementById(symbol).parentElement;
                    container.innerHTML = `<div style="color: red; text-align: center; padding: 10px;">Error loading data</div>`;
                }
            });
        });

        // Set up time slider event listener
        const timeSlider = document.getElementById('timeSlider');
        timeSlider.addEventListener('input', function() {
            const days = parseInt(this.value);
            updateTimeDisplay(days);
            updateCharts(days);
        });
    }

    // Start initialization when the page loads
    document.addEventListener('DOMContentLoaded', initializeCharts);
</script>

</html>
