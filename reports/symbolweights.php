<!DOCTYPE html>
<html>

<head>
    <title>Weight Target Comparison Charts</title>
    <style>
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(390px, 1fr));
            grid-gap: 20px;
            position: absolute;
            top: 47px;
            width: 95%;
        }

        .chart-container {
            width: 100%;
            height: 100%;
        }

        .chart-canvas {
            /* width: 100%;
            height: 100%; */
        }
    </style>
    <link rel="stylesheet" type="text/css" href="../nav.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php
    include("../nav.php");
    ?>
    <div class="grid-container">
        <?php
        // Define the symbols array
        $symbols = [
            'AMX',
            'ANGL',
            'ASML',
            'AVGO',
	    'BAH',
            'BG',
            'BRT',
            'BSIG',
            'CARR',
            'C',
            'D',
            'DBB',
            'DGX',
            'EMB',
            'EVC',
            'EWJ',
            'F',
            'FAGIX',
            'FNBGX',
            'FPE',
            'FTS',
            'GILD',
            'HPK',
            'HTLD',
            'HUN',
            'INGR',
            'IPAR',
            'JPIB',
            'KMB',
            'LKOR',
            'LYB',
            'MLN',
            'MPW',
            'NHC',
            'NICE',
            'NXST',
            'NVS',
            'OTIS',
            'PDBC',
            'PLD',
            'PNM',
            'REM',
            'SCI',
            'SGOL',
            'SIVR',
            'SSNC',
            'TAIT',
            'TGS',
            'TSLA',
            'VCSH',
            'VMC'
        ];

        // Loop through the symbols array
        foreach ($symbols as $symbol) {
            // Call the API for each symbol
            $url = "http://localhost/portfolio/datacsv.php?q=weightcomp&symbol=" . urlencode($symbol);
            $data = json_decode(file_get_contents($url), true);

            // Extract the required data from the API response
            $dates = [];
            $weights = [];
            $targets = [];
            foreach ($data as $item) {
                $dates[] = $item['date'];
                $weights[] = $item['weight'] * 100;
                $targets[] = $item['target'] * 100;
            }
            ?>
            <div class="chart-container">
                <canvas class="chart-canvas" id="<?php echo $symbol; ?>Chart"></canvas>
            </div>

            <script>


                function generateDivergentColors(numColors) {
                    var colors = [];
                    var midpoint = numColors / 2;

                    for (var i = 0; i < numColors; i++) {
                        var red, green, blue;

                        if (i < midpoint) {
                            red = Math.floor((midpoint - i) * (255 / midpoint));
                            green = 0;
                            blue = Math.floor(i * (255 / midpoint));
                        } else {
                            red = 0;
                            green = Math.floor((i - midpoint) * (255 / midpoint));
                            blue = Math.floor((numColors - i) * (255 / midpoint));
                        }

                        var alpha = Math.floor(255 * 0.7);

                        var hexColor = '#' + ((red << 16) | (green << 8) | blue).toString(16).padStart(6, '0') + alpha.toString(16).padStart(2, '0');

                        colors.push(hexColor);
                    }

                    return colors;
                }
                var divergentColors = generateDivergentColors(53);
                // divergentColors = divergentColors.map(color => `rgba(${color[0]}, ${color[1]}, ${color[2]}, 0.8)`);

                // var color1 = divergentColors[Math.floor(Math.random() * divergentColors.length)];
                var color2 = divergentColors[Math.floor(Math.random() * divergentColors.length)];
                console.log(divergentColors);
                var color1 = 'rgba(12, 12, 12, 1)';
                // var color2 = 'rgba(255, 12, 12, 1)';
                var gridcolor = 'rgb(168, 168, 168)'


                // Create a new chart for the symbol
                var ctx = document.getElementById("<?php echo $symbol; ?>Chart").getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($dates); ?>,
                        datasets: [{
                            label: '<?php echo $symbol; ?> - Targets',
                            data: <?php echo json_encode($targets); ?>,
                            borderColor: color1,
                            borderWidth: 1.5,
                            backgroundColor: color1,
                            pointRadius: 0,
                            tension: .15,
                            fill: false,
                        }, {
                            label: '<?php echo $symbol; ?> - Weights',
                            data: <?php echo json_encode($weights); ?>,
                            borderColor: color2,
                            borderWidth: 1.5,
                            backgroundColor: color2,
                            pointRadius: 0,
                            tension: .15,
                            fill: true,
                        }]
                    },
                    options: {
                        animation: false,
                        responsive: true,
                        scales: {
                            x: {
                                display: true,
                                grid: {
                                    color: gridcolor
                                },
                            },
                            y: {
                                display: true,
                                grid: {
                                    color: gridcolor
                                },
                                // suggestedMin: 0,
                                // suggestedMax: 1,
                            }
                        }
                    }
                });
            </script>
            <?php
        }
        ?>
    </div>
</body>

</html>
