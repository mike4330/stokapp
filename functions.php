<?php
$x = sym_costbasis($argv[1]);

// this accounts for lots partially sold

echo $x;

function sym_costbasis($symbol)
{
    //     echo "$symbol\n";
    $dir = 'sqlite:portfolio.sqlite';
    $dbh = new PDO($dir) or die("cannot open the database");

    // whole lots remaining
    $query = "SELECT sum(price*units) as cost 
    from transactions 
    where symbol = '$symbol' 
    and xtype = 'Buy' and disposition IS NULL and units_remaining IS NULL";
    $stmt = $dbh->prepare($query);
    $stmt->execute();
    $zrow = $stmt->fetch();
    $cost1 = $zrow['cost'];

    // partial lots remaining
    $query = "SELECT sum(price*units_remaining) as cost 
    from transactions 
    where symbol = '$symbol' 
    and xtype = 'Buy' and disposition IS NULL and units_remaining IS NOT NULL";
    $stmt = $dbh->prepare($query);
    $stmt->execute();
    $zrow = $stmt->fetch();
    $cost2 = $zrow['cost'];

    //echo "cost of whole lots $cost1\n cost of partial lots $cost2\n";

    $netcost = $cost1 + $cost2;

    return $netcost;
}

function posvalue($symbol)
{
    $dir = 'sqlite:portfolio.sqlite';
    $dbh = new PDO($dir) or die("cannot open the database");
    $nuquery = "SELECT SUM(result) AS total_sum
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

    $stmt = $dbh->prepare($nuquery);
    $stmt->execute();
    $zrow = $stmt->fetch();
    $nu = $zrow['total_sum'];

    $query = "select price from prices where symbol = '$symbol';";
    $stmt = $dbh->prepare($query);
    $stmt->execute();
    $zrow = $stmt->fetch();
    $current_price = $zrow['price'];
    $val = round(($current_price * $nu),2);

    return $val;
}



?>