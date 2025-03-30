<!-- Copyright (C) 2022,2024 Mike Roetto <mike@roetto.org>
 SPDX-License-Identifier: GPL-3.0-or-later-->

<!DOCTYPE html>
<html lang="en">

<head>
    <title>portfolio</title>
    <link rel="stylesheet" type="text/css" href="main.css">
    <link rel="stylesheet" type="text/css" href="nav.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.1.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@^1"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.1.0/dist/chartjs-plugin-annotation.min.js"></script>
    <script src="winbox.bundle.min.js"></script>
    <script src="js/portfolio-charts.js"></script>
</head>

<body>
    <?php
    include_once "nav.php";

    /**
     * Portfolio Transaction Management System
     * 
     * This file serves as the main interface for managing stock portfolio transactions.
     * It provides functionality for viewing, filtering, and adding new transactions.
     * 
     * Key Features:
     * 1. Transaction History Display
     * 2. Transaction Entry Form
     * 3. Symbol-based Filtering
     * 4. Interactive Charts
     * 5. Position Summary
     * 
     * Database Structure:
     * - Uses SQLite database (portfolio.sqlite)
     * - Main tables: transactions, prices, MPT
     * 
     * Dependencies:
     * - jQuery 3.1.1
     * - Chart.js
     * - Luxon.js
     * - ChartJS Adapter Luxon
     * - ChartJS Plugin Annotation
     * - WinBox.js
     */

    // Database Connection
    try {
        $dir = 'sqlite:portfolio.sqlite';
        $dbh = new PDO($dir);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }

    /**
     * Color Scheme Configuration
     * Defines colors for different transaction types
     */
    $c = [
        'Buy' => "#11cc11",  // Green for buy transactions
        'Div' => "#eefb0b",  // Yellow for dividend transactions
        'Sell' => "#dd3333"  // Red for sell transactions
    ];

    /**
     * Symbol Color Mapping
     * Assigns unique colors to each symbol for visual distinction
     */
    $query = "SELECT symbol FROM prices";
    foreach ($dbh->query($query) as $row) {
        $symbol = $row['symbol'];
        $sc[$symbol] = $colors[$x];
        $tc[$symbol] = $contrasting_colors[$x];
        $x++;
    }

    // Special color cases
    $sc['AMX'] = "#f0f000"; $tc['AMX'] = "black";
    $sc['INGR'] = "#007000"; $tc['INGR'] = "white";

    //echo "db ok<br>";
    
    /**
     * Transaction Query Logic
     * Default: Shows last 2000 transactions
     * Filtered: Shows all transactions for a specific symbol
     */
    $query = "SELECT *,(price*units) as ccost from transactions order by date_new desc,id desc limit 2000";

    if (!empty($_GET['symfilter'])):
        $filterterm = $_GET['symfilter'];
        // echo "title filter $filterterm<br>"; 
        $query = "SELECT *,(price*units) as ccost from transactions where symbol = '$filterterm' order by id desc";
        echo '<a href="/portfolio" class="button1">🗑 reset filter</a><br>';
        echo '<div class="minichart" ><canvas id="chart" ></canvas></div>';
        echo '<div id="returnContainer" style="width: 100%;display: hidden;">
            <canvas id="returnchart"></canvas>
        </div>
        <div id="yieldContainer" style="width: 100%;display: hidden;">
            <canvas id="yieldchart"></canvas>
        </div>'
        ;


        echo '<table class=smallnav>';
        $subquery = "select symbol from prices where class IS NOT NULL order by symbol";
        echo "<tr>";
        foreach ($dbh->query($subquery) as $row) {
            $symbol = $row['symbol'];
            $i++;

            echo "<td><a href=\"/portfolio/?symfilter=$symbol\" class=\"buttonsmall\">$symbol</a></td>";
            if ($i > 4) {
                echo "</tr>";
                $i = 0;
            }
        }
        echo "</table>";

        //        echo "total holdings for $filterterm<br>";
        $tclass = "moved";
    endif;

    //data entry
    $curdate = date("Y-m-d");
    echo "<form action=\"index.php\" method=\"POST\">";
    echo "<table >";
    echo "<tr><th>account</th>
<th>date</th>
<th>symbol</th>
<th>action</th>
<th>units</th>
<th>price</th>
<th>fee</th>
</tr>";

    echo "<tr>
<td><select name=\"acct\">
<option value=\"CB\">CB</option>
<option value=\"FIDRI\" selected >FIDRI</option>
<option value=\"FID\" >FID</option>
<option value=\"ET\">CB</option>
<option value=\"IB\">IB</option>
<option value=\"TT\">TT</option>

</select>
<td><input type=date size=8 id=\"date\" name=\"date\" value=$curdate></td>
<td><input type=text size=6 name=\"symbol\"></td>

<td><select name=\"type\">
    <option value=\"Buy\">Buy</option>
    <option value=\"Sell\">Sell</option>
    <option value=\"Div\">Div</option>
    </select>
</td>
<td><input type=text id=\"units\" name=\"units\" size=10>
<td><input type=text id=\"price\" name=\"price\" size=10>
<td><input type=text id=\"fee\" name=\"fee\" size=4>
<input type='hidden' name='app_action' value='new'>
<td><input type=submit></td>
</tr>";
    echo "</table>";
    echo "</form>";

    //input processing
    
    if (!empty($_POST)):
        $date = $_POST["date"];
        $acct = $_POST["acct"];
        $type = $_POST["type"];
        $units = $_POST["units"];
        $price = $_POST["price"];
        $symbol = $_POST["symbol"];
        // echo "post triggered<br>";
    
        // echo "<pre>"; print_r($_POST) ;  echo "</pre>";
        $q = "INSERT into transactions (date_new,acct,xtype,units,price,symbol) VALUES ('$date','$acct','$type','$units','$price','$symbol')";
        echo "query is $q<br>";
        try {
            $DBH = new PDO($dir);
            $DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $STH = $DBH->prepare($q);
            $STH->execute();
        } catch (PDOException $e) {
            $err = $e->getMessage();
            echo "$query $err I'm sorry, Dave. I'm afraid I can't do that.";
            file_put_contents('/tmp/PDOErrors.txt', $e->getMessage(), FILE_APPEND);
        }
    endif;
    ?>

    <div class="toggles">

        <span class="toggle" onClick="$('.Buy').toggle();">Buy</span>
        <span class="toggle" onClick="$('.Sell').toggle();">Sell</span>
        <span class="toggle" onClick="$('.Div').toggle();">Div</span>
    </div>



    <?php
    // create an array to store the table headers
    $headers = array(
        "id",
        "acct.",
        "date",
        "type",
        "symbol",
        "price",
        "units",
        "cost"
    );

    // output table headers using a foreach loop
    echo "<table class='transactions'>";
    echo "<tr>";

    foreach ($headers as $header) {
        echo "<th>$header</th>";
    }

    echo "</tr>";

    // loop through query results and output corresponding data in table format
    foreach ($dbh->query($query) as $row) {
        $x = $row['xtype'];
        $g = $c[$x];
        $sym = $row['symbol'];
        $symbolcolor = $sc[$sym];
        $textcolor = $tc[$sym];
        $cost = round($row['ccost'], 4);
        $units = round($row['units'], 3);

        // output table row
        echo "<tr class='$x'>";
        echo "<td style='padding-right: 2vw;'>{$row['id']}</td>";
        echo "<td>{$row['acct']}</td>";
        echo "<td>{$row['date_new']}</td>";
        echo "<td class='cntr' style='background: $g; color:#000000;'>$row[xtype]</td>";
        echo "<td class='cntr' style='text-align: center;background: $symbolcolor;'>";
        echo "<a href='?symfilter={$row['symbol']}' style='color: $textcolor;'>{$row['symbol']}</a>";
        echo "</td>";
        echo "<td>{$row['price']}</td>";
        echo "<td>$units</td>";
        echo "<td style='padding-left: 2vw;'>\${$cost}</td>";
        echo "</tr>";
    }

    echo "</table>";


    if (!empty($_GET['symfilter'])):
        $sym = $_GET['symfilter'];
        $query = "select sum(units) as buyunits from transactions where symbol = '$sym' and xtype = 'Buy'";
        $stmt = $dbh->prepare($query);
        $stmt->execute();
        $zrow = $stmt->fetch();
        $buyunits = $zrow['buyunits'];
        $query = "select sum(units) as sellunits from transactions where symbol = '$sym' and xtype = 'Sell'";
        $stmt = $dbh->prepare($query);
        $stmt->execute();
        $zrow = $stmt->fetch();
        $sellunits = $zrow['sellunits'];
        $netunits = ($buyunits - $sellunits);
        $query = "select price from prices where symbol = '$sym'";
        $stmt = $dbh->prepare($query);
        $stmt->execute();
        $zrow = $stmt->fetch();
        $price = round($zrow['price'], 2);
        $value = round(($price * $netunits), 2);

        $query = "select * from MPT where symbol = '$sym'";
        $stmt = $dbh->prepare($query);
        $stmt->execute();
        $zrow = $stmt->fetch();
        $overamt = $zrow['overamt'];
        $target = round($zrow['target_alloc'], 4);

        $pv = gpv();

        $poswgt = round(($value / $pv), 4);


        echo "<table class=status2>
        <tr><td>bought</td><td colspan=2> $buyunits</td></tr>
        <tr> <td>sold</td><td colspan=2> $sellunits</td></tr>";
        echo "<tr><td>net units</td><td colspan=2> $netunits</td></tr>";
        echo "<tr><td>current price</td><td colspan=2> \$$price</td></tr>";
        echo "<tr><td>$sym pos. value</td><td colspan=2> \$$value</td></tr>
        <tr><td>wgt. diff</td><td colspan=2>\$$overamt </td></tr>
        <tr><td>tgt/curr wgt.</td><td>$target</td><td>$poswgt</td></tr>
        </table>";
    endif;

    ?>
    <?php if (!empty($_GET['symfilter'])): ?>
    <script>
        const symbol = "<?php echo htmlspecialchars($_GET['symfilter']); ?>";
        initializeCharts(symbol);
        initializeWinBoxes(symbol);
    </script>
    <?php endif; ?>

</body>

</html>
