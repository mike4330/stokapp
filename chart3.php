<html>

<head>
    <link rel="stylesheet" type="text/css" href="main.css">
    <link rel="stylesheet" type="text/css" href="nav.css">
    <title>Position Size Charts</title>
    <script src="/js/jquery-3.1.1.min.js"></script>
    <script type="text/javascript" src="/js/chart.js"></script>
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
                <div class="chart-container"><canvas id="AMX"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="ANGL"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="ASML"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="AVGO"></canvas></div>
            </td>


        </tr>
        <tr>
            <td>
                <div class="chart-container"><canvas id="BG"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="BRKB"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="C"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="CARR"></canvas></div>
            </td>

        </tr>
        <tr>
            <td>
                <div class="chart-container"><canvas id="D"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="DBB"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="DGX"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="EMB"></canvas></div>
            </td>

        </tr>
        <tr>
            <td>
                <div class="chart-container"><canvas id="EVC"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="EWJ"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="F"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="FAGIX"></canvas></div>
            </td>
        </tr>

        <tr>
                    <td>
                <div class="chart-container"><canvas id="FDGFX"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="FNBGX"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="FPE"></canvas></div>
            </td>

            <td>
                <div class="chart-container"><canvas id="GILD"></canvas></div>
            </td>

        </tr>
        <tr>
            <td>
                <div class="chart-container"><canvas id="HUN"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="IPAR"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="INGR"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="JPIB"></canvas></div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="chart-container"><canvas id="KMB"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="LYB"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="MLN"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="MPW"></canvas></div>
            </td>



        </tr>
        <tr>
                        <td>
                <div class="chart-container"><canvas id="PDBC"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="PLD"></canvas></div>
            </td>
                 <td>
                <div class="chart-container"><canvas id="PNM"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="REM"></canvas></div>
            </td>



        </tr>
        <tr>
            <td>
                <div class="chart-container"><canvas id="SCI"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="NHC"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="NICE"></canvas></div>
            </td>
   

            <td>
                <div class="chart-container"><canvas id="NXST"></canvas></div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="chart-container"><canvas id="NVS"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="OTIS"></canvas></div>
            </td>
                     <td>
                <div class="chart-container"><canvas id="PANW"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="PBR"></canvas></div>
            </td>
        </tr>
<tr>

                        <td>
                <div class="chart-container"><canvas id="SGOL"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="SIVR"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="SSNC"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="TAIT"></canvas></div>
            </td></tr>
<tr>
            <td>
                <div class="chart-container"><canvas id="TSLA"></canvas></div>
            </td>
                     <td>
                <div class="chart-container"><canvas id="TGS"></canvas></div>
            </td>
                 <td>
                <div class="chart-container"><canvas id="ULTA"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="VALE"></canvas></div>
            </td></tr>
<tr>
    
            <td>
                <div class="chart-container"><canvas id="VCSH"></canvas></div>
            </td>
            <td>
                <div class="chart-container"><canvas id="VMC"></canvas></div>
            </td></tr>
</table>

    <script>

        Chart.defaults.interaction.mode = 'nearest';
        Chart.defaults.datasets.line.fill = true;
        Chart.defaults.datasets.line.borderWidth = 0;
        Chart.defaults.animation.duration = 225;
        Chart.defaults.datasets.line.pointRadius = 0;
        Chart.overrides.line.tension = 0.1;

        const array = ["AMX", "ASML", "ANGL", "AVGO", "BRKB", "BEN", "BG",
            "BSJN", "C", "CARR", "CNHI", "D", "DBB", "DGX", "EMB", "EVC", "EWJ", "F", "FAGIX", "FDGFX","FNBGX", "FPE",
             "GILD", "HUN", "INGR", "IPAR", "JPIB", "KMB", "LYB", "MLN", "MPW", "NHC",
            "NICE", "NVS", "NXST", "OTIS", "PANW","PBR", "PDBC", "PLD", "PNM","REM", "SCI", "SGOL", "SIVR", "SOXX", "SSNC", "TAIT",
            "TGS", "TSLA", "ULTA","VALE", "VCSH", "VMC"];

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
                                title: { text: item, display: true },
                                legend: { display: false }
                            },
                            responsive: true,
                            scales: {
                                x: { type: 'time' },
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
