<!--Copyright (C) 2022 Mike Roetto <mike@roetto.org>
SPDX-License-Identifier: GPL-3.0-or-later-->
<!DOCTYPE html>
<html>

<head>
  <link rel="stylesheet" type="text/css" href="main.css">
  <link rel="stylesheet" type="text/css" href="nav.css">
  <title>Dividends</title>

  <!-- <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script> -->
  <script src="/js/jquery-3.1.1.min.js"></script>
  <script type="text/javascript" src="/js/chart.js"></script>

</head>

<body>

  <?php
  $dir = 'sqlite:portfolio.sqlite';
  include ("nav.php");
  ?>
  <table class="chart2">
    <tr class="chart">
      <td><canvas id="mychart" style="width:47vw;height:44vh;">"</canvas></td>
      <td><canvas id="valuechart" style="width:47vw;height:44vh;"></canvas></td>
    </tr>

    <tr>
      <td><canvas id="quarterdivs" style="width:47vw;height:44vh;"></canvas></td>
      <td><canvas id="averageschart" style="width: 47vw;height:44vh;"></canvas></td>
    </tr>
  </table>

  <script>
    $.ajax({
      url: 'data.php?q=divs',
      type: 'GET',
      dataType: 'json',
      success: function (data) {

        var cost = [];
        var p = [];

        for (var i in data) {
          cost.push(data[i].cost);
          p.push(data[i].p);
        }

        var chartdata = {
          labels: p,
          datasets: [
            {
              label: 'Div Amount',
              backgroundColor: 'rgb(88, 88, 200)',
              data: cost
            }
          ]
        };

        var ctx = $('#mychart');
        var barGraph = new Chart(ctx, {
          type: 'bar',
          data: chartdata,
          options: { responsive: false }
        });
      },
    });

    $.ajax({
      url: 'data.php?q=valuetrend',
      type: 'GET',
      dataType: 'json',
      success: function (data) {

        var value = []; var date = []; var cost = [];

        for (var i in data) {
          date.push(data[i].date);
          value.push(data[i].value);
          cost.push(data[i].cost);
        }

        var chartdata = {
          labels: date,
          datasets: [
            {
              label: 'Value',
              radius: 0,
              borderColor: 'rgb(255, 0, 0)',
              borderWidth: 1,
              tension: .9,
              data: value
            },
            {
              label: 'Cost',
              borderColor: 'rgb(0, 0, 255)',
              borderWidth: 1,
              radius: 0,
              data: cost
            }
          ]
        };

        var ctx = $('#valuechart');
        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            responsive: false,
          }
        });
      }
    });

    $.ajax({
      url: 'data.php?q=averages',
      type: 'GET',
      dataType: 'json',
      success: function (data) {

        var date = [];
        var WMA24 = []; var WMA28 = []; var WMA36 = []; var WMA41 = [];
        var WMA48 = []; var rtn = []; var WMA64 = []; var WMA72 = [];
        var WMA88 = []; var WMA110 = []; var WMA135 = []; var YMA3=[]; var YMA2=[]; var YMA1=[]; var YMA4=[];

        for (var i in data) {
          date.push(data[i].date);
          WMA24.push(data[i].WMA24);
          WMA28.push(data[i].WMA28); WMA36.push(data[i].WMA36);
          WMA41.push(data[i].WMA41); WMA48.push(data[i].WMA48);
          WMA64.push(data[i].WMA64);
          WMA72.push(data[i].WMA72); WMA88.push(data[i].WMA88); WMA110.push(data[i].WMA110);
          WMA135.push(data[i].WMA135);
          YMA4.push(data[i].YMA4);
          YMA3.push(data[i].YMA3);
          YMA2.push(data[i].YMA2);
          YMA1.push(data[i].YMA1);
          rtn.push(data[i].rtn);
        }

        var chartdata = {
          labels: date,
          datasets: [
            {
              label: 'return',
              radius: 1.5,
              borderColor: 'rgb(32, 32, 32)',
              borderWidth: 1,
              data: rtn
            },
            {
              label: '24WMA',
              radius: 0,
              borderColor: 'rgb(0, 0, 128)',
              borderWidth: 2.5,
              data: WMA24
            },
            {
              label: 'YMA1',
              radius: 0,
              borderColor: 'rgb(255, 0, 0)',
              borderWidth: 2.5,
              data: YMA1
            },
            {
              label: 'YMA2',
              radius: 0,
              borderColor: 'rgb(30,190,30)',
              borderWidth: 2.5,
              data: YMA2
            },

            {
              label: 'YMA3',
              radius: 0,
              borderColor: 'rgb(198,178,0)',
              borderWidth: 2.5,
              data: YMA3
            },
            {
              label: 'YMA4',
              radius: 0,
              borderColor: 'rgb(20, 177, 250)',
              borderWidth: 2.5,
              data: YMA4
            }
          ]
        };

        var ctx = $('#averageschart');
        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            responsive: false,
          }
        });
      }

    });

    $.ajax({
      url: 'data.php?q=quarterdivs',
      type: 'GET',
      dataType: 'json',
      success: function (data) {

        var q = []; var total = [];


        for (var i in data) {
          q.push(data[i].q);
          total.push(data[i].total);
        }

        var chartdata = {
          labels: q,
          datasets: [
            {
              label: 'total',
              radius: 0,
              backgroundColor: 'rgb(16, 172, 16)',
              data: total
            }
          ]
        };

        var ctx = $('#quarterdivs');
        var barGraph = new Chart(ctx, {
          type: 'bar',
          data: chartdata,
          options: {
            responsive: false,
            scales: {
            }
          }
        });
      }

    });

  </script>
</body>

</html>
