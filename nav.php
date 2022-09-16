    <!--Copyright (C) 2022 Mike Roetto <mike@roetto.org>
    SPDX-License-Identifier: GPL-3.0-or-later-->

<?php

$dir = 'sqlite:portfolio.sqlite';





// echo "<div class=nav>";
// echo "<a href=\"/portfolio\" class=\"button2\"><span class=\"navicon\"> 📒 </span>Transactions</a>\n";
// echo "<a href=\"/portfolio/holdings.php\" class=\"button2\"><span  class=\"navicon\"> 💎 </span>Holdings</a>\n";
// echo "<a href=\"/portfolio/div.php\" class=\"button2\"><span  class=\"navicon\"> 🌋 </span>Value Trends</a>\n";
// echo "<a href=\"/portfolio/chart3.php\" class=\"button2\"><span  class=\"navicon\">♒ </span>Pos. Size Charts</a>\n";
// echo "<a href=\"/portfolio/posvalues.php\" class=\"button2\"><span  class=\"navicon\">🫐 </span>Pos. Value Charts</a>\n";
// echo "<a href=\"/portfolio/gainloss.php\" class=\"button2\"><span  class=\"navicon\">🥧</span>Gain/Loss Chart</a>\n";
// echo "<a href=\"/portfolio/alloc.php\" class=\"button2\"><span  class=\"navicon\"> 🔱</span>Allocations</a>";
// echo "<a href=\"/portfolio/divcharts.php\" class=\"button2\"><span  class=\"navicon\"> ☕ </span>Div. Charts</a>";
// echo "<a href=\"/portfolio/mpt.php\" class=\"button2\"><span  class=\"navicon\"> 🌊 </span>MPT</a>";

// echo "</div>";

echo ' <div class="navbar">
  <a href="/portfolio/">Transactions</a>
  <a href="holdings.php">Holdings</a>
  <a href="alloc.php">Allocations</a>
  <a href="lots.php">Lots</a>
  <a href="mpt.php">Modelling</a>
  <div class="dropdown">
    <button class="dropbtn">Charts
      <i class="fa fa-caret-down"></i>
    </button>
    <div class="dropdown-content">
      <a href="div.php">Value Trends</a>
      <a href="divcharts.php">Dividend Charts</a>
      <a href="chart3.php">Pos. Size Charts</a>
      <a href="posvalues.php">Pos. Value Charts</a>
    </div>
  </div>
</div> ';

function gpv() {
//     echo "executing function<br>";
    $dir = 'sqlite:portfolio.sqlite';
    $dbh  = new PDO($dir) or die("cannot open the database");
    $q = "SELECT DISTINCT symbol FROM transactions order by symbol";
    foreach ($dbh->query($q) as $trow) {
        $tsym=$trow['symbol'];
        $subquery = "select sum(units) as buyunits from transactions where xtype = 'Buy' and symbol = '$tsym'";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $buyunits = $zrow['buyunits'];
        $subquery = "select sum(units) as sellunits from transactions where xtype = 'Sell' and symbol = '$tsym'";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $sellunits = $zrow['sellunits'];
        $netunits = ($buyunits-$sellunits);
        $subquery = "select price from prices where symbol = '$tsym'";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $cprice = $zrow['price'];
        
        if ($netunits == 0) continue;
        
        $value = round(($netunits * $cprice),3);
        $ttotal = ($ttotal + $value);
//      echo "total $ttotal <br>";
    }
return $ttotal;
}



?>
