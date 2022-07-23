

<!-- SPDX-License-Identifier: GPL-3.0-or-later -->

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="main.css">
<title>Allocations</title>


<script src="/js/jquery-3.1.1.min.js"></script>
<script type="text/javascript" src="/js/chart.js"></script> 


</head>
<body> 
<?php

$stock_pct_target=44;
$bond_pct_target=37;
$other_cats_target = 100-($stock_pct_target + $bond_pct_target);

$other_cats_prop_pct = round(($other_cats_target / 4),2);

// stock bond ratios
$master_cat_target['Domestic Bonds']=$bond_pct_target*0.8;
$master_cat_target['Foreign Bonds']=$bond_pct_target*0.2;
$master_cat_target['Domestic Stock']=$stock_pct_target*0.8;
$master_cat_target['Foreign Stock']=$stock_pct_target*0.2;

// Alternatives Ratios
$master_cat_target['Precious Metals']=$other_cats_prop_pct+0.97;
$master_cat_target['Real Estate']=$other_cats_prop_pct+1.04;
$master_cat_target['Commodities']=$other_cats_prop_pct+1.06;
$master_cat_target['Currency']=$other_cats_prop_pct-3.07;

function get_portfolio_value() {
//     echo "executing function<br>";
    $dir = 'sqlite:portfolio.sqlite';
    $dbh  = new PDO($dir) or die("cannot open the database");
    $q = "SELECT DISTINCT symbol FROM transactions order by symbol";
    foreach ($dbh->query($q) as $trow) {
        $tsym=$trow[symbol];
        $subquery = "select sum(units) as buyunits from transactions where xtype = 'Buy' and symbol = '$tsym'";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $buyunits = $zrow['buyunits'];
        $subquery = "select sum(units) as sellunits from transactions where xtype = 'Sell' and symbol = '$tsym'";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $sellunits = $zrow[sellunits];
        $netunits = ($buyunits-$sellunits);
        $subquery = "select price from prices where symbol = '$tsym'";
        $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $cprice = $zrow[price];
        
        if ($netunits == 0) continue;
        
        $value = round(($netunits * $cprice),3);
        $ttotal = ($ttotal + $value);
//      echo "total $ttotal <br>";
    }
return $ttotal;
}


$dir = 'sqlite:portfolio.sqlite';
include ("nav.php"); 
$dbh  = new PDO($dir) or die("cannot open the database");

$portvalue=get_portfolio_value();

// echo "<table>";
// echo "<tr><td>$portvalue</td></tr>";

$query = "SELECT DISTINCT symbol FROM transactions order by symbol";

foreach ($dbh->query($query) as $row) { 
    $sym=$row['symbol'];
    
    $subquery = "select sum(units) as buyunits from transactions where xtype = 'Buy' and symbol = '$sym'";
    $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $buyunits = $zrow['buyunits'];
    $subquery = "select sum(units) as sellunits from transactions where xtype = 'Sell' and symbol = '$sym'";
    $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $sellunits = $zrow[sellunits];
    
    $netunits = round(($buyunits-$sellunits),4);
    
    $subquery = "select price from prices where symbol = '$sym'";
    $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $cprice = round($zrow[price],2);
    
    if ($netunits == 0) continue;
            
    $value = round(($netunits * $cprice),3);                
    
    $subquery = "select asset_class from prices where symbol = '$sym'";
    $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $asset_class=$zrow['asset_class'];
    
//     echo "<tr><td>$row[symbol]</td><td>$asset_class</td><td>$value</td></tr>";
    
    $class_total[$asset_class]=$class_total[$asset_class]+$value;
    
    }

// // echo "</table>";

// echo '<table style="position:absolute; top:150px; right:5vw; z-index:1;width: 25vw;height: 10vw;">';

echo '<table style="position:absolute; top:150px; left:66vw; z-index:1;">';
echo '<th>category</th><th>value</th><th>Pct</th><th>cat tgt</th><th>diff</th>';
$query = "SELECT DISTINCT asset_class from prices where asset_class IS NOT NULL";

foreach ($dbh->query($query) as $rowa) { 
    $ac=$rowa['asset_class'];
    $class_pct = $class_total[$ac]/$portvalue*100;
    $class_pct_r = round($class_pct,1);
    
    $class_stats[] = array('asset_class' => "$rowa[asset_class]", 'class_pct'=> "$class_pct");
    
    echo "<tr>
        <td style=\"color:#00ff00;\">$rowa[asset_class]</td>
        <td style=\"color:#00ff00;padding-left: 15px\">$class_total[$ac]</td>
        <td style=\"color:#00ff00;padding-left: 1px;\">$class_pct_r%</td>
        <td>$master_cat_target[$ac]%</td>";
        
    $zdiff= round($class_pct_r -$master_cat_target[$ac],4);
    
    if ($zdiff < 0) {
        echo "<td style=\"padding-left: 20px\">$zdiff%</td>";
        $show[$ac] = 1;
        $addinvest[$ac] = round((($master_cat_target[$ac]/100) * $portvalue) - $class_total[$ac],2);
        }
    else {$show[$ac]=0;}
    
    echo "</tr>";
}

// echo "<tr><td  colspan=4 style=\"background: #393939\";> </td></tr>";

$total_stock = $class_total['Domestic Stock'] + $class_total['Foreign Stock'];
$total_stock_pct = round((($total_stock / $portvalue)*100),1);

$total_bond = $class_total['Domestic Bonds'] + $class_total['Foreign Bonds'];
$total_bond_pct = round((($total_bond / $portvalue)*100),1);

$other_cats_pct=100-($total_stock_pct+$total_bond_pct);
$other_cats_dlr=$portvalue - ($total_stock + $total_bond);

$stock_target=round(($portvalue*($stock_pct_target/100)),2);
$bond_target=round(($portvalue*($bond_pct_target/100)),2);

$stock_diff = round(($total_stock - $stock_target),0);
$bond_diff = round(($total_bond - $bond_target),0);

echo "<th>Master Cat.</th><th>Value</th><th>Pct</th><th colspan=2>Tgt pct</th>";
echo "<tr>
    <td class=\"alloc\">Stock</td><td class=\"alloc\">$total_stock</td>
    <td class=\"alloc\">$total_stock_pct% </td>
    <td class=\"alloc\" style=\"padding-left: 65px\" colspan=2>$stock_pct_target%</td>
    </tr>";

echo "<tr>
    <td class=\"alloc\">Bond</td><td class=\"alloc\">$total_bond</td>
    <td class=\"alloc\">$total_bond_pct%</td>
    <td class=\"alloc\" style=\"padding-left: 65px\" colspan=2>$bond_pct_target%</td>
    </tr>";    
echo "<tr>
    <td class=\"alloc\">Other Cats</td><td class=\"alloc\">$other_cats_dlr</td>
    <td class=\"alloc\">$other_cats_pct% </td>
    <td class=\"alloc\" style=\"padding-left: 65px\" colspan=2>$other_cats_target%</td>
    </tr>";

echo "<th>Master Cat.</th><th>Dollar Target</th><th colspan=3>Diff.</th>";

echo "<tr><td class=\"alloc\">Stock</td>
    <td class=\"alloc\" style=\"padding-left: 10px\">$stock_target</td>
    <td class=\"alloc\" colspan=3 style=\"padding-left: 90px\">\$$stock_diff</td>
</tr>";

echo "<tr>
    <td class=\"alloc\">Bond</td>
    <td class=\"alloc\" style=\"padding-left: 10px\">$bond_target</td>
    <td class=\"alloc\" style=\"padding-left: 90px\" colspan=3>\$$bond_diff</td>";
echo "</table>";


// model trade table
$query = "SELECT DISTINCT asset_class from prices where asset_class IS NOT NULL order by asset_class";
echo "<table style=\"position:absolute; top:150px; left:33vw; z-index:1;\"><th>Class</th><th>Sym pick</th><th>Add. Invest.</th>";

foreach ($dbh->query($query) as $rowb) { 
    $ac=$rowb[asset_class];
    
    $subquery = "select symbol 
    FROM prices 
    WHERE asset_class = '$ac' 
    AND class is NOT NULL
    order by compidx2 limit 1";
    
    $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch(); $winner_symbol = $zrow['symbol'];
    
    if ($show[$ac]) {    
        echo "<tr><td class=\"alloc\">$rowb[asset_class]</td>";
        echo "<td class=\"alloc\">$winner_symbol </td><td class=\"alloc\">\$$addinvest[$ac]</td>";
        echo '</tr>';
        }
    }

echo "</table>";

?>

<div class="chart-container" style="position:absolute; top:5vw; left:1vw; z-index:1; height: 40vh;width: 22vw;"><canvas id="pie" ></canvas></div>

<div class="chart-container" style="position:absolute; top:22vw; left:1vw; z-index:1; height: 40vh;width: 22vw;"><canvas id="class_pie" ></canvas></div>


<div class="chart-container" style="position:absolute; top:20vw; left:26vw; z-index:1; height: 45vh;width: 38vw;"><canvas id="catpct" ></canvas></div>

<script>


var class_stats = JSON.parse('<?php echo json_encode($class_stats); ?>');

var asset_class = []; var class_pct = [];

for(var i in class_stats){
    asset_class.push(class_stats[i].asset_class);
    class_pct.push(class_stats[i].class_pct);
    console.log(class_stats[i].asset_class,class_stats[i].class_pct);
}

var ctx = $('#class_pie');
var chartdata = {
    labels: asset_class,
    datasets: [{
    labels: asset_class,
    borderWidth: 0.0,
        backgroundColor: [
        'rgb(160, 140, 0)',
        'rgb(12, 12, 155)',
        'rgb(64, 255, 64)',
        'rgb(130, 130, 255)',
        'rgb(128, 0, 0)',
        'rgb(20, 192, 20)',
        'rgb(255, 255, 255)',
        'rgb(255, 180, 180)'
        ],
    data: class_pct    
    }]     
}

var barGraph = new Chart(ctx, {
    type:'doughnut',
    data: chartdata, 
    options: {
        animation: {
            animateScale: true,
            duration: 200
        },
        cutout: "30%",
        plugins: {
            legend: {
                position: 'right',
                labels: {color: 'rgb(255,255,255)'}
                }
            
            }
        }
});



// ---------------------------------

$.ajax({
    url: 'data.php?q=portpct',
    type: 'GET',
    dataType: 'json',
    success:function(data){
        Chart.defaults.datasets.line.fill = true;
        var symbol = [];
        var pos_pct = [];
        
        for(var i in data){
          symbol.push(data[i].symbol);
          pos_pct.push(data[i].pos_pct);
//           console.log(data[i].symbol);
            }
        // pos_pct.sort(function(a, b){return a - b});
        var chartdata = {
            labels: symbol,
            datasets: [{
//             label: 'symbol',
            data: pos_pct,
            spacing: 0,
            borderWidth: 0.5,
            backgroundColor: [
                'rgb(0,0,128)',
                'rgb(0,0,255)',
                'rgb(128,128,255)',
                'rgb(0,128,0)',
                'rgb(0,255,0)',
                'rgb(128,255,128)',
                'rgb(128,0,0)',
                'rgb(255,0,0)',
                'rgb(255,128,128)',
                'rgb(255,255,0)',
                ],
            }]
        };
        
        var ctx = $('#pie');
        
        var barGraph = new Chart(ctx, {
            type:'pie',
            data: chartdata,  
            options: {
            maintainAspectRatio: false,
            responsive: true,
            plugins: {legend: {
                        position: 'bottom',
                        labels: {color: 'rgb(255,255,255)'}
                            }
                        }
            }    
        });
            
    } //end success function
    
}) //end ajax


// ---------Asset Class Percentages Timeseries------------------------

$.ajax({
    url: 'datacsv.php?catpct',
    type: 'GET',
    dataType: 'json',
    success:function(data){
        
        Chart.defaults.datasets.line.fill = true;
        Chart.overrides.line.tension = 0.1;
        Chart.defaults.datasets.line.pointRadius = 0;
        Chart.defaults.plugins.title.color = 'rgb(0,255,0)';
        

        var date = []; var pctcomms = []; var pctstock = []; var pctbonds = []; var pctpm = [];
        for(var i in data){
            date.push(data[i].date);
            pctcomms.push(data[i].pctcomms);
            pctstock.push(data[i].pctstock);
            pctbonds.push(data[i].pctbonds);
            pctpm.push(data[i].pctpm);
            console.log("bonds: ",data[i].pctbonds);
            }
    var ctx = $('#catpct');
    Chart.defaults.datasets.line.fill = true;
    var chartdata = {
        labels: date,
            datasets: [
                    {
                label: 'PM Pct',
                borderWidth: 0,
                borderColor: 'rgba(255,255,255,255)',
                backgroundColor: 'rgba(12,64,12,.6)',
                data: pctpm    
            },
            {
                label: 'Comms Pct',
                borderWidth: 0,
                borderColor: 'rgba(255,255,255,255)',
                backgroundColor: 'rgba(255,255,12,.6)',
                data: pctcomms    
            },
            {
                label: 'Bonds Pct',
                backgroundColor: 'rgba(22,22,190,.6)',
                data: pctbonds
            }  ,
                   {
                label: 'Stock Pct',
                backgroundColor: 'rgba(200,20,20,.6)',
                data: pctstock
            } 
            ]     
    }
    
        var barGraph = new Chart(ctx, {
        type:'line',
        data: chartdata,  
        options: {
        scales: {
            x:{
                
                ticks:{
                    color: 'rgb(255, 255, 255)'
                }
                
            },
            y:{
                stacked: true,
                grid: {color: 'rgb(90,90,90)'},
                ticks:{
                    color: 'rgb(255,255,255)'
                }
            }
            
        },
        maintainAspectRatio: false,
        responsive: true,
        plugins: {legend: {
                    position: 'bottom',
                    labels: {color: 'rgb(255,255,255)'}
                        }
                    }
        }    
    }); 
    
        
        
    }
})

</script>

</body>
</html>
