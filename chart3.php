
<html>
<head>
<link rel="stylesheet" type="text/css" href="main.css">
<link rel="stylesheet" type="text/css" href="nav.css">
<title>Charts 3</title>
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
?>

<table class="chart">
<tr>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="ANGL" ></canvas></div></td>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="ASML" ></canvas></div></td>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="BEN" ></canvas></div></td>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="BRKB" ></canvas></div></td>
    
</tr>
<tr>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="C" ></canvas></div></td>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="DBB" ></canvas></div></td>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="EWJ" ></canvas></div></td>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="GILD" ></canvas></div></td>
</tr>
<tr>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="GSL" ></canvas></div></td>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="KHC" ></canvas></div></td>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="KMB" ></canvas></div></td>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="LKOR" ></canvas></div></td>

</tr>
<tr>
    
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="LNG" ></canvas></div></td>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="MLN" ></canvas></div></td>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="REM" ></canvas></div></td>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="SGOL" ></canvas></div></td>
    
 
</tr>
<tr>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="SOXX" ></canvas></div></td>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="UFPI" ></canvas></div></td>
    <td><div class="chart-container" style="position: relative;  width:23vw;height:22vh;"><canvas id="VMC" ></canvas></div></td>
</tr>
</table>

<script>

Chart.defaults.interaction.mode = 'nearest';
Chart.defaults.datasets.line.fill = true;
Chart.defaults.datasets.line.borderWidth = 1;
Chart.defaults.animation.duration = 225;
Chart.defaults.datasets.line.pointRadius = 2;

const array = ["ASML", "ANGL", "BRKB", "BEN",
"BSJN","C","DBB", "EWJ","GILD","GSL","KMB","KHC", "LKOR" , "LNG","MLN", 
"REM","SGOL","SOXX","VMC","UFPI"];

array.forEach(function (item, index) {

    
    $.ajax({
    url: 'datacsv.php?q=cumshares2&symbol='+item,
    type: 'GET',
    dataType: 'json',
    
    success:function(data){
    const rvalue=Math.floor(Math.random() * 235);
      const gvalue=Math.floor(Math.random() * 235);
      var bvalue=Math.floor(Math.random() * 235);
      var bgstring = 'rgba(' + rvalue + ',' + gvalue + ',' + bvalue + ',.5)';
    
        var cumshares = [];
        var date = [];
        
        for(var i in data){
          cumshares.push(data[i].cumshares);
          date.push(data[i].date);
            }
        
        var chartdata = {
        labels: date,
        datasets: [{
            label: 'Shares',
            backgroundColor: bgstring,
            
            data:cumshares,
            }]
        };
        
//         var ctx = $('#VMC');

console.log(item, index);

var iname = '#' + item;
        var ctx = $(iname);
        
        var barGraph = new Chart(ctx, {
            type:'line',
            data: chartdata,  
            options: {
            maintainAspectRatio: false,
            pointRadius: 0.85 ,
            stepped: true,
                plugins: {title: {text: item,display: true},
                legend: {display: false}},
                responsive: true,
                scales: {
                    x: {type: 'time'},
//                     y: {min: 0}
                    }
                }
        });
        
        } // end success func

    }); //end ajax object
    
}); //end loop
</script>

</body>


</html>
