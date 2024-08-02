<?php

//Copyright (C) 2022 Mike Roetto <mike@roetto.org>
//SPDX-License-Identifier: GPL-3.0-or-later

header('Content-Type: application/json');

$dir = 'sqlite:portfolio.sqlite';

$dbh = new PDO($dir) or die("cannot open the database");

// echo "q $_GET[q]\n";

if ($_GET['q'] == "pv") {
    $v = get_portfolio_value();
    $subquery = "select value from historical order by date desc limit 1";
    $stmt = $dbh->prepare($subquery);
    $stmt->execute();
    $zrow = $stmt->fetch();
    $prev_value = $zrow['value'];

    $diff = round(($v - $prev_value), 2);

    $array = array('value' => "$v", 'prev_value' => "$prev_value", 'diff' => "$diff");
    echo json_encode($array);

    exit;
}

if ($_GET['q'] == "divs") {
    //     echo "ok\n";
//     echo "ok2\n";
    $query = "select sum(price*units) as cost,substr(date_new,1,7) as p from transactions where xtype = 'Div' group by p order by p desc limit 24 ";
}

if ($_GET['q'] == "valuetrend") {
    $query = "select date,value,cost from historical where date > date('now','-1 year')";
}

if ($_GET['q'] == "averages") {
    $query = "select date,WMA8,WMA24,WMA28,WMA36,WMA48,WMA41,
    WMA64,WMA72,WMA88,WMA110,WMA135,YMA3,YMA2,YMA1,return as rtn 
    from historical where date > date('now','-180 days')";
}

if ($_GET['q'] == "quarterdivs") {
    $query = "select sum(units*price) as total,(strftime('%Y', date_new)) || 'Q' || ((strftime('%m', date_new) + 2) / 3) as q 
    from transactions where xtype = 'Div' group by q order by q desc limit 8";
}


if ($_GET['q'] == "portpct") {

    $v = get_portfolio_value();
    // echo $v;
    $query = "SELECT DISTINCT symbol FROM transactions order by symbol";
    foreach ($dbh->query($query) as $row) {
        $sym = $row['symbol'];
        $subquery = "select sum(units) as buyunits from transactions where xtype = 'Buy' and symbol = '$sym'";
        $stmt = $dbh->prepare($subquery);
        $stmt->execute();
        $zrow = $stmt->fetch();
        $buyunits = $zrow['buyunits'];
        $subquery = "select sum(units) as sellunits from transactions where xtype = 'Sell' and symbol = '$sym'";
        $stmt = $dbh->prepare($subquery);
        $stmt->execute();
        $zrow = $stmt->fetch();
        $sellunits = $zrow['sellunits'];
        $netunits = ($buyunits - $sellunits);

        if ($netunits == 0)
            continue;

        $subquery = "select price from prices where symbol = '$sym'";
        $stmt = $dbh->prepare($subquery);
        $stmt->execute();
        $zrow = $stmt->fetch();
        $curprice = $zrow['price'];

        $subquery = "select target_alloc from MPT where symbol = '$sym'";
        $stmt = $dbh->prepare($subquery);
        $stmt->execute();
        $zrow = $stmt->fetch();
        $model_rec = $zrow['target_alloc'];

        $pos_value = ($curprice * $netunits);

        $pos_pct = ($pos_value / $v) * 100;
        $model_rec = ($model_rec * 100);

        //         echo "$sym $curprice $pos_value $pos_pct\n";
        if ($pos_pct > .8) {
            $array[] = array('symbol' => "$sym", 'pos_pct' => "$pos_pct", 'model_rec' => "$model_rec");
        } else {
            $otherpct = $otherpct + $pos_pct;
        }
        //         $array[$sym] = $pos_pct;


    }

    $array[] = array('symbol' => "Other", 'pos_pct' => "$otherpct");

    // asort($array,SORT_NUMERIC );
// print_r($array);

    echo json_encode($array);
    exit;
}

$sth = $dbh->prepare($query);
$sth->execute();

$data = $sth->fetchall(PDO::FETCH_ASSOC);

echo json_encode($data);

function get_portfolio_value()
{
    //     echo "executing function<br>";
    $ttotal = 0;
    $dir = 'sqlite:portfolio.sqlite';
    $dbh = new PDO($dir) or die("cannot open the database");
    $q = "SELECT DISTINCT symbol FROM prices order by symbol";
    foreach ($dbh->query($q) as $trow) {
        $tsym = $trow['symbol'];
        $subquery = "select sum(units) as buyunits from transactions where xtype = 'Buy' and symbol = '$tsym'";
        $stmt = $dbh->prepare($subquery);
        $stmt->execute();
        $zrow = $stmt->fetch();
        $buyunits = $zrow['buyunits'];
        $subquery = "select sum(units) as sellunits from transactions where xtype = 'Sell' and symbol = '$tsym'";
        $stmt = $dbh->prepare($subquery);
        $stmt->execute();
        $zrow = $stmt->fetch();
        $sellunits = $zrow['sellunits'];
        $netunits = ($buyunits - $sellunits);
        $subquery = "select price from prices where symbol = '$tsym'";
        $stmt = $dbh->prepare($subquery);
        $stmt->execute();
        $zrow = $stmt->fetch();

        // $cprice = $zrow['price'];

        if (is_array($zrow) && isset($zrow['price']) && $zrow['price'] !== '') {
            $cprice = $zrow['price'];
        } else {
            // Handle the case where 'price' is not set or is an empty string.
            $cprice = 0; // Set a default value, for example.
            error_log("value error $cprice $tsym.");
        }

        if ($netunits == 0)
            continue;
        $value = round(($netunits * $cprice), 3);
        $ttotal = ($ttotal + $value);
        //      echo "total $ttotal <br>";
    }
    return $ttotal;
}
?>