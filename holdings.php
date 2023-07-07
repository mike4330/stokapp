<!DOCTYPE html>
<html>

<head>
  <meta http-equiv="refresh" content="301">
<title>portfolio holdings</title>


<link rel="stylesheet" type="text/css" href="main.css">
<link rel="stylesheet" type="text/css" href="nav.css">
<style>
</style>

<script>
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
</script>
</head>

<body>

<?php include ("nav.php"); ?> 

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

$query = "SELECT DISTINCT symbol FROM transactions order by symbol";

$ttotal=get_portfolio_value();
//echo "total is $ttotal<br>";

echo "<table class=\"holdings\" id=\"myTable2\">";
echo "<th onclick=\"sortTable(0)\">symbol</th>

	<th>net units</th>
	<th colspan=2>last price</th>
	<th onclick=\"numericsort(4)\">pos. value</th>
	<th>tgt. diff \$</th>
	<th onclick=\"numericsort(6)\">port pct.</th>
	<th>all. tgt.</th>
	

	<th>net cost</th>
	<th>cost basis</th>
	
	<th onclick=\"numericsort(10)\">UGL\$</th>
	<th onclick=\"numericsort(11)\">return%</th>
	<th>RGL\$</th>
	
	<th onclick=\"numericsort(14)\">Divs</th>
	<th>PriceChg</th>
	<th onclick=\"numericsort(15)\">PosValChg</th>
	";

//main tabular output
foreach ($dbh->query($query) as $row) {
    $sym=$row['symbol'];
    
        $subquery = "select sum(units) as buyunits from transactions where xtype = 'Buy' and symbol = '$sym'";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $buyunits = $zrow['buyunits'];
        $subquery = "select sum(units) as sellunits from transactions where xtype = 'Sell' and symbol = '$sym'";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $sellunits = $zrow['sellunits'];
        
        $netunits = round(($buyunits-$sellunits),4);
        
        $subquery = "select price from prices where symbol = '$sym'";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $cprice = round($zrow['price'],4);
        
        if ($netunits <= 0) continue;
                
        $value = round(($netunits * $cprice),3);
                
        // silver premium
        $premiumprice = $cprice + ($cprice * .2);
        if ($sym == 'XAG') {$value = ($netunits*$premiumprice);}
        
        $total = ($total + $value);
        
        $subquery="SELECT sum(units*price) AS buytotal FROM transactions WHERE xtype = 'Buy' AND symbol = '$sym'";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $buytotal = round($zrow['buytotal'],3);
        
        $subquery="SELECT sum(units*price) AS selltotal FROM transactions WHERE xtype = 'Sell' AND symbol = '$sym'";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $selltotal = round($zrow['selltotal'],3);
        
        $subquery="SELECT sum(gain) as rgain FROM transactions WHERE xtype = 'Sell' AND symbol = '$sym'";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $gain = round($zrow['rgain'],2);
        
        $subquery="SELECT sum(units*price) as total_dividends FROM transactions WHERE xtype = 'Div' AND symbol = '$sym'";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $total_dividends = round($zrow['total_dividends'],2);
                
//         echo "gain for $sym is $gain<br>";
        
        $netcost = round(($buytotal - $selltotal),2)+$gain;
        $tnetcost = ($netcost + $tnetcost);
        $dollarreturn=round(($value - $netcost),2);
        
        if ($dollarreturn > 0) {$freecash=($freecash+$dollarreturn);}
        
        $costbasis = round(($netcost / $netunits),2);
        
        $returnpct=round((($gain+$dollarreturn+$total_dividends)/$netcost)*100,2);
        
        if ($returnpct < -5) {$color2="$red2";$tcolor2="black";} 
            elseif ($returnpct > -5 && $returnpct < 0) {$color2="$red1";}
            elseif ($returnpct > 0 && $returnpct < 5) {$color2="$green1";}
            elseif ($returnpct > 5 && $returnpct < 10) {$color2="$green2";}
            elseif ($returnpct > 10 && $returnpct < 15) {$color2="$green3";}
            elseif ($returnpct > 15 && $returnpct < 20) {$color2="$green4";}
            elseif ($returnpct > 20 ) {$color2="$green5";}
            else {$color2 = "";
                $tcolor2="";}
                
        if ($returnpct > 0) {$profitable_position = $profitable_position + $value;}
                    
        if ($dollarreturn < 0) {
            $cellcolor = "$red1" ;$unrealtcolor="black";}
            else {$cellcolor = "";$unrealtcolor="";}
        
        $portfolio_pct = round((($value / $ttotal)*100),2);
        $subquery = "select alloc_target,compidx2,class as sclass,mean50,mean200 from prices where symbol = '$sym'";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $alloc_target = $zrow['alloc_target'];
//         $compidx=round($zrow['compidx2'],2);
        $sclass=$zrow['sclass'];
        
        $target_value = ($ttotal * ($alloc_target/100));
        $target_diff = round(($value - $target_value),2);
        
        if ($target_diff < 0) {
            $tcellcolor = "#2222ee" ;$targetcolor="black";}
            else {$tcellcolor = "";$targetcolor="";}
                    
    $rquery = "UPDATE returns set returnpct = $returnpct where symbol = '$sym'";
    $stmt = $dbh->prepare($rquery);$stmt->execute();
    
    //row output
    echo "<tr class=\"main\"><td><a href=\"/portfolio/?symfilter=$row[symbol]\" class=\"holdinglist\">$sym</a></td>  
    <td class=\"cntr\">$netunits</td>
    <td class=\"cntr\">\$$cprice</td><td>";
    
    if ($cprice < $costbasis) {echo '<span class=icon>CB</span>';}
    if ($cprice < $zrow['mean50']) {echo '<span class=icon style="color: #ff0000;">▼50</span>';}
    if ($cprice < $zrow['mean200']) {echo '<span class=icon style="color: #ff0000;">▼200</span>';}
    if ($cprice > $zrow['mean50'] && $zrow['mean50'] > 1 ) {echo '<span class=icon style="color: #08ac08;" >▲50</span>';}
    if ($cprice > $zrow['mean200'] && $zrow['mean200'] > 1) {echo '<span class=icon style="color: #08ac08;">▲200</span>';}
   
 
    $subquery="SELECT close from security_values where symbol = '$sym' order by timestamp desc limit 1";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $prevclose = $zrow['close'];
        
        $pos_price_change = round(($cprice - $prevclose),2);
        if ($sym == "ETHUSD" || $sym == "BTCUSD" || $sym == "XAG") {$pos_price_change=0;}
        $pos_day_change = round((($pos_price_change)*$netunits),2);
        
    
//     ▲
    echo "</td>
    <td class=\"cntr\">$value</td>
    <td class=\"cntr\" style=\"background: $tcellcolor;color: $targetcolor\">\$$target_diff</td>
    <td class=\"cntr\">$portfolio_pct</td>
    <td class=\"cntr\">$alloc_target</td>
  
    <td class=\"cntr\">\$$netcost</td>
    <td class=\"cntr\">$costbasis</td>
    <td class=\"cntr\" style=\"background: $cellcolor;color: $unrealtcolor\">$dollarreturn</td>
    <td class=\"cntr\" style=\"background: $color2;color: $tcolor2;\">$returnpct</td>

    <td>$gain</td>
    
    <td class=\"cntr\">$total_dividends</td>
    <td class=\"cntr\">$pos_price_change</td>
    <td class=\"cntr\">$pos_day_change</td>
    </tr>\n";
    
    
}

$tdollarrtn=round(($ttotal - $tnetcost),2);
$trtnpct=round(($tdollarrtn / $tnetcost)*100,2);

echo "</table>";

// bottom summary bar
$subquery = "select value from historical order by date desc limit 1";
$stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $prevvalue = $zrow['value'];

$daychange = round(($ttotal - $prevvalue),2);



echo "<div class=statusmessage>Portfolio Value: \$$ttotal ($daychange)
<br>Dol. Rtn. \$$tdollarrtn
<br>Pct. Rtn. $trtnpct %
<br>Profit Skim $freecash
<br>Profitable Pos. Val.: $profitable_position
</div>";

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

</body>
</html>
