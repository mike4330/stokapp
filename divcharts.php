<!--Copyright (C) 2022 Mike Roetto <mike@roetto.org>
SPDX-License-Identifier: GPL-3.0-or-later-->
<html>
<head>
<link rel="stylesheet" type="text/css" href="main.css">
<link rel="stylesheet" type="text/css" href="nav.css">
<title>Position Values over Time</title>
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
$dbh  = new PDO($dir) or die("cannot open the database");

$query = "select symbol,dividend_date,expected_amount from aux_attributes where dividend_date LIKE '%-%' and dividend_date >=  date('now') order by dividend_date";

echo "<div class=statusmessage>Upcoming Dividends<br>";
foreach ($dbh->query($query) as $row) {
    echo "$row[dividend_date]  $row[symbol] <br>";
}
echo "</div><br>";
?>

<table class=chart>
<tr>
    <td><div class="chart-container3" style="position: relative;  "><canvas id="ANGL""></canvas></div></td>
    <td><div class="chart-container3" style="position: relative;  "><canvas id="GSL""></canvas></div></td>
    <td><div class="chart-container3" style="position: relative;  "><canvas id="LKOR""></canvas></div></td>
    <td><div class="chart-container3" style="position: relative;  "><canvas id="MLN""></canvas></div></td>
</tr>
<tr>
    <td><div class="chart-container3" style="position: relative;  "><canvas id="REM""></canvas></div></td>
    <td><div class="chart-container3" style="position: relative;  "><canvas id="SOXX""></canvas></div></td>
    <td><div class="chart-container3" style="position: relative;  "><canvas id="VMC""></canvas></div></td>
    <td><div class="chart-container3" style="position: relative;  "><canvas id="UFPI""></canvas></div></td>
</tr>
</table>

<script>

Chart.defaults.plugins.legend.display = false;

c1='rgb(224, 224, 32)'

$.ajax({
      url: 'datacsv.php?symquery=ANGL',
      type: 'GET',
      // this was what I needed to make it work.
      dataType: 'json',
      success:function(data){

        var cost = [];
        var date = [];

        for(var i in data){
          cost.push(data[i].cost);
          date.push(data[i].date);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'Div Amount',
                backgroundColor: c1,
                data:cost
                
              }
            ] 
        };

        var ctx = $('#ANGL');

        var barGraph = new Chart(ctx, {
            type:'bar',
            data: chartdata,  
            options: {
                plugins: {title: {text: "ANGL",display: true}},
                maintainAspectRatio: false,
                responsive: true}
            
        });
      },

    });
    

$.ajax({
      url: 'datacsv.php?symquery=GSL',
      type: 'GET',
      // this was what I needed to make it work.
      dataType: 'json',
      success:function(data){

        item = 'GSL';  
        var cost = [];
        var date = [];

        for(var i in data){
          cost.push(data[i].cost);
          date.push(data[i].date);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'Div Amount',
                backgroundColor: 'rgb(255, 99, 132)',
                data:cost
                
              }
            ] 
        };
        var iname = '#' + item;
        var ctx = $(iname);

        var barGraph = new Chart(ctx, {
            type:'bar',
            data: chartdata,  
            options: {
                plugins: {title: {text: item,display: true}},
                maintainAspectRatio: false,
                responsive: true,
                
            }
            
        });
      },

    });
    

$.ajax({
      url: 'datacsv.php?symquery=LKOR',
      type: 'GET',
      // this was what I needed to make it work.
      dataType: 'json',
      success:function(data){

        item = 'LKOR';  
        var cost = [];
        var date = [];

        for(var i in data){
          cost.push(data[i].cost);
          date.push(data[i].date);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'Div Amount',
                backgroundColor: 'rgb(255, 99, 132)',
                data:cost
                
              }
            ] 
        };
        var iname = '#' + item;
        var ctx = $(iname);

        var barGraph = new Chart(ctx, {
            type:'bar',
            data: chartdata,  
            options: {
                plugins: {title: {text: item,display: true}},
                maintainAspectRatio: false,
                responsive: true}
            
        });
      },

    });

$.ajax({
      url: 'datacsv.php?symquery=MLN',
      type: 'GET',
      // this was what I needed to make it work.
      dataType: 'json',
      success:function(data){

        item = 'MLN';  
        var cost = [];
        var date = [];

        for(var i in data){
          cost.push(data[i].cost);
          date.push(data[i].date);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'Div Amount',
                backgroundColor: 'rgb(32, 99, 132)',
                data:cost
                
              }
            ] 
        };
        var iname = '#' + item;
        var ctx = $(iname);

        var barGraph = new Chart(ctx, {
            type:'bar',
            data: chartdata,  
            options: {
                plugins: {title: {text: item,display: true}},
                maintainAspectRatio: false,
                responsive: true}
            
        });
      },

    });

$.ajax({
      url: 'datacsv.php?symquery=VMC',
      type: 'GET',
      // this was what I needed to make it work.
      dataType: 'json',
      success:function(data){

        item = 'VMC';  
        var cost = [];
        var date = [];

        for(var i in data){
          cost.push(data[i].cost);
          date.push(data[i].date);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'Div Amount',
                backgroundColor: 'rgb(42, 120, 42)',
                data:cost
                
              }
            ] 
        };
        var iname = '#' + item;
        var ctx = $(iname);

        var barGraph = new Chart(ctx, {
            type:'bar',
            data: chartdata,  
            options: {
                plugins: {title: {text: item,display: true}},
                maintainAspectRatio: false,
                responsive: true}
            
        });
      },

    });

$.ajax({
      url: 'datacsv.php?symquery=UFPI',
      type: 'GET',
      // this was what I needed to make it work.
      dataType: 'json',
      success:function(data){

        item = 'UFPI';  
        var cost = [];
        var date = [];

        for(var i in data){
          cost.push(data[i].cost);
          date.push(data[i].date);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'Div Amount',
                backgroundColor: 'rgb(32, 32, 240)',
                data:cost
                
              }
            ] 
        };
        var iname = '#' + item;
        var ctx = $(iname);

        var barGraph = new Chart(ctx, {
            type:'bar',
            data: chartdata,  
            options: {
                plugins: {title: {text: item,display: true}},
                maintainAspectRatio: false,
                responsive: true}
            
        });
      },

    });

$.ajax({
      url: 'datacsv.php?symquery=REM',
      type: 'GET',
      // this was what I needed to make it work.
      dataType: 'json',
      success:function(data){

        item = 'REM';  
        var cost = [];
        var date = [];

        for(var i in data){
          cost.push(data[i].cost);
          date.push(data[i].date);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'Div Amount',
                backgroundColor: 'rgb(32, 32, 240)',
                data:cost
                
              }
            ] 
        };
        var iname = '#' + item;
        var ctx = $(iname);

        var barGraph = new Chart(ctx, {
            type:'bar',
            data: chartdata,  
            options: {
                plugins: {title: {text: item,display: true}},
                maintainAspectRatio: false,
                responsive: true}
            
        });
      },

    });

$.ajax({
      url: 'datacsv.php?symquery=SOXX',
      type: 'GET',
      // this was what I needed to make it work.
      dataType: 'json',
      success:function(data){

        item = 'SOXX';  
        var cost = [];
        var date = [];

        for(var i in data){
          cost.push(data[i].cost);
          date.push(data[i].date);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'Div Amount',
                backgroundColor: 'rgb(40, 255, 40)',
                data:cost
                
              }
            ] 
        };
        var iname = '#' + item;
        var ctx = $(iname);

        var barGraph = new Chart(ctx, {
            type:'bar',
            data: chartdata,  
            options: {
                plugins: {title: {text: item,display: true}},
                maintainAspectRatio: false,
                responsive: true}
            
        });
      },

    });


</script>

</body>
</html>
