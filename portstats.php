<?php

// updates historical table with returns , cost and moving averages

chdir('/var/www/html/portfolio');

$dir = 'sqlite:portfolio.sqlite';

$pvalue=@get_portfolio_value();
$pcost=@get_porfolio_cost();

$curdate=date("Y-m-d");

$dollarreturn = ($pvalue - $pcost);

$return = round((($dollarreturn/$pcost)*100),3);

$dbh  = new PDO($dir) or die("cannot open the database");

echo "query 1\n";
$subquery = "select avg(return) as wma8 from historical where date >= date('now','-35 days')";
$stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $wma8 = round($zrow['wma8'],3);
echo "query 2\n";
$subquery = "select avg(return) as wma48 from historical where date >= date('now','-336 days')";
$stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $wma48 = round($zrow['wma48'],3);
echo "query 3\n";
$subquery = "select avg(return) as wma28 from historical where date >= date('now','-196 days')";
$stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $wma28 = round($zrow['wma28'],3);

$subquery = "select avg(return) as wma24 from historical where date >= date('now','-168 days')";
$stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $wma24 = round($zrow['wma24'],3);

$subquery = "select avg(return) as wma41 from historical where date >= date('now','-287 days')";
$stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $wma41 = round($zrow['wma41'],3);

$subquery = "select avg(return) as wma55 from historical where date >= date('now','-385 days')";
$stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $wma55 = round($zrow['wma55'],3);

$subquery = "select avg(return) as wma36 from historical where date >= date('now','-252 days')";
$stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $wma36 = round($zrow['wma36'],3);

$subquery = "select avg(return) as wma64 from historical where date >= date('now','-448 days')";
$stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $wma64 = round($zrow['wma64'],3);

$subquery = "select avg(return) as wma72 from historical where date >= date('now','-504 days')";
$stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $wma72 = round($zrow['wma72'],3);

$subquery = "select avg(return) as wma88 from historical where date >= date('now','-616 days')";
$stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $wma88 = round($zrow['wma88'],3);

$q = "INSERT into historical (date,value,cost,dret,return,WMA8,WMA24,WMA28,WMA41,WMA48,WMA36,WMA64,WMA72,WMA88) 
VALUES('$curdate','$pvalue','$pcost','$dollarreturn','$return', '$wma8','$wma24', '$wma28','$wma41', '$wma48',  '$wma36','$wma64', '$wma72', '$wma88')";

echo "query is $q\n";

$stmt = $dbh->prepare($q);$stmt->execute();


// exit;



function get_portfolio_value() {
//     echo "executing function<br>";
    $ttotal=0;
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
        
        $value = ($netunits * $cprice);
        $ttotal = ($ttotal + $value);
        
        
//      echo "value $ttotal\n";
    }
return $ttotal;
}

function get_porfolio_cost() {
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
            if ($netunits == 0) continue;
            
            $subquery="SELECT sum(units*price) AS buytotal FROM transactions WHERE xtype = 'Buy' AND symbol = '$tsym'";
            $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $buytotal = $zrow['buytotal'];
            
            $subquery="SELECT sum(units*price) AS selltotal,sum(gain) as gain FROM transactions WHERE xtype = 'Sell' AND symbol = '$tsym'";
            $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $selltotal = $zrow['selltotal'];$gain=$zrow['gain'];
            
            
            $netcost = round(($buytotal - $selltotal),3)+$gain;
            $tnetcost = ($netcost + $tnetcost);
            
            //echo "$tsym\t\t$gain\t$netcost\t$tnetcost\n";
         
            }
        return $tnetcost;
}
 
?>
