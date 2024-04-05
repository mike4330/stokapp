<?php
$x=sym_costbasis($argv[1]);

// this accounts for lots partially sold

echo $x;

function sym_costbasis($symbol) {
//     echo "$symbol\n";
    $dir = 'sqlite:portfolio.sqlite';
    $dbh  = new PDO($dir) or die("cannot open the database");
    
    // whole lots remaining
    $query = "SELECT sum(price*units) as cost 
    from transactions 
    where symbol = '$symbol' 
    and xtype = 'Buy' and disposition IS NULL and units_remaining IS NULL";
    $stmt = $dbh->prepare($query);$stmt->execute();$zrow = $stmt->fetch();
    $cost1 = $zrow['cost'];
    
    // partial lots remaining
    $query = "SELECT sum(price*units_remaining) as cost 
    from transactions 
    where symbol = '$symbol' 
    and xtype = 'Buy' and disposition IS NULL and units_remaining IS NOT NULL";
    $stmt = $dbh->prepare($query);$stmt->execute();$zrow = $stmt->fetch();
    $cost2 = $zrow['cost'];
    

    //echo "cost of whole lots $cost1\n cost of partial lots $cost2\n";
     
    $netcost = $cost1 + $cost2;

   
    
    
    
    return $netcost;
}



?>
