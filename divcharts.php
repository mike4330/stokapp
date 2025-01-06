<!--Copyright (C) 2022,2024 Mike Roetto <mike@roetto.org>
SPDX-License-Identifier: GPL-3.0-or-later-->
<!DOCTYPE html>
<html>

<head>
  <link rel="stylesheet" type="text/css" href="main.css">
  <link rel="stylesheet" type="text/css" href="nav.css">
  <title>Dividend Amounts over Time</title>
  <script src="/js/jquery-3.1.1.min.js"></script>
  <script type="text/javascript" src="/js/chart.js"></script>
  <!-- <script src="https://cdn.jsdelivr.net/npm/luxon@^2"></script> -->
  <script type="text/javascript" src="/js/luxon.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@^1"></script>
</head>

<body>
  <?php
  include ("nav.php");
  $dir = 'sqlite:portfolio.sqlite';
  $dbh = new PDO($dir) or die("cannot open the database");

  $query = "select symbol,dividend_date,expected_amount from aux_attributes where dividend_date LIKE '%-%' and dividend_date >=  date('now') order by dividend_date";

  echo "<div class=divboard style=\"font-family: Monospace;\">Upcoming Dividends<br>";
  foreach ($dbh->query($query) as $row) {
    echo "<span style=\"display: inline-block;border: 2px solid #119911;padding: 2px;margin-bottom: 4px;margin-left:5px;\" >$row[dividend_date] $row[symbol] </span><br>";
    $ix++;
    #    if ($ix > 4) {echo "<br>";$ix=0;}
  }
  echo "</div><br>";
  ?>

  <?php
  // $symbols=['AMX','ANGL','ASML','AVGO','BAH','BG','BRT','BRKB','BSIG','CARR','C','D','DBB','DGX','EMB',
// 'EVC','EWJ','F','FAF','FAGIX','FDGFX','FNBGX','FPE','FTS','GILD','HPK','HTLD','HUN','INGR',
// 'IPAR','JPIB','KMB','LKOR','LYB','MLN','MPW','NHC','NICE','NXST','NVS','OTIS','PANW','PBR','PDBC','PLD',
// 'PNM','REM','SCI','SGOL','SIVR','SSNC','TAIT','TGS','TSLA','ULTA','VCSH','VMC'];
  
  $symbols = [
    'AMX',
    'ANGL',
    'ASML',
    'AVGO',
    'BAH',
    'BRT',
    'CARR',
    'D',
    'DGX',
    'EVC',
    'EWJ',
    'FAF',
    'FAGIX',
    'FDGFX',
    'FNBGX',
    'FTS',
    'HPK',
    'HUN',
    'INGR',
    'IPAR',
    'JPIB',
    'KMB',
    'LKOR',
    'LYB',
    'MLN',
    'MPW',
    'NHC',
    'NXST',
    'PBR',
    'PGHY',
    'PLD',
    'TXNM',
    'REM',
    'SCI',
    'SJNK',
    'SSNC',
    'TAIT',
    'TDTF',
    'VALE',
    'VCSH',
    'VMC'
  ];
  ?>

  <div class="grid-container2">

    <?php
    foreach ($symbols as $symbol) {
      echo '<div  class="chart-container99" >';
      echo "<canvas class=\"chart-canvas\" id=\"$symbol\"></canvas></div>";
    }
    ?>
  </div>

  <script>
    Chart.defaults.plugins.legend.display = false;
    Chart.defaults.datasets.line.fill = true;
    Chart.defaults.datasets.line.borderWidth = 1.2;
    Chart.defaults.animation.duration = 225;
    Chart.defaults.datasets.line.pointRadius = 1.2;
    Chart.defaults.font.size = 8;

    c1 = 'rgb(224, 224, 32)'

    const chartDataList = ['AMX', 'ANGL', 'ASML', 'AVGO', 'BAH', 'BRT', 'CARR', 'C', 'D', 'DGX', 'EMB', 'EVC', 'EWJ', 'F', 'FAF',
      'FAGIX', 'FDGFX', 'FNBGX', 'FPE', 'FTS', 'GILD', 'HPK', 'HTLD', 'HUN', 'INGR', 'IPAR', 'JPIB', 'KMB', 'LKOR', 'LYB', 'MLN', 'MPW',
      'NHC', 'NXST', 'PBR', 'PGHY', 'PLD', 'TXNM', 'REM', 'SCI', 'SJNK', 'SSNC', 'VCSH', 'VMC', 'TAIT', 'TDTF','VALE'
    ];

    $.ajaxSetup({
      pool: true
    });

    for (let i = 0; i < chartDataList.length; i++) {
      loadChartData(chartDataList[i]);
    }

    function loadChartData(item) {
      $.ajax({
        url: `datacsv.php?symquery=${item}`,
        type: 'GET',
        dataType: 'json',
        success: function (data) {
          var cost = [];
          var date = [];

          for (var i in data) {
            cost.push(data[i].cost);
            date.push(data[i].date);
          }

          const randomColor = () => {
            const r = Math.floor(Math.random() * 226);
            const g = Math.floor(Math.random() * 226);
            const b = Math.floor(Math.random() * 226);
            return `rgba(${r}, ${g}, ${b}, 0.6)`;
          };
          const backgroundColor = randomColor()

          var chartData = {
            labels: date,
            datasets: [{
              label: 'Div Amount',
              // backgroundColor: randomColor(),
              backgroundColor: (context) => {
                const ctx = context.chart.ctx;
                const gradient = ctx.createLinearGradient(0, 0, 0, 200);
                const r = Math.floor(Math.random() * 226);
                const g = Math.floor(Math.random() * 226);
                const b = Math.floor(Math.random() * 226);
                gradient.addColorStop(0, "rgba(0,128,0,1)");
                gradient.addColorStop(1, "rgba(0, 128, 32,0.2)");
                return gradient;
              },

              borderColor: 'rgb(65, 65, 65)',
              tension: 0.15,
              data: cost
            }]
          };

          var canvasId = `#${item}`;
          var ctx = $(canvasId);

          var barGraph = new Chart(ctx, {
            type: 'line',
            data: chartData,
            fill: true,
            options: {
              plugins: {
                title: {
                  text: item,
                  display: true,
                  padding: {
                    top: 15
                  }
                }
              },
              maintainAspectRatio: true,
              responsive: true
            }
          });
        }
      });
    }
  </script>
</body>

</html>
