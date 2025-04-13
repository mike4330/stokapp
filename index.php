<!-- Copyright (C) 2022,2024 Mike Roetto <mike@roetto.org>
 SPDX-License-Identifier: GPL-3.0-or-later-->

<!DOCTYPE html>
<html lang="en">

<head>
    <title>portfolio</title>
    <!-- External CSS files -->
    <link rel="stylesheet" type="text/css" href="main.css">
    <link rel="stylesheet" type="text/css" href="nav.css">
    <link rel="stylesheet" type="text/css" href="css/chart-styles.css">
    
    <!-- External JavaScript libraries -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.1.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@^1"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.1.0/dist/chartjs-plugin-annotation.min.js"></script>
    <script src="winbox.bundle.min.js"></script>
    
    <!-- Application-specific JavaScript files -->
    <script src="js/chart-config.js"></script>
    <script src="/js/portfolio-charts.js"></script>
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
     * 6. Transaction Type Toggles (Buy/Sell/Div)
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
     * - Buy: Green (#11cc11)
     * - Dividend: Yellow (#eefb0b)
     * - Sell: Red (#dd3333)
     */
    $c = [
        'Buy' => "#11cc11",  // Green for buy transactions
        'Div' => "#eefb0b",  // Yellow for dividend transactions
        'Sell' => "#dd3333"  // Red for sell transactions
    ];

    // Removed legacy symbol color mapping code
    // $query = "SELECT symbol FROM prices";
    // foreach ($dbh->query($query) as $row) {
    //     $symbol = $row['symbol'];
    //     $sc[$symbol] = $colors[$x];
    //     $tc[$symbol] = $contrasting_colors[$x];
    //     $x++;
    // }

    // Removed special color cases
    // $sc['AMX'] = "#f0f000"; $tc['AMX'] = "black";
    // $sc['INGR'] = "#007000"; $tc['INGR'] = "white";
    
    /**
     * Transaction Query Logic
     * Default: Shows last 2000 transactions ordered by date and ID
     * Filtered: Shows all transactions for a specific symbol when symfilter is set
     */
    $query = "SELECT *,(price*units) as ccost from transactions order by date_new desc,id desc limit 2000";

    if (!empty($_GET['symfilter'])):
        $filterterm = $_GET['symfilter'];
        $query = "SELECT *,(price*units) as ccost from transactions where symbol = '$filterterm' order by id desc";
        
        // Display filter reset button and chart containers
        echo '<a href="/portfolio" class="button1">🗑 reset filter</a><br>';
        echo '<div class="minichart"><canvas id="chart"></canvas></div>';
        echo '<div class="returnchart"><canvas id="returnchart"></canvas></div>';
        echo '<div class="yieldchart"><canvas id="yieldchart"></canvas></div>';
        echo '<div class="cbpschart"><canvas id="cbpschart"></canvas></div>';

        // Display symbol navigation table
        echo '<table class=smallnav>';
        $subquery = "select symbol from prices where class IS NOT NULL order by symbol";
        $i = 0;
        foreach ($dbh->query($subquery) as $row) {
            if ($i % 4 == 0) {
                echo "<tr>";
            }
            $symbol = $row['symbol'];
            echo "<td><a href=\"/portfolio/?symfilter=$symbol\" class=\"buttonsmall\">$symbol</a></td>";
            if ($i % 4 == 3) {
                echo "</tr>";
            }
            $i++;
        }
        // Close the last row if needed
        if ($i % 4 != 0) {
            echo "</tr>";
        }
        echo "</table>";

        $tclass = "moved";
    endif;

    /**
     * Transaction Entry Form
     * Creates a form for adding new transactions with the following fields:
     * - Account selection
     * - Date
     * - Symbol
     * - Action type (Buy/Sell/Div)
     * - Units
     * - Price
     * - Fee
     */
    $curdate = date("Y-m-d");
    echo "<div class=\"transaction-container\">";
    echo "<form action=\"index.php\" method=\"POST\">";
    echo "<div class=\"form-row form-header\">";
    echo "<div>account</div>";
    echo "<div>date</div>";
    echo "<div>symbol</div>";
    echo "<div>action</div>";
    echo "<div>units</div>";
    echo "<div>price</div>";
    echo "<div>fee</div>";
    echo "<div></div>";  // For submit button
    echo "</div>";
    
    echo "<div class=\"form-row\">";
    echo "<div><select name=\"acct\">
            <option value=\"CB\">CB</option>
            <option value=\"FIDRI\" selected>FIDRI</option>
            <option value=\"FID\">FID</option>
            <option value=\"ET\">CB</option>
            <option value=\"IB\">IB</option>
            <option value=\"TT\">TT</option>
          </select></div>";
    echo "<div><input type=\"date\" name=\"date\" value=\"$curdate\"></div>";
    echo "<div><input type=\"text\" name=\"symbol\"></div>";
    echo "<div><select name=\"type\">
            <option value=\"Buy\">Buy</option>
            <option value=\"Sell\">Sell</option>
            <option value=\"Div\">Div</option>
          </select></div>";
    echo "<div><input type=\"text\" name=\"units\"></div>";
    echo "<div><input type=\"text\" name=\"price\"></div>";
    echo "<div><input type=\"text\" name=\"fee\"></div>";
    echo "<div><input type=\"hidden\" name=\"app_action\" value=\"new\"><input type=\"submit\" value=\"Add\"></div>";
    echo "</div>";
    echo "</form>";
    echo "</div>";

    /**
     * Process New Transaction Submission
     * Handles the form submission and inserts new transactions into the database
     */
    if (!empty($_POST)):
        $date = $_POST["date"];
        $acct = $_POST["acct"];
        $type = $_POST["type"];
        $units = $_POST["units"];
        $price = $_POST["price"];
        $symbol = $_POST["symbol"];
    
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

    echo '<div class="toggles">';
    echo '<span class="toggle" onClick="$(\'.Buy\').toggle();">Buy</span>';
    echo '<span class="toggle" onClick="$(\'.Sell\').toggle();">Sell</span>';
    echo '<span class="toggle" onClick="$(\'.Div\').toggle();">Div</span>';
    echo '</div>';
    ?>

    <?php
    /**
     * Transaction Table Display
     * Creates a table to display transaction history with the following columns:
     * - ID
     * - Account
     * - Date
     * - Type
     * - Symbol
     * - Price
     * - Units
     * - Cost
     */
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

    // Output table headers
    echo "<table class='transactions'>";
    echo "<tr>";

    foreach ($headers as $header) {
        echo "<th>$header</th>";
    }

    echo "</tr>";

    // Output transaction rows with appropriate styling
    foreach ($dbh->query($query) as $row) {
        $x = $row['xtype'];
        $g = $c[$x];
        $sym = $row['symbol'];
        $cost = round($row['ccost'], 4);
        $units = round($row['units'], 3);

        // Output table row with transaction data
        echo "<tr class='$x'>";
        echo "<td style='padding-right: 2vw;'>{$row['id']}</td>";
        echo "<td>{$row['acct']}</td>";
        echo "<td>{$row['date_new']}</td>";
        echo "<td class='cntr' style='background: $g; color:#000000;'>$row[xtype]</td>";
        echo "<td class='cntr' style='text-align: center;'>";
        echo "<a href='?symfilter={$row['symbol']}'>{$row['symbol']}</a>";
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
    </script>
    <?php endif; ?>

</body>

</html>
