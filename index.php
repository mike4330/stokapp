<!-- Copyright (C) 2022 Mike Roetto <mike@roetto.org>
 SPDX-License-Identifier: GPL-3.0-or-later-->


<!DOCTYPE html>
<html lang="en">

<head>
    <title>portfolio</title>
    <link rel="stylesheet" type="text/css" href="main.css">
    <link rel="stylesheet" type="text/css" href="nav.css">
    <script src="/js/jquery-3.1.1.min.js"></script>
    <script type="text/javascript" src="/js/chart.js"></script>
    <script type="text/javascript" src="/js/luxon.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@^1"></script>
    <script src="/js/node_modules/chartjs-plugin-annotation/dist/chartjs-plugin-annotation.min.js"></script>
</head>

<body>

  <?php
include_once "nav.php";

$c = [
    'Buy' => "#11cc11",
    'Div' => "#eefb0b",
    'Sell' => "#dd3333"
];

try {
    $dir = 'sqlite:portfolio.sqlite';
    $dbh = new PDO($dir);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

$query = "SELECT symbol FROM prices";


    foreach ($dbh->query($query) as $row) {
        $symbol = $row['symbol'];
        $sc[$symbol] = $colors[$x];
        $tc[$symbol] = $contrasting_colors[$x];
        $x++;
    }

    //echo "db ok<br>";

    $query = "SELECT *,(price*units) as ccost from transactions order by date_new desc,id desc";

    if (!empty($_GET['symfilter'])) :
        $filterterm = $_GET['symfilter'];
        // echo "title filter $filterterm<br>"; 
        $query = "SELECT *,(price*units) as ccost from transactions where symbol = '$filterterm' order by id desc";
        echo '<a href="/portfolio" class="button1">🗑 reset filter</a><br>';
        echo '<div class="minichart" ><canvas id="chart" ></canvas></div>';
        echo '<table class=smallnav>';
        $subquery = "select symbol from prices where class IS NOT NULL order by symbol";
        echo "<tr>";
        foreach ($dbh->query($subquery) as $row) {
            $symbol = $row['symbol'];
            $i++;

            echo  "<td><a href=\"/portfolio/?symfilter=$symbol\" class=\"buttonsmall\">$symbol</a></td>";
            if ($i > 7) {
                echo "</tr>";
                $i = 0;
            }
        }
        echo "</table>";

        //        echo "total holdings for $filterterm<br>";
        $tclass = "moved";
    endif;

    //data entry
    $curdate = date("Y-m-d");
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
<td><select name=\"acct\">
<option value=\"CB\">CB</option>
<option value=\"IB\">IB</option>
<option value=\"RH\">RH</option>
<option value=\"FIDRI\" >FIDRI</option>
<option value=\"FID\" selected>FID</option>

</select>
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

    if (!empty($_POST)) :
        $date = $_POST["date"];
        $acct = $_POST["acct"];
        $type = $_POST["type"];
        $units = $_POST["units"];
        $price = $_POST["price"];
        $symbol = $_POST["symbol"];
        // echo "post triggered<br>";

        // echo "<pre>"; print_r($_POST) ;  echo "</pre>";
        $q = "INSERT into transactions (date_new,acct,xtype,units,price,symbol) VALUES ('$date','$acct','$type','$units','$price','$symbol')";
        echo "query is $q<br>";
        try {
            $DBH = new PDO($dir);
            $DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $STH = $DBH->prepare($q);
            $STH->execute();
        } catch (PDOException $e) {
            $err = $e->getMessage();
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
// create an array to store the table headers
$headers = array(
    "id",
    "acct.",
    "date",
    "type",
    "symbol",
    "price",
    "units",
    "cost"
);

// output table headers using a foreach loop
echo "<table class='transactions'>";
echo "<tr>";

foreach ($headers as $header) {
    echo "<th>$header</th>";
}

echo "</tr>";

// loop through query results and output corresponding data in table format
foreach ($dbh->query($query) as $row) {
    $x = $row['xtype'];
    $g = $c[$x];
    $sym = $row['symbol'];
    $symbolcolor = $sc[$sym];
    $textcolor = $tc[$sym];
    $cost = round($row['ccost'], 4);
    $units = round($row['units'], 3);

    // output table row
    echo "<tr class='$x'>";
    echo "<td style='padding-right: 2vw;'>{$row['id']}</td>";
    echo "<td>{$row['acct']}</td>";
    echo "<td>{$row['date_new']}</td>";
    echo "<td class='cntr' style='background: $g; color:#000000;'>$row[xtype]</td>";
    echo "<td class='cntr' style='text-align: center;background: $symbolcolor;'>";
    echo "<a href='?symfilter={$row['symbol']}' style='color: $textcolor;'>{$row['symbol']}</a>";
    echo "</td>";
    echo "<td>{$row['price']}</td>";
    echo "<td>$units</td>";
    echo "<td style='padding-left: 2vw;'>\${$cost}</td>";
    echo "</tr>";
}

echo "</table>";


    if (!empty($_GET['symfilter'])) :
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
        $query = "select price from prices where symbol = '$sym'";
        $stmt = $dbh->prepare($query);
        $stmt->execute();
        $zrow = $stmt->fetch();
        $price = $zrow['price'];
        $value = round(($price * $netunits), 2);

        echo "<div class=status2>bought $buyunits sold $sellunits<br>";
        echo "net units $netunits<br>";
        echo "current price \$$price<br>";
        echo "$sym position value \$$value</div>";
    endif;

    ?>
    <script>
        var x = "<?php echo "$filterterm" ?>";

        $.ajax({
            url: 'datacsv.php?q=secprices&symbol=' + x,
            type: 'GET',
            dataType: 'json',

            success: function(data) {
                const rvalue = Math.floor(Math.random() * 235);
                const gvalue = Math.floor(Math.random() * 235);
                var bvalue = Math.floor(Math.random() * 235);
                var bgstring = 'rgba(' + rvalue + ',' + gvalue + ',' + bvalue + ',.5)';
                Chart.defaults.datasets.line.borderWidth = .2;
                Chart.defaults.animation.duration = 225;
                Chart.overrides.line.tension = 0.1;

                var close = [];
                var date = [];

                for (var i in data) {
                    close.push(parseFloat(data[i].posvalue));
                    date.push(data[i].date);
                }

                var totalSum = 0;
                for (var z in close) {
                    var v = parseFloat(close[z]);
                    totalSum += v;
                }

                var std = getStandardDeviation(close);
                var numsCnt = close.length;
                var avg = (totalSum / numsCnt);
                var std1 = (avg + (std * 1));
                var std2H = (avg + (std * 2));
                var std1L = (avg - (std * 1));
                var std2L = (avg - (std * 2));
                var std3L = (avg - (std * 3));

                //         console.log(avg,std,std1);

                var chartdata = {
                    labels: date,
                    datasets: [{
                        label: 'Value',
                        backgroundColor: bgstring,
                        borderWidth: 1.1,
                        borderColor: 'rgb(8, 8, 8)',
                        radius: 0,
                        spanGaps: true,
                        data: close,
                    }]
                };

                //         var ctx = $('#VMC');
                // console.log(item, index);
                var ctx = $(chart);
                var dv = 0;
                var h2;

                var m = Math.min(...close);
                var mx = Math.max(...close);
                console.log("min", m, "std3L", std3L);
                console.log("max", mx, "std2H", std2H);

                if (m < std3L) {
                    dv = 1;
                    console.log("below std3", dv);
                } else dv = 0;

                if (mx > std2H) {
                    h2 = 1;
                    console.log("above std2H", h2);
                }

                var barGraph = new Chart(ctx, {
                    type: 'line',
                    data: chartdata,
                    options: {
                        fill: true,
                        maintainAspectRatio: false,
                        plugins: {
                            annotation: {
                                annotations: {

                                    line2: {
                                        type: 'line',
                                        borderColor: 'rgb(16, 16, 240)',
                                        borderWidth: .75,
                                        enabled: true,
                                        scaleID: 'y',
                                        value: avg,
                                        label: {
                                            backgroundColor: 'rgba(0,0,255,.9)',
                                            padding: 2,
                                            content: 'mean',
                                            position: 'start',
                                            enabled: true,
                                            borderRadius: 2
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
                                            padding: 2,
                                            position: 'start',
                                            enabled: true,
                                            borderRadius: 2
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
                            title: {
                                text: x,
                                display: true
                            },
                            legend: {
                                display: false
                            },
                            responsive: true,
                            scales: {
                                x: {
                                    type: 'time'
                                },
                                y: {
                                    min: 0
                                }
                            }
                        }
                    }
                });

            } // end success func

        }); //end ajax object



        function getStandardDeviation(array) {
            const n = array.length
            const mean = array.reduce((a, b) => a + b) / n
            return Math.sqrt(array.map(x => Math.pow(x - mean, 2)).reduce((a, b) => a + b) / n)
        }
    </script>
</body>

</html>