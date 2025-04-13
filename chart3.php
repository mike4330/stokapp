<!-- Copyright (C) 2024 Mike Roetto <mike@roetto.org>
SPDX-License-Identifier: GPL-3.0-or-later -->
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" type="text/css" href="main.css">
    <link rel="stylesheet" type="text/css" href="nav.css">
    <title>Position Size Charts</title>
    <script src="/js/jquery-3.1.1.min.js"></script>
    <script type="text/javascript" src="/js/chart.js"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
    <!-- <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script> -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/luxon@^2"></script> -->
    <script type="text/javascript" src="/js/luxon.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@^1"></script>
</head>

<body>
    <?php
    include("nav.php");
    $dir = 'sqlite:portfolio.sqlite';
    $dbh = new PDO($dir) or die("cannot open the database");
    ?>

    <table class="chart">
        <tr>
            <td>
                <div class="chart-container4"><canvas id="AMX"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="ANGL"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="ASML"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="AVGO"></canvas></div>
            <td>


        </tr>
        <tr>

            <td>
                <div class="chart-container4"><canvas id="BRKB"></canvas></div>
            <td>
            <td> <div class="chart-container4"><canvas id="BNDX"></canvas></div><td>
            <td> <div class="chart-container4"><canvas id="BRT"></canvas></div><td>
            <td>
                <div class="chart-container4"><canvas id="CARR"></canvas></div>
            <td>

        </tr>
        <tr>
            <td>  <div class="chart-container4"><canvas id="D"></canvas></div><td>
            <td>
                <div class="chart-container4"><canvas id="DBB"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="DGX"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="EMB"></canvas></div>
            <td>

        </tr>
        <tr>
            <td>
                <div class="chart-container4"><canvas id="EVC"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="EWJ"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="FAF"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="FAGIX"></canvas></div>
            <td>
        </tr>

        <tr>
            <td>
                <div class="chart-container4"><canvas id="FDGFX"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="FNBGX"></canvas></div>
            <td>
         
            <td>
                <div class="chart-container4"><canvas id="FTS"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="GAMB"></canvas></div>
            <td>
        </tr>
        <tr>

            <td>
                <div class="chart-container4"><canvas id="HUN"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="INGR"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="IPAR"></canvas></div>
            <td>

        <td>    <div class="chart-container4"><canvas id="IRMD"></canvas></div>     <td>
        </tr>
        <tr>
            <td>
                <div class="chart-container4"><canvas id="JPIB"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="KMB"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="LKOR"></canvas></div>
            <td>
         
        <td> <div class="chart-container4"><canvas id="LYB"></canvas></div><td>
        </tr>
        <tr>
        
            <td>
                <div class="chart-container4"><canvas id="MPW"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="NHC"></canvas></div>
            <td>
            <td><div class="chart-container4"><canvas id="NICE"></canvas></div>  <td>
            <td><div class="chart-container4"><canvas id="NVDA"></canvas></div>  <td>
        </tr>
        <tr>

            <td>
                <div class="chart-container4"><canvas id="NXST"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="NVS"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="PANW"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="PBR"></canvas></div>
            <td>

        </tr>
        <tr>
            <td>
                <div class="chart-container4"><canvas id="PDBC"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="PGHY"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="PLD"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="REM"></canvas></div>
            <td>
        </tr>
        <tr>
            <td>
                <div class="chart-container4"><canvas id="RMD"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="SCI"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="SGOL"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="SIVR"></canvas></div>
            <td>
        </tr>
        <tr>

            <td>
                <div class="chart-container4"><canvas id="SJNK"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="SMCI"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="SKY"></canvas></div>
            <td>

        </tr>
        <tr>
            <td>
                <div class="chart-container4"><canvas id="TAIT"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="TDTF"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="TGS"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="TSLA"></canvas></div>
            <td>
        </tr>
        <tr>
        
            <td>
                <div class="chart-container4"><canvas id="TXNM"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="ULTA"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="VCSH"></canvas></div>
            <td>
            <td>
                <div class="chart-container4"><canvas id="VMC"></canvas></div>
            <td>
        </tr>
<tr>
            <td>
                <div class="chart-container4"><canvas id="WDFC"></canvas></div>
            </td></tr>
    </table>

    <script>
        Chart.defaults.interaction.mode = 'nearest';
        Chart.defaults.datasets.line.fill = true;
        Chart.defaults.datasets.line.borderWidth = 0;
        Chart.defaults.animation.duration = 225;
        Chart.defaults.datasets.line.pointRadius = 0;
        Chart.overrides.line.tension = 0.1;

        const array = ["AMX", "ASML", "ANGL", "AVGO", "BNDX","BRKB", "BRT",
             "CARR", "D", "DBB", "DGX", "EMB", "EVC", "EWJ", "FAF", "FAGIX",
            "FDGFX", "FNBGX", "FPE", "FTS", "GAMB", "HUN", "INGR", "IPAR","IRMD" ,"JPIB", "KMB", "LKOR",
            "LYB",  "MPW", "NHC", "NICE", "NVDA","NVS", "NXST", "PANW", "PBR", "PDBC", "PGHY", "PLD",
            "TXNM", "REM", "RMD","SCI", "SGOL", "SIVR", "SJNK", "SKY","SMCI", "TAIT", "TDTF",
            "TGS", "TSLA", "ULTA", "VALE", "VCSH", "VMC", "WDFC"
        ];

        array.forEach(function (item, index) {

            $.ajax({
                url: 'datacsv.php?q=cumshares2&symbol=' + item,
                type: 'GET',
                dataType: 'json',

                success: function (data) {
                    const rvalue = Math.floor(Math.random() * 235);
                    const gvalue = Math.floor(Math.random() * 235);
                    var bvalue = Math.floor(Math.random() * 235);
                    var bgstring = 'rgba(' + rvalue + ',' + gvalue + ',' + bvalue + ',.6)';

                    var cumshares = [];
                    var date = [];

                    for (var i in data) {
                        cumshares.push(data[i].cumshares);
                        date.push(data[i].date);
                    }

                    var chartdata = {
                        labels: date,
                        datasets: [{
                            label: 'Shares',
                            backgroundColor: bgstring,
                            data: cumshares,
                        }]
                    };

                    //         var ctx = $('#VMC');

                    // console.log(item, index);

                    var iname = '#' + item;
                    var ctx = $(iname);

                    var barGraph = new Chart(ctx, {
                        type: 'line',
                        data: chartdata,
                        options: {
                            maintainAspectRatio: false,
                            pointRadius: 0,
                            stepped: true,
                            plugins: {
                                title: {
                                    text: item,
                                    display: true
                                },
                                legend: {
                                    display: false
                                }
                            },
                            responsive: true,
                            scales: {
                                x: {
                                    type: 'time'
                                },
                                //                     y: {min: 0}
                            }
                        }
                    });

                } // end success func

            }); //end ajax object

        }); //end loop
    </script>

</body>

</html>
