<!DOCTYPE html>
<html lang="en">
<head>
<title>portfolio</title>
<link rel="stylesheet" type="text/css" href="main.css">

<script src="/js/jquery-3.1.1.min.js"></script>
<script type="text/javascript" src="/js/chart.js"></script> 
<script type="text/javascript" src="/js/luxon.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@^1"></script>
<script src="/js/node_modules/chartjs-plugin-annotation/dist/chartjs-plugin-annotation.min.js"></script>



</head>

<body>


<?php
include ("nav.php"); 

$c['Buy']="#11cc11";
$c['Div']="#eefb0b";
$c['Sell']="#dd3333";


$sc['ANGL']="#1192e8";$tc['ANGL']="#000000";
$sc['ASML']="#002d9c";
$sc['BEN']="#484848";
$sc['BSJN']="#005d5d";
$sc['BRK.B']="#eeee99";
$sc['BTCUSD']="#116e6e";$sc['C']="#eeee00";
$sc['DBB']="#b28600";$sc['EMB']="#5818b3";
$sc['EWJ']="#aaaa00";$sc['ETHUSD']="#5818b3";
$sc['FPE']="#6929c4";$sc['FNBGX']="#ffee11";
$sc['GILD']="#012749";$tc['GILD']="#eeee11";
$sc['GSL']="#9a2530";$tc['GSL']="#ffffff";
$sc['KHC']="#3f2341";$tc['KHC']="#fefefe";
$sc['JPIB']="#ff2341";$tc['JPIB']="#fefefe";
$sc['KMB']="#8a3800";$tc['KMB']="#eeeeee";
$sc['LKOR']="#772428"; $tc['LKOR']="#EEEEEC";
$sc['LNG']="#a56eff";$sc['MLN']="#fa4d56";
$sc['MPW']="#ee3333";$tc['MPW']="#ffff00";
$sc['PDBC']="#4c4c4c";$tc['PDBC']="#fefefe";
$sc['REM']="#dd427a";$sc['SGOL']="#ee538b";
$sc['SOXX']="#009d9a";$sc['UFPI']="#9f1853";$sc['VMC']="#167735"; $sc['XAG']="#e9e9e9";

$dir = 'sqlite:portfolio.sqlite';
$dbh  = new PDO($dir) or die("cannot open the database");

//echo "db ok<br>";

$query = "SELECT *,(price*units) as ccost from transactions order by date_new desc,id desc";

if (!empty($_GET['symfilter'])):
        $filterterm = $_GET['symfilter'];
    // echo "title filter $filterterm<br>"; 
        $query = "SELECT *,(price*units) as ccost from transactions where symbol = '$filterterm' order by id desc";
        echo '<a href="/portfolio" class="button1">🗑 reset filter</a><br>';
        echo '<div class="minichart" ><canvas id="chart" ></canvas></div>';
        echo '<span class="smallnav">more symbols<br>
        <a href="/portfolio/?symfilter=ANGL" class="buttonsmall">ANGL</a> 
        <a href="/portfolio/?symfilter=ASML" class="buttonsmall">ASML</a> 
        <a href="/portfolio/?symfilter=BRK.B" class="buttonsmall">BRK.B</a>
        <a href="/portfolio/?symfilter=BSJN" class="buttonsmall">BSJN</a>
        <a href="/portfolio/?symfilter=DBB" class="buttonsmall">DBB</a>
        <a href="/portfolio/?symfilter=FPE" class="buttonsmall">FPE</a>
        <a href="/portfolio/?symfilter=GILD" class="buttonsmall">GILD</a> 
        <a href="/portfolio/?symfilter=GSL" class="buttonsmall" >GSL</a><br>
        <a href="/portfolio/?symfilter=KMB" class="buttonsmall">KMB</a> 
        <a href="/portfolio/?symfilter=LKOR" class="buttonsmall">LKOR</a>
        <a href="/portfolio/?symfilter=MLN" class="buttonsmall">MLN</a> 
        <a href="/portfolio/?symfilter=REM" class="buttonsmall">REM</a> 
        <a href="/portfolio/?symfilter=SGOL" class="buttonsmall">SGOL</a>
        <a href="/portfolio/?symfilter=SOXX" class="buttonsmall">SOXX</a> 
        <a href="/portfolio/?symfilter=UFPI" class="buttonsmall">UFPI</a>
        <a href="/portfolio/?symfilter=VMC" class="buttonsmall">VMC</a> 
        </span>';
//        echo "total holdings for $filterterm<br>";
        $tclass="moved";
endif;

//data entry
$curdate=date("Y-m-d");
echo "<form action=\"index.php\" method=\"POST\">";
echo "<table >";
echo "<tr><th>account</th>
<th>date</th>
<th>symbol</th>
<th>action</th>
<th>units</th>
<th>price</th>
<th>fee</th>
</tr>";

echo "<tr>
<td><select name=\"acct\"><option value=\"CB\">CB</option><option value=\"RH\">RH</option><option value=\"FID\" selected>FID</option></select>
<td><input type=date size=8 id=\"date\" name=\"date\" value=$curdate></td>
<td><input type=text size=6 name=\"symbol\"></td>

<td><select name=\"type\">
    <option value=\"Buy\">Buy</option>
    <option value=\"Sell\">Sell</option>
    <option value=\"Div\">Div</option>
    </select>
</td>
<td><input type=text id=\"units\" name=\"units\" size=10>
<td><input type=text id=\"price\" name=\"price\" size=10>
<td><input type=text id=\"fee\" name=\"fee\" size=4>
<input type='hidden' name='app_action' value='new'>
<td><input type=submit></td>
</tr>";
echo "</table>";
echo "</form>";

//input processing

if (!empty($_POST)):
$date=$_POST["date"];
$acct=$_POST["acct"];
$type=$_POST["type"];
$units=$_POST["units"];
$price=$_POST["price"];
$symbol=$_POST["symbol"];
// echo "post triggered<br>";

// echo "<pre>"; print_r($_POST) ;  echo "</pre>";
$q = "INSERT into transactions (date_new,acct,xtype,units,price,symbol) VALUES ('$date','$acct','$type','$units','$price','$symbol')";
echo "query is $q<br>";
try {
$DBH = new PDO($dir);
$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

$STH=$DBH->prepare($q);
$STH->execute();
}
catch(PDOException $e) {
    $err=$e->getMessage();
    echo "$query $err I'm sorry, Dave. I'm afraid I can't do that.";
    file_put_contents('/tmp/PDOErrors.txt', $e->getMessage(), FILE_APPEND);
}
endif;
?>

<div class="toggles">

<span class="toggle" onClick="$('.Buy').toggle();">Buy</span>
<span class="toggle" onClick="$('.Sell').toggle();">Sell</span>
<span class="toggle" onClick="$('.Div').toggle();">Div</span>
</div>

<?php
//table view
echo "<table class=$tclass>";
echo "<th>id</th><th>acct.</th>
<th>date</th>
<th>type</th>
<th>symbol</th>
<th>price</th>
<th>units</th>
<th>cost</th>
";



// main tabular transaction list output
foreach ($dbh->query($query) as $row) {
    $x=$row[xtype];
    $g=$c[$x];
    $sym=$row[symbol];
    $symbolcolor=$sc[$sym];
    $textcolor=$tc[$sym];
    $cost = round($row[ccost],4);
    $units=(round($row[units],3));
    echo "<tr class=\"$x\">
    <td style=\"padding-right: 2vw;\">$row[id]</td><td>$row[acct]</td>
    <td>$row[date_new]</td>
    <td class='cntr' style=\"background: $g; color:#000000; \">$row[xtype]</td> 
    <td class='cntr' style=\"text-align: center;background: $symbolcolor; \">
    <a href=\"?symfilter=$row[symbol]\" style=\"color: $textcolor;\">$row[symbol]</a></td> 
    <td>$row[price]</td>
    <td>$units</td>
    <td style=\"padding-left: 2vw;\">\$$cost</td>";
}

echo "</table>";

if (!empty($_GET['symfilter'])):
    $sym = $_GET['symfilter'];
    $query = "select sum(units) as buyunits from transactions where symbol = '$sym' and xtype = 'Buy'";
    $stmt = $dbh->prepare($query);
    $stmt->execute();
    $zrow = $stmt->fetch();
    $buyunits = $zrow['buyunits'];
    $query = "select sum(units) as sellunits from transactions where symbol = '$sym' and xtype = 'Sell'";
    $stmt = $dbh->prepare($query);
    $stmt->execute();
    $zrow = $stmt->fetch();
    $sellunits = $zrow['sellunits'];
    $netunits = ($buyunits - $sellunits);
    $query = "select price from prices where symbol = '$sym'";$stmt = $dbh->prepare($query);$stmt->execute();
    $zrow = $stmt->fetch();
    $price = $zrow['price'];
    $value = ($price * $netunits);
    
    echo "<span class=status2>bought $buyunits sold $sellunits<br>";
    echo "net units $netunits<br>";
    echo "current price \$$price<br>";
    echo "$sym position value \$$value</span>";
endif;

?>
<script>

    var x = "<?php echo"$filterterm"?>";

    $.ajax({
    url: 'datacsv.php?q=secprices&symbol='+x,
    type: 'GET',
    dataType: 'json',
    
    success:function(data){
    const rvalue=Math.floor(Math.random() * 235);
    const gvalue=Math.floor(Math.random() * 235);
    var bvalue=Math.floor(Math.random() * 235);
    var bgstring = 'rgba(' + rvalue + ',' + gvalue + ',' + bvalue + ',.5)';
    Chart.defaults.datasets.line.borderWidth = .2;
    Chart.defaults.animation.duration = 225;
    
        var close = [];
        var date = [];
        
        for(var i in data){
        close.push(parseFloat(data[i].posvalue));
        date.push(data[i].date);
            }
        
    var totalSum = 0; 
    for (var z in close) {
        var v=parseFloat(close[z]);
        totalSum += v; 
    }
    
        var std=getStandardDeviation (close);         
                    
        var numsCnt = close.length;
        
        var avg = (totalSum / numsCnt);
        
        var std1= (avg + (std*1));
        var std2H = (avg + (std*2));
        var std1L= (avg - (std*1));
        var std2L= (avg - (std*2));
        var std3L= (avg - (std*3));
        
        
//         console.log(avg,std,std1);
        
        var chartdata = {
        labels: date,
        datasets: [{
            label: 'Value',
            backgroundColor: bgstring,
//             borderWidth: .2,
            radius: 1.5 ,
            spanGaps: true,
            data:close,
            }]
        };
        
//         var ctx = $('#VMC');

// console.log(item, index);


        var ctx = $(chart);
        var dv = 0;
        var h2;
        
        var m = Math.min(...close);
        var mx = Math.max(...close);
        console.log("min",m, "std3L",std3L);
        console.log("max",mx, "std2H",std2H);
        
        if (m < std3L) {
        dv = 1;
        console.log("below std3",dv);
        } else dv = 0;
        
        if (mx > std2H) {
        h2=1;
        console.log("above std2H",h2);
        }
        
        
        var barGraph = new Chart(ctx, {
            type:'line',
            data: chartdata, 
            
            options: {
            
            fill: true,
            maintainAspectRatio: false,
                plugins: {
                annotation: {
                    annotations: {
        
                        line2: {
                            type: 'line', 
                            borderColor: 'rgb(16, 16, 16)',
//                             borderWidth:  .5,
                            enabled: true,
                            scaleID: 'y',
                            value: avg,
                            label: {
                                backgroundColor: 'rgba(0,0,255,.5)',
                                content: 'mean',
                                position: 'start',
                                enabled: true
                                },
                            },
                        line3: {
                            type: 'line', 
                            borderColor: 'rgb(232, 32, 32)',
                            borderWidth: .75,
                            enabled: true,
                            scaleID: 'y',
                            value: std1,
                            label: {
                                backgroundColor: 'rgba(255,0,0,.5)',
                                content: '1x stdev',
                                position: 'start',
                                enabled: true
                                },
                            },
                            line4: {
                            type: 'line', 
                            borderColor: 'rgb(232, 32, 32)',
                            borderWidth: .75,
                            enabled: true,
                            scaleID: 'y',
                            value: std1L,
                            label: {
                                backgroundColor: 'rgba(255,0,0,.5)',
                                content: '1x stdev',
                                position: 'start',
                                enabled: true
                                },
                            },
                            line5: {
                            type: 'line', 
                            borderColor: 'rgb(24, 240, 24)',
                            borderWidth: .75,
                            enabled: true,
                            scaleID: 'y',
                            value: std2L,
                            label: {
                                backgroundColor: 'rgba(24, 240, 24,.5)',
                                content: '2x stdev',
                                position: 'start',
                                enabled: true
                                },
                            },
                            line6: {
                            type: 'line', 
                            display: dv,
                            borderColor: 'rgb(64, 64, 192)',
                            borderWidth: 1,
                            enabled: dv,
                            scaleID: 'y',
                            value: std3L,
                            label: {
                                backgroundColor: 'rgba(64, 64, 128,.5)',
                                content: '3x stdev',
                                position: 'start',
                                enabled: true
                                },
                            },
                            
                            line7: {
                            type: 'line', 
                            display: h2,
                            borderColor: 'rgb(64, 64, 192)',
                            borderWidth: 1,
                            enabled: h2,
                            scaleID: 'y',
                            value: std2H,
                            label: {
                                backgroundColor: 'rgba(64, 64, 128,.5)',
                                content: '2x stdev',
                                position: 'start',
                                enabled: true
                                },
                            },
                            
                   
                        }
                    },
                title: {text: x,display: true},
                legend: {display: false},
                responsive: true,
                scales: {
                        x: {type: 'time'},
                        y: {min: 0}
                            }
                        }        
            }
        });
        

        
        
        
        } // end success func

    }); //end ajax object

    
    
function getStandardDeviation (array) {
const n = array.length
const mean = array.reduce((a, b) => a + b) / n
return Math.sqrt(array.map(x => Math.pow(x - mean, 2)).reduce((a, b) => a + b) / n)
}   
    
</script>
</body>
</html>
