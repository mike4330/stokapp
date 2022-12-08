<!--Copyright (C) 2022 Mike Roetto <mike@roetto.org>
SPDX-License-Identifier: GPL-3.0-or-later-->
<!DOCTYPE html>

<html>
<head>
<meta http-equiv="refresh" content="120">
<link rel="stylesheet" type="text/css" href="main.css">
<link rel="stylesheet" type="text/css" href="nav.css">
<title>Tax Lot Analysis</title>
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
<table class="lots"><th>Account</th><th>symbol</th><th>date</th>
<th>units</th>

<th>Current Price</th><th>cur val</th>
<th>profit</th>
<th>flag</th>
<?php

$hcolor[0]="#ffffff";
$hcolor[1]="#009900";
$hcolor[2]="#00cc00";
$hcolor[3]="#00ff00";
$hcolor[4]="#22ff22";

$dir = 'sqlite:portfolio.sqlite';
include ("nav.php"); 
$dbh  = new PDO($dir) or die("cannot open the database");

$query = "SELECT  symbol,flag,overamt FROM MPT where flag = 'O' order by symbol";
foreach ($dbh->query($query) as $row) {
    $symbol=$row['symbol'];
    $flag=$row['flag'];
    
    $pquery = "select price from prices where symbol = '$symbol'";
    $stmt = $dbh->prepare($pquery);$stmt->execute();$zrow = $stmt->fetch();$cprice=$zrow['price'];

	
    if ($row['overamt'] < 1) {continue;}
    echo "<tr><td colspan=9 style=\"background: black;\"></td></tr>";
    $subquery = "select * from transactions 
    where symbol = '$symbol' and xtype='Buy' and disposition IS NULL ";
        foreach ($dbh->query($subquery) as $rowb) {
            
            if ($rowb['price'] < $cprice) {
              if ($rowb['units_remaining']) {$units=$rowb['units_remaining'];}
                else {$units=$rowb['units'];}
              $curval=round(($cprice*$units),2);
              $cost=$rowb['price']*$units;
              $profit=round(($curval - $cost),3);
              
              #discard trash
              if ($profit < .003) {continue;}
              if ($curval < 1) {continue;}
              
              echo "<tr><td class=lots>$rowb[acct]</td><td class=lots>$symbol</td>
              <td class=lots>$rowb[date_new]</td>
              <td class=lots>$units</td>
              <td class=lots>$cprice</td>
              <td class=lots>$curval</td>
              <td class=lots><b>$profit</b></td>
              <td class=lots>$flag</td>
              </tr>";
            $sum[$symbol]=$sum[$symbol]+$profit;
            }
        } 
  if ($sum[$symbol]) {
    if ($sum[$symbol] > 6.86) {$ci=4;}
      elseif ($sum[$symbol] > 3.61) {$ci=3;}
      elseif ($sum[$symbol] > 1.9) {$ci=2;} 
      elseif ($sum[$symbol] > 1) {$ci=1;} 
        else {$ci=0;}
	echo "<tr><td style=\"color: #a0a0a0;\">profit for $symbol</td>
	<td><b><span style=\"background: $hcolor[$ci];color:#000000;\">\$$sum[$symbol]</b></span></td></tr>";
	$totalprofit=$totalprofit+$sum[$symbol];
    echo "<tr><td>Total over weight </td><td>$$row[overamt]</td></tr>";
	}
}
echo "<tr><td colspan=3>total avail profit $totalprofit</td></tr>";
?>
</table>
</body>
</html>
