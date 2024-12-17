<?php
//Copyright (C) 2022,2024 Mike Roetto <mike@roetto.org>
//SPDX-License-Identifier: GPL-3.0-or-later
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

function calculateTargetDiff($symbol) {
    global $dbh; // Assuming $dbh is your database connection

    // Calculate the value for the given symbol
    $query = "SELECT SUM(CASE WHEN t.units_remaining IS NULL THEN t.units ELSE t.units_remaining END) AS netunits,
                     p.price AS current_price
              FROM transactions t
              JOIN prices p ON t.symbol = p.symbol
              WHERE t.symbol = :symbol AND t.xtype = 'Buy' AND t.disposition IS NULL";
    $stmt = $dbh->prepare($query);
    $stmt->execute([':symbol' => $symbol]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $netunits = $row['netunits'];
    $price = $row['current_price'];
    $value = round($netunits * $price, 3);

    // Calculate the total portfolio value
    $query = "SELECT SUM(
                CASE WHEN t.units_remaining IS NULL THEN t.units ELSE t.units_remaining END * p.price
              ) AS total_value
              FROM transactions t
              JOIN prices p ON t.symbol = p.symbol
              WHERE t.xtype = 'Buy' AND t.disposition IS NULL";
    $stmt = $dbh->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalPortfolioValue = $row['total_value'];

    // Get the allocation target for the symbol
    $query = "SELECT alloc_target FROM prices WHERE symbol = :symbol";
    $stmt = $dbh->prepare($query);
    $stmt->execute([':symbol' => $symbol]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $alloc_target = $row['alloc_target'];

    // Calculate the target value
    $target_value = $totalPortfolioValue * ($alloc_target / 100);

    // Calculate the difference
    $target_diff = round($value - $target_value, 2);

    return [
        'target_diff' => $target_diff,
        'value' => $value,
        'totalPortfolioValue' => $totalPortfolioValue
    ];
}


?>