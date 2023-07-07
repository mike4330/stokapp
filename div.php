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
    <td><canvas id="mychart" style="width:46vw;height:44vh;">"</canvas></td>
    <td><canvas id="valuechart" style="width:46vw;height:44vh;"></canvas></td>
    </tr>
    
    <tr>
    <td><canvas id="quarterdivs" style="width:46vw;height:44vh;"></canvas></td>
    <td><canvas id="averageschart" style="width: 46vw;height:44vh;"></canvas></td>
    </tr></table>

    <script>
        
$.ajax({
      url: 'data.php?q=divs',
      type: 'GET',
      dataType: 'json',
      success:function(data){

        var cost = [];
        var p = [];

        for(var i in data){
          cost.push(data[i].cost);
          p.push(data[i].p);
        }

        var chartdata = {
            labels: p,
            datasets: [
              {
                label: 'Div Amount',
                backgroundColor: 'rgb(90, 90, 132)',
                data:cost
              }
            ] 
        };

        var ctx = $('#mychart');
        var barGraph = new Chart(ctx, {
            type:'bar',
            data: chartdata,  
            options: {responsive: false}
        });
      },
    });
    
  $.ajax({
    url: 'data.php?q=valuetrend',
    type: 'GET',
    dataType: 'json',
    success:function(data){
    
        var value = [];var date = [];var cost = [];
         
         for(var i in data){
          date.push(data[i].date);
          value.push(data[i].value);
          cost.push(data[i].cost);
        }
    
        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'Value',
                
                radius: 2,
                borderColor: 'rgb(255, 0, 0)',
                borderWidth: 1,
                data:value
              },
            {
                label: 'Cost',
                borderColor: 'rgb(0, 0, 255)',
                borderWidth: 1,
                radius: 0,
                data:cost
              }
            ] 
            };
            
        var ctx = $('#valuechart');
        var barGraph = new Chart(ctx, {
            type:'line',
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
    success:function(data){
    
        var WMA8 = [];var date = [];
        var WMA24 = []; var WMA28 = []; var WMA36=[]; var WMA41=[]; 
        var WMA48=[];var rtn=[]; var WMA64=[]; var WMA72=[]; var WMA88=[];
         
         for(var i in data){
          date.push(data[i].date);
          WMA8.push(data[i].WMA8);WMA24.push(data[i].WMA24);
          WMA28.push(data[i].WMA28);WMA36.push(data[i].WMA36);
          WMA41.push(data[i].WMA41);WMA48.push(data[i].WMA48);
          WMA64.push(data[i].WMA64);
          WMA72.push(data[i].WMA72); WMA88.push(data[i].WMA88);
          rtn.push(data[i].rtn);
        }

        var chartdata = { 
          labels: date,
            datasets: [
              {
                label: '8WMA',
                radius: 0,
                borderColor: 'rgb(255, 0, 0)',
                borderWidth: 2.5,
                data: WMA8
              },
              {
                label: '48WMA',
                radius: 0,
                borderColor: 'rgb(32, 54, 240)',
                borderWidth: 2.5,
                data: WMA48
              },

               {
                label: 'return',
                radius: 1.5,
                borderColor: 'rgb(32, 32, 32)',
                borderWidth: 1,
                data: rtn
              },
 
                    {
                label: 'WMA28',
                radius: 0,
                borderColor: 'rgb(255,255,0)',
                borderWidth: 2.5,
                data: WMA28
              },
                     {
                label: 'WMA88',
                radius: 0,
                borderColor: 'rgb(30,225,30)',
                borderWidth: 2.5,
                data: WMA88
              }
            ] 
            };
            
            
        var ctx = $('#averageschart');        
        var barGraph = new Chart(ctx, {
            type:'line',
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
    success:function(data){
    
        var q = [];var total = [];
        
         
         for(var i in data){
          q.push(data[i].q);
          total.push(data[i].total);
        }
    
        var chartdata = {
            labels: q,
            datasets: [
              {
                label: 'total',
                radius: 0,
                backgroundColor: 'rgb(16, 192, 16)',
                data: total
              }
            ] 
            };
            
            
        var ctx = $('#quarterdivs');
        
        
        var barGraph = new Chart(ctx, {
            type:'bar',
            data: chartdata,  
            options: {
                responsive: false,
            }
        });
    }
  
  }); 
 
 </script>
</body>
</html>
