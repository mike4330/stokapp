<!-- Copyright (C) 2024 Mike Roetto <mike@roetto.org>
SPDX-License-Identifier: GPL-3.0-or-later -->
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["is_logged_in"]) || $_SESSION["is_logged_in"] !== true) {
    header("Location: /portfolio/auth/index.php");
    exit();
}

// Check session timeout (30 minutes)
if (isset($_SESSION["last_activity"]) && (time() - $_SESSION["last_activity"] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: /portfolio/auth/index.php?error=session_expired");
    exit();
}
$_SESSION["last_activity"] = time();

// Add session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
?>
<!DOCTYPE html>
<html>

<head>
  <meta http-equiv="refresh" content="301">
  <title>portfolio holdings</title>
  <link rel="stylesheet" type="text/css" href="main.css">
  <link rel="stylesheet" type="text/css" href="nav.css">
  <style>
  .loading {
    opacity: 0.7;
    pointer-events: none;
  }

  .loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 40px;
    height: 40px;
    border: 3px solid var(--neutral);
    border-top-color: var(--profit-green);
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  /* Add sort indicator styles */
  th[role="columnheader"] {
    cursor: pointer;
    position: relative;
    padding-right: 20px;
  }

  th.sort-asc::after {
    content: "▲";
    position: absolute;
    right: 5px;
    color: #08ac08;
  }

  th.sort-desc::after {
    content: "▼";
    position: absolute;
    right: 5px;
    color: #ff0000;
  }
  </style>

  <script>
  function changeFontSize(increase) {
    var tds = document.getElementsByTagName("td");

    for (var i = 0; i < tds.length; i++) {
      var currentFontSize = parseInt(window.getComputedStyle(tds[i]).fontSize);
      var newFontSize;

      if (increase) {
        newFontSize = currentFontSize + 2;
      } else {
        newFontSize = currentFontSize - 2;
      }

      tds[i].style.fontSize = newFontSize + 'px';
    }
  }
  </script>


  <script>
  function sortTable(n) {
    var table, rows, switching, i, x, y, shouldSwitch;
    table = document.getElementById("myTable2");
    switching = true;
    
    // Get current sort direction from header
    var header = table.getElementsByTagName("th")[n];
    var currentDir = header.getAttribute("data-sort-dir") || "asc";
    var newDir = currentDir === "asc" ? "desc" : "asc";
    
    // Update sort direction in header
    header.setAttribute("data-sort-dir", newDir);
    
    // Update sort indicator
    updateSortIndicators(table, n, newDir);
    
    while (switching) {
      switching = false;
      rows = table.rows;
      
      for (i = 1; i < (rows.length - 1); i++) {
        shouldSwitch = false;
        x = rows[i].getElementsByTagName("TD")[n];
        y = rows[i + 1].getElementsByTagName("TD")[n];
        
        // Handle alpha sorting for symbol column
        if (n === 0) {
          if (newDir === "asc") {
            if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
              shouldSwitch = true;
              break;
            }
          } else {
            if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
              shouldSwitch = true;
              break;
            }
          }
        } else {
          // Handle numeric sorting for other columns
          var xVal = parseFloat(x.innerHTML.replace(/[^0-9.-]+/g,"")) || 0;
          var yVal = parseFloat(y.innerHTML.replace(/[^0-9.-]+/g,"")) || 0;
          
          if (newDir === "asc") {
            if (xVal > yVal) {
              shouldSwitch = true;
              break;
            }
          } else {
            if (xVal < yVal) {
              shouldSwitch = true;
              break;
            }
          }
        }
      }
      
      if (shouldSwitch) {
        rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
        switching = true;
      }
    }
  }

  function updateSortIndicators(table, activeColumn, direction) {
    // Remove all sort indicators
    var headers = table.getElementsByTagName("th");
    for (var i = 0; i < headers.length; i++) {
      headers[i].classList.remove("sort-asc", "sort-desc");
      headers[i].setAttribute("data-sort-dir", "");
    }
    
    // Add indicator to active column
    headers[activeColumn].classList.add(direction === "asc" ? "sort-asc" : "sort-desc");
  }
  </script>

</head>

<body>


<?php include ("nav.php"); ?> 
<?php include ("functions.php"); ?> 
<?php
$dir = 'sqlite:portfolio.sqlite';
$dbh  = new PDO($dir) or die("cannot open the database");
$red1 = "#ff2222";
$red2 = "#ee0000";
$green1= "#55ff55";
$green2= "#11d311";
$green3= "#11ca11";
$green4= "#11c011";
$green5= "#11aa11";

// Color constants for return percentage backgrounds
$lightRedBg = "#ff2222";
$darkRedBg = "#ee0000";
$lightGreenBg = "#55ff55";
$mediumGreenBg = "#11d311";
$darkGreenBg1 = "#11ca11";
$darkGreenBg2 = "#11c011";
$darkGreenBg3 = "#11aa11";

$query = "SELECT DISTINCT symbol FROM prices order by symbol";

$totalPortfolioValue = get_portfolio_value();
$totalNetCost = 0;
$profitablePositionsValue = 0;
$profitSkimTotal = 0;

echo "<table class=\"holdings\" id=\"myTable2\" role=\"grid\" aria-label=\"Portfolio Holdings\">";

echo "<thead role=\"rowgroup\">
    <tr role=\"row\">
      <th role=\"columnheader\" data-sort=\"symbol\" onclick=\"sortTable(0)\">symbol</th>
      <th role=\"columnheader\" data-sort=\"units\" onclick=\"sortTable(1)\">net units</th>
      <th role=\"columnheader\" data-sort=\"price\" onclick=\"sortTable(2)\">last price</th>
      <th role=\"columnheader\" data-sort=\"indicators\">indicators</th>
      <th role=\"columnheader\" data-sort=\"value\" onclick=\"sortTable(4)\">pos. value</th>
      <th role=\"columnheader\" data-sort=\"target\" onclick=\"sortTable(5)\">tgt. diff</th>
      <th role=\"columnheader\" data-sort=\"percentage\" onclick=\"sortTable(6)\">port pct.</th>
      <th role=\"columnheader\" data-sort=\"allocation\" onclick=\"sortTable(7)\">all. tgt.</th>
      <th role=\"columnheader\" data-sort=\"cost\" onclick=\"sortTable(8)\">net cost</th>
      <th role=\"columnheader\" data-sort=\"basis\" onclick=\"sortTable(9)\">cost basis</th>
      <th role=\"columnheader\" data-sort=\"unrealized\" onclick=\"sortTable(10)\">UGL\$</th>
      <th role=\"columnheader\" data-sort=\"return\" onclick=\"sortTable(11)\">return%</th>
      <th role=\"columnheader\" data-sort=\"realized\" onclick=\"sortTable(12)\">RGL\$</th>
      <th role=\"columnheader\" data-sort=\"dividends\" onclick=\"sortTable(13)\">Divs</th>
      <th role=\"columnheader\" data-sort=\"pricechange\" onclick=\"sortTable(14)\">PriceChg</th>
      <th role=\"columnheader\" data-sort=\"valuechange\" onclick=\"sortTable(15)\">PosValChg</th>
    </tr>
</thead>";

echo "<tbody role=\"rowgroup\">";

//main tabular output
foreach ($dbh->query($query) as $row) {
    $symbol = $row['symbol'];
    
    // Get buy units
    $buyUnitsQuery = "SELECT SUM(result) AS buyUnits
    FROM (
        SELECT CASE
            WHEN units_remaining IS NULL THEN units
            ELSE units_remaining
        END AS result
        FROM transactions
        WHERE xtype = 'Buy'
        AND symbol = '$symbol'
        AND disposition IS NULL
    ) subquery;";

    $stmt = $dbh->prepare($buyUnitsQuery);
    $stmt->execute();
    $result = $stmt->fetch();
    $netUnits = $result['buyUnits'];
    
    if ($netUnits <= 0) continue;
    
    // Get current price
    $priceQuery = "select price from prices where symbol = '$symbol'";
    $stmt = $dbh->prepare($priceQuery);
    $stmt->execute();
    $result = $stmt->fetch();
    $currentPrice = round($result['price'], 4);

    $positionValue = round(($netUnits * $currentPrice), 3);
    
    // Handle silver premium
    if ($symbol == 'XAG') {
        $premiumPrice = $currentPrice + ($currentPrice * 0.2);
        $positionValue = ($netUnits * $premiumPrice);
    }
    
    // Calculate costs and returns
    $netCost = round(sym_costbasis($symbol), 2);
    $totalNetCost += $netCost;
    $dollarReturn = round(($positionValue - $netCost), 2);
    
    if ($dollarReturn > 0) {
        $profitSkimTotal += $dollarReturn;
    }
    
    $costBasis = round(($netCost / $netUnits), 2);
    $returnPercent = round(($dollarReturn / $netCost) * 100, 2);

    // Color coding logic for return percentage
    if ($returnPercent < -5) {
        $color2 = $darkRedBg;
        $tcolor2 = "black";
    } elseif ($returnPercent > -5 && $returnPercent < 0) {
        $color2 = $lightRedBg;
        $tcolor2 = "";
    } elseif ($returnPercent > 0 && $returnPercent < 5) {
        $color2 = $lightGreenBg;
        $tcolor2 = "black";
    } elseif ($returnPercent > 5 && $returnPercent < 10) {
        $color2 = $mediumGreenBg;
        $tcolor2 = "";
    } elseif ($returnPercent > 10 && $returnPercent < 15) {
        $color2 = $darkGreenBg1;
        $tcolor2 = "";
    } elseif ($returnPercent > 15 && $returnPercent < 20) {
        $color2 = $darkGreenBg2;
        $tcolor2 = "";
    } elseif ($returnPercent > 20) {
        $color2 = $darkGreenBg3;
        $tcolor2 = "";
    } else {
        $color2 = "";
        $tcolor2 = "";
    }

    // Color coding logic
    if ($returnPercent > 0) {
        $profitablePositionsValue += $positionValue;
    }

    $total = ($total + $positionValue);
    
    $subquery="SELECT sum(units*price) AS buytotal FROM transactions WHERE xtype = 'Buy' AND symbol = '$symbol'";
    $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $buytotal = round($zrow['buytotal'],3);
    
    $subquery="SELECT sum(units*price) AS selltotal FROM transactions WHERE xtype = 'Sell' AND symbol = '$symbol'";
    $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $selltotal = round($zrow['selltotal'],3);
    
    $subquery="SELECT sum(gain) as rgain FROM transactions WHERE xtype = 'Sell' AND symbol = '$symbol'";
    $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $gain = round($zrow['rgain'],2);
    
    $subquery="SELECT sum(units*price) as total_dividends FROM transactions WHERE xtype = 'Div' AND symbol = '$symbol'";
    $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $total_dividends = round($zrow['total_dividends'],2);
                
    $netcost = round(($buytotal - $selltotal),2)+$gain;

    $netcost = round(sym_costbasis($symbol),2);

    $tnetcost = ($netcost + $tnetcost);
    
    if ($dollarReturn > 0) {$freecash=($freecash+$dollarReturn);}
    
    $portfolioPercent = round((($positionValue / $totalPortfolioValue) * 100), 2);
    $targetQuery = "SELECT alloc_target, class as securityClass, mean50, mean200 
                   FROM prices 
                   WHERE symbol = :symbol";
    $stmt = $dbh->prepare($targetQuery);
    $stmt->bindParam(':symbol', $symbol);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $allocationTarget = $result['alloc_target'];
    $securityClass = $result['securityClass'];
    $mean50 = floatval($result['mean50']);
    $mean200 = floatval($result['mean200']);
    
    $targetValue = ($totalPortfolioValue * ($allocationTarget / 100));
    $targetDiff = round(($positionValue - $targetValue), 2);
    
    // Initialize target difference color variables
    if ($targetDiff < 0) {
        $tcellcolor = "#2222ee";
        $targetcolor = "black";
    } else {
        $tcellcolor = "";
        $targetcolor = "";
    }
    
    // Get previous close and calculate changes
    $prevCloseQuery = "SELECT close from security_values where symbol = '$symbol' order by timestamp desc limit 1";
    $stmt = $dbh->prepare($prevCloseQuery);
    $stmt->execute();
    $result = $stmt->fetch();
    $previousClose = $result['close'];
    
    $priceChange = round(($currentPrice - $previousClose), 2);
    if ($symbol == "ETHUSD" || $symbol == "BTCUSD" || $symbol == "XAG") {
        $priceChange = 0;
    }
    $positionDayChange = round(($priceChange * $netUnits), 2);

    // Update return percentage in database
    $updateReturnQuery = "UPDATE returns set returnpct = $returnPercent where symbol = '$symbol'";
    $stmt = $dbh->prepare($updateReturnQuery);
    $stmt->execute();

    // Output row
    echo "<tr role=\"row\" class=\"main\">
        <td><a href=\"/portfolio/?symfilter=$symbol\" class=\"holdinglist\">$symbol</a></td>
        <td class=\"cntr\">$netUnits</td>
        <td class=\"cntr\">$currentPrice</td>
        <td>";
    
    // Output technical indicators
    if ($currentPrice < $costBasis) {
        echo '<span class="icon">CB</span>';
    }
    
    // 50-day moving average indicator
    if ($mean50 > 0) {  // Only show if we have valid data
        if ($currentPrice < $mean50) {
            echo '<span class="icon" style="color: #ff0000; margin-left: 4px;">▼50</span>';
        } else {
            echo '<span class="icon" style="color: #08ac08; margin-left: 4px;">▲50</span>';
        }
    }
    
    // 200-day moving average indicator
    if ($mean200 > 0) {  // Only show if we have valid data
        if ($currentPrice < $mean200) {
            echo '<span class="icon" style="color: #ff0000; margin-left: 4px;">▼200</span>';
        } else {
            echo '<span class="icon" style="color: #08ac08; margin-left: 4px;">▲200</span>';
        }
    }
    
    echo "</td>
        <td class=\"cntr\">$positionValue</td>
        <td class=\"cntr\" style=\"background: $tcellcolor;color: $targetcolor\">$targetDiff</td>
        <td class=\"cntr\">$portfolioPercent</td>
        <td class=\"cntr\">$allocationTarget</td>
        <td class=\"cntr\">\$$netCost</td>
        <td class=\"cntr\">$costBasis</td>
        <td class=\"cntr\">$dollarReturn</td>
        <td class=\"cntr\" style=\"background: $color2;color: $tcolor2;\">$returnPercent%</td>
        <td>$gain</td>
        <td class=\"cntr\">$total_dividends</td>
        <td class=\"cntr\">$priceChange</td>
        <td class=\"cntr\">$positionDayChange</td>
    </tr>\n";
    
    
}

echo "</tbody></table>";

$tdollarrtn=round(($totalPortfolioValue - $tnetcost),2);
$trtnpct=round(($tdollarrtn / $tnetcost)*100,2);

// Format the values for display
$totalPortfolioValue_fmt = number_format($totalPortfolioValue, 2);
$profitablePositionsValue_fmt = number_format($profitablePositionsValue, 2);

// Get the day change
$subquery = "select value from historical order by date desc limit 1";
$stmt = $dbh->prepare($subquery);
$stmt->execute();
$zrow = $stmt->fetch(); 
$prevvalue = $zrow['value'];
$daychange = round(($totalPortfolioValue - $prevvalue), 2);

echo "<div class=\"statusmessage\">
    <div class=\"status-item\">
        <span class=\"status-label\">Portfolio Value:</span>
        <span class=\"status-value\">\$$totalPortfolioValue_fmt</span>
        <span class=\"status-value " . ($daychange >= 0 ? "positive" : "negative") . "\">($daychange)</span>
    </div>
    <div class=\"status-item\">
        <span class=\"status-label\">Dollar Return:</span>
        <span class=\"status-value " . ($tdollarrtn >= 0 ? "positive" : "negative") . "\">\$$tdollarrtn</span>
    </div>
    <div class=\"status-item\">
        <span class=\"status-label\">Percent Return:</span>
        <span class=\"status-value " . ($trtnpct >= 0 ? "positive" : "negative") . "\">$trtnpct%</span>
    </div>
    <div class=\"status-item\">
        <span class=\"status-label\">Profit Skim:</span>
        <span class=\"status-value " . ($freecash >= 0 ? "positive" : "negative") . "\">\$$freecash</span>
    </div>
    <div class=\"status-item\">
        <span class=\"status-label\">Profitable Positions:</span>
        <span class=\"status-value positive\">\$$profitablePositionsValue_fmt</span>
    </div>
</div>";

function get_portfolio_value()
{
    chdir('/var/www/html/portfolio');
    $dir = 'sqlite:portfolio.sqlite';
    $total_value = 0;
    $curdate = date("Y-m-d");

    $dbh = new PDO($dir) or die("cannot open the database");

    $query = "SELECT DISTINCT symbol FROM transactions where symbol <> 'SNP' order by symbol";
    foreach ($dbh->query($query) as $row) {
        $sym = $row['symbol'];

        $nuquery = "SELECT SUM(result) AS buyunits
        FROM (
        SELECT CASE
            WHEN units_remaining IS NULL THEN units
            ELSE units_remaining
        END AS result
        FROM transactions
        WHERE xtype = 'Buy'
        AND symbol = '$sym'
        AND disposition IS NULL
        ) subquery;";

        $stmt = $dbh->prepare($nuquery);
        $stmt->execute();
        $zrow = $stmt->fetch();
        $netunits = $zrow['buyunits'];

        // $netunits = ($buyunits - $sellunits);
        if ($netunits <= 0)
            continue;

        $subquery = "select price from prices where symbol = '$sym'";
        $stmt = $dbh->prepare($subquery);
        $stmt->execute();
        $zrow = $stmt->fetch();
        $cprice = round($zrow['price'], 4);

        $position_value = ($netunits * $cprice);

        $subquery = "SELECT sum(units*price) AS buytotal FROM transactions WHERE xtype = 'Buy' AND symbol = '$sym'";
        $stmt = $dbh->prepare($subquery);
        $stmt->execute();
        $zrow = $stmt->fetch();
        $buycost = round($zrow['buytotal'], 3);

        // echo "$sym,NU $netunits,CP $cprice,PV $position_value,CB $netcost,UGL $unrealized_pl,RGL $realized_gain,DIVS $total_dividends\n";

        $total_value = round(($total_value + $position_value), 2);

    }
    return $total_value;
}

?>


<div class="font-size-controls">
  <button onclick="changeFontSize(true)">Aa &#8593;</button>
  <button onclick="changeFontSize(false)">Aa &#8595;</button>
</div>






</body>
</html>
