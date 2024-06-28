<!DOCTYPE html>
<html lang="en">

<head>
<meta http-equiv="refresh" content="120">
<link rel="stylesheet" type="text/css" href="main.css">
<link rel="stylesheet" type="text/css" href="nav.css">
<style>
td {
padding-left: 1vw;
font-family: monospace;
font-size: 1.5vh;
}

table {margin: 0px;}

div.main {
width: 90vw;
margin: auto;
position: absolute;
background: #00eeee;
display:table;
}
</style>

<title>Miscellaneous Reports</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<style>
        #chartContainer {
            width: 90vw;
            height: 60vh;
            position: absolute;
            bottom: 1vh;
            background: #0e0e0e;
        }
        #myChart {
            width: 100%;
            height: 100%;
        }
    </style>
</head>

<body>

<?php
include ("nav.php");

$dbh  = new PDO($dir) or die("cannot open the database");
$query = "select date_new,(price*units) as cost,symbol,xtype from transactions order by cost desc limit 10";

echo '<div class="main">';

echo "<table style=\"position:absolute; top:100px; left: 66vw; z-index:1;\">
<th colspan=4>Top Ten Transactions by cost</th>";
foreach ($dbh->query($query) as $row) {
	$cost=round($row['cost'],2);
    echo "<tr>
    <td >$row[date_new]</td>
    <td >$row[symbol]</td>
    <td >$row[xtype]</td>
    <td >$cost</td>
    </tr>";
}
echo "</table>";

echo "<table style=\"position:absolute; top:100px; left:1vw; z-index:1;\">
<th colspan=4>Top Ten Dividend Payouts</th>";
$query = "select date_new,(price*units) as cost,symbol,xtype from transactions where xtype = 'Div' order by cost desc limit 10";
foreach ($dbh->query($query) as $row) {
    $div=round($row['cost'],2);
    echo "<tr>
    <td >$row[date_new]</td>
    <td >$row[symbol]</td>
    <td >$row[xtype]</td>
    <td >$div</td>
    </tr>";
}
echo "</table>";

echo "<table style=\"position:absolute; top:100px; left:33vw; z-index:1;\">
<th colspan=4>Top Ten Realized Gain</th>";
$query = "select date_new,gain,symbol,xtype from transactions where xtype = 'Sell' order by gain desc limit 10";
foreach ($dbh->query($query) as $row) {
    $gain=round($row['gain'],2);
    echo "<tr>
    <td >$row[date_new]</td>
    <td >$row[symbol]</td>
    <td >$gain</td>
    </tr>";
}
echo "</table>";
echo '</div>';

$db = new SQLite3('portfolio.sqlite');
$results = $db->query('SELECT symbol, div_growth_rate FROM MPT');

$data = [];
while ($row = $results->fetchArray()) {
    $data[] = $row;
}

// echo json_encode($data);

    // Connect to the database and fetch data
    $db = new SQLite3('portfolio.sqlite');
    $results = $db->query('SELECT symbol, div_growth_rate 
    FROM MPT where div_growth_rate  !=0 and symbol IS NOT \'PDBC\' and symbol IS NOT \'DBB\' order by div_growth_rate');

    $symbols = [];
    $div_growth_rates = [];
    while ($row = $results->fetchArray()) {
        $symbols[] = $row['symbol'];
        $div_growth_rates[] = $row['div_growth_rate'];
    }

    // Convert PHP arrays to JavaScript arrays
    $symbols_js = json_encode($symbols);
    $div_growth_rates_js = json_encode($div_growth_rates);


?>
    <div id="chartContainer">
        <canvas id="myChart"></canvas>
    </div>
<script>
    // Get PHP arrays in JavaScript
    var symbols = <?php echo $symbols_js; ?>;
    var div_growth_rates = <?php echo $div_growth_rates_js; ?>;
    
    var ctx = document.getElementById('myChart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: symbols,
            datasets: [{
                label: 'Dividend Growth Rate',
                data: div_growth_rates,
                backgroundColor: 'rgba(0, 255, 0, 0.5)', //brighter shade of green with transparency 
                borderColor: 'rgba(0, 255, 0, 1)', //brighter shade of green with full transparency
                borderWidth: .8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: "rgba(0, 255, 0, 0.2)" // change color of gridlines to brighter green
                    },
                    ticks: {
                        color: "green", // change color of tickmarks to green
                        font: {
                            family: 'monospace' // switch to monospace font for tick labels
                        }
                    }
                }
            }
        }
    });
</script>


</body>
