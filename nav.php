<!--Copyright (C) 2022 Mike Roetto <mike@roetto.org>
    SPDX-License-Identifier: GPL-3.0-or-later-->


    
<?php

session_start();

if (!isset($_SESSION['is_logged_in'])) {
  header("Location: auth/index.php");
  exit();
}

$dir = 'sqlite:portfolio.sqlite';
echo '<img class="logo" src="/portfolio/res/mpmlogo2.svg" width="50px" >';

echo ' <div class="navbar">

  <a href="/portfolio/alloc.php">ALLOCATIONS</a>
    <div class="dropdown">
    <button class="dropbtn">CHARTS
      <i class="fa fa-caret-down"></i>
    </button>
    <div class="dropdown-content">
    <button><a href="/portfolio/divcharts.php">⤇ Dividends</a></button>
    <button><a href="/portfolio/reports/income.php">⤇ Income</a></button>
    <button><a href="/portfolio/reports/sectoralloc.php">⤇ Model Sector Allocations</a></button>
    <button><a href="/portfolio/reports/symbolweights.php">⤇ Model Target Tracking</a></button>
      <button><a href="/portfolio/chart3.php">⤇ Position Sizes</a></button>
      <button><a href="/portfolio/posvalues.php">⤇ Positions Values</a></button>
      <button><a href="/portfolio/returns.php">⤇ Returns</a></button>
      <button><a href="/portfolio/div.php">⤇ Value Trends</a></button>
    </div>
  </div>
  <a href="/portfolio/holdings.php">HOLDINGS</a>
  <a href="/portfolio/lots.php">LOTS</a>
  <a href="/portfolio/mpt.php">MODELLING</a>
  <a href="/portfolio/sectors.php">SECTORS</a>
  <a href="/portfolio/">TRANSACTIONS</a>
  <div class="dropdown">
  <button class="dropbtn">ANALYSIS
    <i class="fa fa-caret-down"></i>
  </button>
  <div class="dropdown-content">
    <button><a href="/portfolio/divpredict.php">Dividend Prediction</a></button>
    <button><a href="/portfolio/reports/pairs.php">Pair Analysis</a></button>
    <button><a href="/portfolio/lotmgmt.php">Lot Management</a></button>
  </div>
 
</div>
  <a href="/portfolio/auth/">🔐 Login</a>
   <a href="/portfolio/auth/logout.php">🚪 logout</a>

</div> ';

function gpv()
{
  $dir = 'sqlite:portfolio.sqlite';
  $dbh = new PDO($dir) or die("cannot open the database");
  $ttotal = 0;
  $q = "SELECT DISTINCT symbol FROM transactions order by symbol";
  foreach ($dbh->query($q) as $trow) {
    $tsym = $trow['symbol'];
    $subquery = "select sum(units) as buyunits from transactions where xtype = 'Buy' and symbol = '$tsym'";
    $stmt = $dbh->prepare($subquery);
    $stmt->execute();
    $zrow = $stmt->fetch();
    $buyunits = $zrow['buyunits'];
    $subquery = "select sum(units) as sellunits from transactions where xtype = 'Sell' and symbol = '$tsym'";
    $stmt = $dbh->prepare($subquery);
    $stmt->execute();
    $zrow = $stmt->fetch();
    $sellunits = $zrow['sellunits'];
    $netunits = ($buyunits - $sellunits);
    $subquery = "select price from prices where symbol = '$tsym'";
    $stmt = $dbh->prepare($subquery);
    $stmt->execute();
    $zrow = $stmt->fetch();
    $cprice = $zrow['price'];
    if ($netunits == 0) {
      continue;
    }
    $value = round(($netunits * $cprice), 3);
    $ttotal = ($ttotal + $value);
  }
  return $ttotal;
}


// Initialize an empty array to store the colors
$colors = [];

// Use a while loop to generate and add colors to the array until it has 61 elements
while (count($colors) < 200) {
  // Generate a random hexadecimal color value
  $color = dechex(mt_rand(0x000000, 0xFFFFFF));
  // Pad the color value with leading zeros if it is less than 6 characters long
  $color = str_pad($color, 6, "0", STR_PAD_LEFT);
  // Add a # character to the beginning of the color value
  $color = "#" . $color;
  // Add the color to the array if it is not already present
  if (!in_array($color, $colors)) {
    $colors[] = $color;
  }
}



$contrasting_colors = array();
foreach ($colors as $color) {
  $rgb = sscanf($color, "#%02x%02x%02x");
  $luminance = 1 - (0.299 * $rgb[0] + 0.587 * $rgb[1] + 0.114 * $rgb[2]) / 255;
  if ($luminance < 0.5) {
    $contrasting_colors[] = '#000000';
  } else {
    $contrasting_colors[] = '#ffffff';
  }
}

function generateLineChart($symbolName)
{
  // API Call URL
  $apiUrl = "datacsv.php?symreturn=" . $symbolName . "&tf=180";

  // Fetch the data from the API
  $apiData = file_get_contents($apiUrl);

  // Process the data and extract the necessary values for the chart
  // ...

  // Prepare the Chart.js configuration
  $chartConfig = [
    "type" => "line",
    "data" => [
      // Set up the chart data using the extracted values
      // ...
    ],
    "options" => [
      // Configure other options such as title, axis labels, etc.
      // ...
    ]
  ];

  // Generate the JavaScript code to render the chart
  $chartScript = "
        <script>
            var ctx = document.getElementById('myChart').getContext('2d');
            var myChart = new Chart(ctx, " . json_encode($chartConfig) . ");
        </script>
    ";

  // Return the chart HTML markup and the JavaScript code
  return "<canvas id='myChart'></canvas>" . $chartScript;
}



?>
