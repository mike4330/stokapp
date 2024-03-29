<!--Copyright (C) 2022-2023 Mike Roetto <mike@roetto.org>
SPDX-License-Identifier: GPL-3.0-or-later-->
<!DOCTYPE html>

<html>

<head>
    <link rel="stylesheet" type="text/css" href="test.css">
    <link rel="stylesheet" type="text/css" href="nav.css">
    <title>Div Predictions</title>

</head>



<?php

include("nav.php");

// Example usage
// $symbol = "BG";
// $forecastTable = qtrpredict($symbol);
// echo $forecastTable;

$table = "<table style='position: relative; top: 40px;'>";
$tickers = array(
    'ANGL',
    'AMX',
    'ASML',
    'AVGO',
    'BG',
    'BRT',
    'BSIG',
    'CARR',
    'C',
    'D',
    'DGX',
    'EMB',
    'EVC',
    'F',
    'FPE',
    'FAF',
    'FAGIX',
    'FNBGX',
    'FTS',
    'GILD',
    'HPK',
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
    'NXST',
    'OTIS',
    'PBR',
    'PNM',
    'PLD',
    'REM',
    'SCI',
    'VALE',
    'VMC',
    'VCSH'
);

$count = 0;
foreach ($tickers as $ticker) {
    if ($count % 8 == 0) {
        $table .= "<tr>"; // start new row
    }

    $monthlies = ['ANGL', 'EMB', 'FPE', 'JPIB', 'LKOR', 'FAGIX', 'FNBGX', 'MLN', 'VCSH'];
    if (in_array($ticker, $monthlies)) {
        $table .= "<td><div style='padding: .5vw;'>" . monthpredict($ticker) . "</div></td>";
        $count++;
        continue;
    }

    $table .= "<td><div style='padding: .5vw;'>" . qtrpredict($ticker) . "</div></td>";

    $count++;
    if ($count % 8 == 0) {
        $table .= "</tr>"; // end current row
    }
}

$table .= "</table>";
echo $table;

function qtrpredict($symbol)
{
    // API endpoint URL
    $apiUrl = "http://localhost/portfolio/datacsv.php?symquery=" . urlencode($symbol);

    // Fetch JSON data from the API
    $jsonData = file_get_contents($apiUrl);
    $data = json_decode($jsonData, true);

    // Extract necessary information from the response
    $date = $data[0]['date'];
    $symbol = $data[0]['symbol'];

    // Extract dividend amounts
    $dividendAmounts = array();
    foreach ($data as $entry) {
        $dividendAmounts[] = floatval($entry['cost']);
    }

    // Perform linear regression
    $dividendCount = count($dividendAmounts);
    $sumX = 0;
    $sumY = 0;
    $sumXY = 0;
    $sumXX = 0;

    for ($i = 0; $i < $dividendCount; $i++) {
        $x = $i + 1;
        $y = $dividendAmounts[$i];

        $sumX += $x;
        $sumY += $y;
        $sumXY += ($x * $y);
        $sumXX += ($x * $x);
    }

    $averageX = $sumX / $dividendCount;
    $averageY = $sumY / $dividendCount;

    $slope = ($sumXY - ($dividendCount * $averageX * $averageY)) / ($sumXX - ($dividendCount * $averageX * $averageX));
    $intercept = $averageY - ($slope * $averageX);

    // Calculate forecast starting from three months after the last data point
    $lastDataDate = $data[$dividendCount - 1]['date'];
    $nextQuarter = date('Y-m', strtotime($lastDataDate . ' + 3 months'));
    $startMonth = date('Y-m', strtotime($nextQuarter . ' + 0 months'));

    // echo $lastDataDate;

    // Generate 12-quarter forecast
    $forecast = array();
    for ($i = 0; $i < 49; $i++) {
        $forecastMonth = date('Y-m', strtotime($startMonth . ' + ' . (3 * $i) . ' months'));
        $forecast[] = array(
            'date' => $forecastMonth,
            'symbol' => $symbol,
            'cost' => number_format(($intercept + ($slope * ($dividendCount + $i * 3))), 8)
        );
    }

    // Get the last three entries from the forecast
    $lastThreeEntries = array_slice($forecast, -3);

    // Output the last three entries as an HTML table
    $html = '<table class="forecast">';
    $html .= "<tr><th colspan=3>$symbol</th></tr><tr><th>Date</th><th>Cost</th></tr>";
    foreach ($lastThreeEntries as $entry) {
        if ($entry['cost'] < 0) {
            $entry['cost'] = 0;
        }
        $html .= '<tr>';
        $html .= '<td>' . $entry['date'] . '</td>';
        // $html .= '<td>' . $entry['symbol'] . '</td>';

        $cost = round($entry['cost'], 2);
        getbgstring($cost);

        $html .= '<td style="width: 3.5vw; background: ' . getbgstring($cost) . ';">' . $cost . '</td>';



        $html .= '</tr>';
    }
    $html .= '</table>';

    return $html;
}

function monthpredict($symbol)
{
    // API endpoint URL
    $apiUrl = "http://localhost/portfolio/datacsv.php?symquery=" . urlencode($symbol);

    // Fetch JSON data from the API
    $jsonData = file_get_contents($apiUrl);
    $data = json_decode($jsonData, true);

    // Extract necessary information from the response
    $date = $data[0]['date'];
    $symbol = $data[0]['symbol'];

    // Extract dividend amounts
    $dividendAmounts = array();
    foreach ($data as $entry) {
        $dividendAmounts[] = floatval($entry['cost']);
    }

    // Perform linear regression
    $dividendCount = count($dividendAmounts);
    $sumX = 0;
    $sumY = 0;
    $sumXY = 0;
    $sumXX = 0;

    for ($i = 0; $i < $dividendCount; $i++) {
        $x = $i + 1;
        $y = $dividendAmounts[$i];

        $sumX += $x;
        $sumY += $y;
        $sumXY += ($x * $y);
        $sumXX += ($x * $x);
    }

    $averageX = $sumX / $dividendCount;
    $averageY = $sumY / $dividendCount;

    $slope = ($sumXY - ($dividendCount * $averageX * $averageY)) / ($sumXX - ($dividendCount * $averageX * $averageX));
    $intercept = $averageY - ($slope * $averageX);

    // Calculate forecast starting from three months after the last data point
    $lastDataDate = $data[$dividendCount - 1]['date'];
    $nextQuarter = date('Y-m', strtotime($lastDataDate . ' + 1 months'));
    $startMonth = date('Y-m', strtotime($nextQuarter . ' + 0 months'));

    // echo $lastDataDate;

    // Generate 12-quarter forecast
    $forecast = array();
    for ($i = 0; $i < 144; $i++) {
        $forecastMonth = date('Y-m', strtotime($startMonth . ' + ' . (1 * $i) . ' months'));
        $forecast[] = array(
            'date' => $forecastMonth,
            'symbol' => $symbol,
            'cost' => number_format(($intercept + ($slope * ($dividendCount + $i * 1))), 8)
        );
    }

    // Get the last three entries from the forecast
    $lastThreeEntries = array_slice($forecast, -3);

    // Output the last three entries as an HTML table
    $html = '<table class="forecast">';
    $html .= "<tr><th colspan=3>$symbol</th></tr><tr><th>Date</th><th>Cost</th></tr>";
    foreach ($lastThreeEntries as $entry) {
        if ($entry['cost'] < 0) {
            $entry['cost'] = 0;
        }
        $html .= '<tr>';
        $html .= '<td>' . $entry['date'] . '</td>';
        // $html .= '<td>' . $entry['symbol'] . '</td>';

        $cost = round($entry['cost'], 2);
        getbgstring($cost);

        $html .= '<td style="width: 3.5vw; background: ' . getbgstring($cost) . ';">' . $cost . '</td>';


        $html .= '</tr>';
    }
    $html .= '</table>';

    return $html;
}

function getbgstring($cost)
{
    if ($cost > 70) {
        $bgstring = '#006600';
    } else if ($cost > 50) {
        $bgstring = '#00ff00';
    } 
    else if ($cost > 30) {
        $bgstring = '#84ff84';
    } 
    else if ($cost > 10) {
        $bgstring = '#a4ffa4';
    }
    else if ($cost > 5) {
        $bgstring = '#cdffcd';
    }
    else if ($cost == 0) {
        $bgstring = '#1c1c1c';
    }
    else {
        $bgstring = 'transparent'; // Use default background color for other values
    }

    return $bgstring;
}

?>
</body>
</html>
