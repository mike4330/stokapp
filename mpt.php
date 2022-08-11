<!--Copyright (C) 2022 Mike Roetto <mike@roetto.org>
SPDX-License-Identifier: GPL-3.0-or-later-->
<!DOCTYPE html>

<html>
<head>
<link rel="stylesheet" type="text/css" href="main.css">
<link rel="stylesheet" type="text/css" href="nav.css">
<title>MPT Modeling</title>
<script src="/js/jquery-3.1.1.min.js"></script>
<script type="text/javascript" src="/js/chart.js"></script> 
<script>
function numericsort(n) {
  var table, rows, switching, i, x, y, shouldSwitch;
  table = document.getElementById("myTable2");
  switching = true;
  /*Make a loop that will continue until
  no switching has been done:*/
  while (switching) {
    //start by saying: no switching is done:
    switching = false;
    rows = table.rows;
    /*Loop through all table rows (except the
    first, which contains table headers):*/
    for (i = 1; i < (rows.length - 1); i++) {
      //start by saying there should be no switching:
      shouldSwitch = false;
      /*Get the two elements you want to compare,
      one from current row and one from the next:*/
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      //check if the two rows should switch place:
      if (Number(x.innerHTML) > Number(y.innerHTML)) {
        //if so, mark as a switch and break the loop:
        shouldSwitch = true;
        break;
      }
    }
    if (shouldSwitch) {
      /*If a switch has been marked, make the switch
      and mark that a switch has been done:*/
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
    }
  }
}
function sortTable(n) {
  var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
  table = document.getElementById("myTable2");
  switching = true;
  // Set the sorting direction to ascending:
  dir = "asc";
  /* Make a loop that will continue until
  no switching has been done: */
  while (switching) {
    // Start by saying: no switching is done:
    switching = false;
    rows = table.rows;
    /* Loop through all table rows (except the
    first, which contains table headers): */
    for (i = 1; i < (rows.length - 1); i++) {
      // Start by saying there should be no switching:
      shouldSwitch = false;
      /* Get the two elements you want to compare,
      one from current row and one from the next: */
      x = rows[i].getElementsByTagName("TD")[n];
      y = rows[i + 1].getElementsByTagName("TD")[n];
      /* Check if the two rows should switch place,
      based on the direction, asc or desc: */
      if (dir == "asc") {
        if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
          // If so, mark as a switch and break the loop:
          shouldSwitch = true;
          break;
        }
      } else if (dir == "desc") {
        if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
          // If so, mark as a switch and break the loop:
          shouldSwitch = true;
          break;
        }
      }
    }
    if (shouldSwitch) {
      /* If a switch has been marked, make the switch
      and mark that a switch has been done: */
      rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
      switching = true;
      // Each time a switch is done, increase this count by 1:
      switchcount ++;
    } else {
      /* If no switching has been done AND the direction is "asc",
      set the direction to "desc" and run the while loop again. */
      if (switchcount == 0 && dir == "asc") {
        dir = "desc";
        switching = true;
      }
    }
  }
}
</script>
</head>
<body> 

<?php

$dir = 'sqlite:portfolio.sqlite';
include ("nav.php"); 
$dbh  = new PDO($dir) or die("cannot open the database");

$portfolio_value=get_portfolio_value();

echo "portfolio_value = $portfolio_value";
$query = "SELECT DISTINCT symbol,target_alloc,sector,logo_file FROM MPT order by symbol";

echo "<table class=\"mpt\" id=\"myTable2\">";
echo "
<tr><th>Symbol</th><th>T.Wgt</th>
<th>pos_val</th><th>Target Value</th><th onclick=\"numericsort(4)\">Target Diff</th><th onclick=\"numericsort(6)\">IDX</th><th>class</th><th onclick=\"sortTable(7)\">Sector</th></tr>";

foreach ($dbh->query($query) as $row) {
    $sym=$row['symbol'];
    $sector=$row['sector'];
    $logo_file=$row['logo_file'];
    $subquery = "select close*shares as pos_val from security_values 
    where symbol = '$sym' 
    order by timestamp desc limit 1";
    $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); 
    $pos_val = round($zrow['pos_val'],2);
    
    $subquery = "select asset_class,compidx2 from prices where symbol = '$sym'";
    $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch();
    $current_class = $zrow['asset_class']; $compidx2=round($zrow['compidx2'],1);
    
    $talloc=round($row['target_alloc'],4);
    $talloc_display=round(($row['target_alloc']*100),2);
    $tvalue = round($talloc*$portfolio_value,2);
    
    $tdiff= ($pos_val-$tvalue);
    
    if ($tdiff < -1) {
		$color2="#cc0000";$tcolor2="white";
		$subquery = "update MPT set flag = 'U' where symbol = '$sym'"; 
		$stmt = $dbh->prepare($subquery);$stmt->execute();
		} 
    elseif ($returnpct > -5 && $returnpct < 0) {$color2="$red1";}
    else {$color2 = "";$tcolor2="";}

    if ($tdiff > -1 && $tdiff < 0) {
		$subquery = "update MPT set flag = 'H' where symbol = '$sym'"; 
		$stmt = $dbh->prepare($subquery);$stmt->execute();
	
	}

    if ($tdiff > -1) {
    	$subquery = "update MPT set flag = 'O' where symbol = '$sym'";
	$stmt = $dbh->prepare($subquery);$stmt->execute();
	}
    
    $class_total[$current_class]= $class_total[$current_class]+$pos_val;
    $class_proposed_total[$current_class]=$class_proposed_total[$current_class]+$tvalue;
    
    $diffamt[$sym]=$tdiff;
    
    echo "<tr class=\"main\">
    
    <td>$sym</td><td>$talloc_display %</td>
    <td>\$$pos_val</td><td>\$$tvalue</td>
    <td style=\"background: $color2;color: $tcolor2\">$tdiff</td>
    <td>$compidx2</td><td>$current_class</td><td>$sector</td></tr>";
}

$cmp=$class_proposed_total['Commodities'];
$cmc=$class_total['Commodities'];
$cmpct_p=round(($cmp/$portfolio_value*100),2);
$cmpct_c=round(($cmc/$portfolio_value*100),2);

$dsp=$class_proposed_total['Domestic Stock'];
$dsc=$class_total['Domestic Stock'];
$dspct_p=round(($dsp/$portfolio_value*100),2);
$dspct_c=round(($dsc/$portfolio_value*100),2);

$fsp=$class_proposed_total['Foreign Stock'];
$fsc=$class_total['Foreign Stock'];
$fspct_p=round(($fsp/$portfolio_value*100),2);
$fspct_c=round(($fsc/$portfolio_value*100),2);

$dbp=$class_proposed_total['Domestic Bonds'];
$dbc=$class_total['Domestic Bonds'];
$dbpct_p=round(($dbp/$portfolio_value*100),2);
$dbpct_c=round(($dbc/$portfolio_value*100),2);

$fbp=$class_proposed_total['Foreign Bonds'];
$fbc=$class_total['Foreign Bonds'];
$fbpct_p=round(($fbp/$portfolio_value*100),2);
$fbpct_c=round(($fbc/$portfolio_value*100),2);

echo "<table class=\"mpt2\">";
echo "<th></th><th>Current</th><th>Proposed</th><th>Cur. Alloc</th><th>Prop. Alloc</th>";
echo "<tr><td>Commodities</td><td>$class_total[Commodities]</td><td>$class_proposed_total[Commodities]</td><td>$cmpct_c</td><td>$cmpct_p</td></tr>";
echo "<tr><td>Domestic Stock</td><td>$dsc</td><td>$dsp</td><td>$dspct_c</td><td>$dspct_p</td></tr>";
echo "<tr><td>Foreign Stock</td><td>$fsc</td><td>$fsp</td><td>$fspct_c</td><td>$fspct_p</td></tr>";
echo "<tr><td>Domestic Bonds</td><td>$dbc</td><td>$dbp</td><td>$dbpct_c</td><td>$dbpct_p</td></tr>";
echo "<tr><td>Foreign Bonds</td><td>$fbc</td><td>$fbp</td><td>$fbpct_c</td><td>$fbpct_p</td></tr>";
$totalbonds=($dbpct_p+$fbpct_p);
echo "<tr><td>Total Bonds in Target</td><td>$totalbonds%</td></tr>";
echo "</table>\n";


// picker table
echo "\n\n<table class=\"picker\">
<tr><th colspan=4>Picker</th></tr>
  <tr><th>symbol</th>
  <th>Diff Amt</th>
  <th>ζ val</th>
  <th>price/l.cl.</th></tr>";

$query ="select symbol,pe,range,beta,divyield,avgflag,sector,
round((pe+range+beta)-divyield,2) as z from MPT where flag = 'U' and sector != 'Bonds' and avgflag != 'A50A200'
order by z";

foreach ($dbh->query($query) as $trow) {
  $symbol = $trow['symbol'];
  
  $subquery="select MPT.symbol,prices.price-(select close from security_values where symbol = MPT.symbol order by timestamp desc limit 1) as pricediff from MPT,prices 
where flag = 'U' and MPT.symbol = '$symbol'
and MPT.symbol = prices.symbol";
  $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); 
  $pricediff = round($zrow['pricediff'],2);
  
  if ($pricediff < 0) {$bgstring = "#03aa03"; $clrstring = "#000000";}
    else {$bgstring = "#000000"; $clrstring = "#1111cc";}
  
 echo "<tr><td style=\"background: $bgstring; color: $clrstring;\">$trow[symbol]</td>
  <td style=\"background: $bgstring;color: $clrstring;\">$diffamt[$symbol]</td>
  <td style=\"background: $bgstring;color: $clrstring;\">$trow[z]</td>
  <td style=\"background: $bgstring;color: $clrstring;\">$pricediff</td>
  </tr>\n"; 
}

echo "</table>\n";


//overs sell table
echo "<table class=\"overs\"><th>sym</th><th colspan=2>Over Amt.</th><th>pr.chg.</th>";
$query = "select symbol from MPT where flag = 'O'";
foreach ($dbh->query($query) as $trow) {
  $sym = $trow['symbol'];
 
      $subquery = "select price,
      (select close from security_values where symbol = '$sym' order by timestamp desc limit 1) as pprice
      from prices where symbol = '$sym'";
      $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); 

   if ($diffamt[$sym] < 10) {continue;}
   
  $pricediff = round(($zrow['price'] - $zrow['pprice']),2);
  
  if ($pricediff > 0) {$bgstring = "#03aa03"; $clrstring = "#000000";}
    else {$bgstring = "#000000"; $clrstring = "#1111cc";}
   
	$barwidth = $diffamt[$sym]*.04 . "vw" ; 
        $gradwidth = 0;
  echo "<tr>
    <td style=\"background: $bgstring;color: $clrstring;\">$sym</td>
    <td style=\"background: $bgstring;color: $clrstring;\">$diffamt[$sym]</td>
    <td><svg class=\"bars\" width=\"$barwidth\" height=\"1vw\">";

    echo '  <linearGradient id="myGradient" >
      <stop offset="1px"  stop-color="#004c00" />';
      echo "<stop offset=\"95%\" stop-color=\"#22ff22\" />
    </linearGradient>";

    echo"<rect x=\"0\" y=\"0\" width=\"$barwidth\" height=\"1vw\" rx=\"4\" style=\"fill:url(\#myGradient);\"/>
		</svg></td>
    <td style=\"background: $bgstring;color: $clrstring;\">$pricediff</td></tr>";
}
echo "</table>";

function get_portfolio_value() {
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
