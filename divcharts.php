<!--Copyright (C) 2022 Mike Roetto <mike@roetto.org>
SPDX-License-Identifier: GPL-3.0-or-later-->
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
  include("nav.php");
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

  <table class=chart style="position: absolute;left: 8vw;">
    <tr>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="ANGL"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="ASML"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="AVGO"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="BG"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="BRT"></canvas></div>
      </td>

    </tr>

    <tr>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="D"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="DGX"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="EMB"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="EVC"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="F"></canvas></div>
      </td>

    </tr>

    <tr>
    <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="FAF"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="FAGIX"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="FNBGX"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="FPE"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="FTS"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="GILD"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="HUN"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="IPAR"></canvas></div>
      </td>
    </tr>

    <tr>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="JPIB"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="KMB"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="LKOR"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="LYB"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="MLN"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="NXST"></canvas></div>
      </td>

    </tr>
    <tr>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="PBR"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="PLD"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="PNM"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="REM"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="SCI"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="SSNC"></canvas></div>
      </td>


    </tr>
    <tr>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="TAIT"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="VCSH"></canvas></div>
      </td>
      <td>
        <div class="chart-container3" style="position: relative;  "><canvas id="VMC"></canvas></div>
      </td>
    </tr>
  </table>

  <script>

    Chart.defaults.plugins.legend.display = false;
    Chart.defaults.datasets.line.fill = true;
    Chart.defaults.datasets.line.borderWidth = 1.2;
    Chart.defaults.animation.duration = 225;
    Chart.defaults.datasets.line.pointRadius = 1.2;

    c1 = 'rgb(224, 224, 32)'


    const chartDataList = ['ANGL', 'ASML', 'AVGO', 'BG', 'BRT', 'D', 'DGX', 'EMB', 'EVC', 'F', 'FAF', 'FAGIX',
      'FNBGX', 'FPE', 'FTS', 'GILD', 'HUN', 'IPAR', 'JPIB', 'KMB', 'LKOR', 'LYB', 'MLN', 'NXST', 'PBR', 'PLD', 'PNM',
      'REM', 'SCI', 'SSNC', 'VCSH', 'VMC', 'TAIT'];
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
            return `rgba(${r}, ${g}, ${b}, 0.5)`;
          };
          const backgroundColor = randomColor()

          var chartData = {
            labels: date,
            datasets: [{
              label: 'Div Amount',
              backgroundColor: randomColor(),
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
                  padding: { top: 15 }
                }
              },
              maintainAspectRatio: false,
              responsive: true
            }
          });
        }
      });
    }



  </script>
</body>

</html>