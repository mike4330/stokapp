
<html>
<head>
<link rel="stylesheet" type="text/css" href="main.css">
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
?>

<table class="chart">
<tr>
    <td><div class="chart-container2" style="position: relative;  "><canvas id="ANGL" ></canvas></div></td>
    <td><div class="chart-container2" style="position: relative;  "><canvas id="ASML" ></canvas></div></td>
    <td><div class="chart-container2" style="position: relative;  "><canvas id="BEN" ></canvas></div></td>
    <td><div class="chart-container2" style="position: relative;  "><canvas id="BRKB" ></canvas></div></td>
    
    
</tr>
<tr>
    
    <td><div class="chart-container2" style="position: relative;  "><canvas id="DBB" ></canvas></div></td>
    <td><div class="chart-container2" style="position: relative;  "><canvas id="C" ></canvas></div></td>
    <td><div class="chart-container2" style="position: relative;  "><canvas id="EMB" ></canvas></div></td>
    <td><div class="chart-container2" style="position: relative;  "><canvas id="EWJ" ></canvas></div></td>
   
    
 
</tr>
<tr>
 <td><div class="chart-container2" style="position: relative;  "><canvas id="FNBGX" ></canvas></div></td>
    <td><div class="chart-container2" style="position: relative;  "><canvas id="GILD" ></canvas></div></td>
   <td><div class="chart-container2" style="position: relative;  "><canvas id="GSL" ></canvas></div></td>
   <td><div class="chart-container2" style="position: relative;  "><canvas id="JPIB" ></canvas></div></td>
  
   
   

</tr>
<tr>
 <td><div class="chart-container2" style="position: relative;  "><canvas id="KHC" ></canvas></div></td>
<td><div class="chart-container2" style="position: relative;  "><canvas id="KMB" ></canvas></div></td>
 <td><div class="chart-container2" style="position: relative;  "><canvas id="LKOR" ></canvas></div></td>
<td><div class="chart-container2" style="position: relative;  "><canvas id="LNG" ></canvas></div></td>


</tr>
<tr>
<td><div class="chart-container2" style="position: relative;  "><canvas id="MLN" ></canvas></div></td>
<td><div class="chart-container2" style="position: relative;  "><canvas id="MPW" ></canvas></div></td>
<td><div class="chart-container2" style="position: relative;  "><canvas id="PDBC" ></canvas></div></td>
<td><div class="chart-container2" style="position: relative;  "><canvas id="REM" ></canvas></div></td>



</tr>
<tr>
<td><div class="chart-container2" style="position: relative;  "><canvas id="SGOL" ></canvas></div></td>
<td><div class="chart-container2" style="position: relative;  "><canvas id="SOXX" ></canvas></div></td>
<td><div class="chart-container2" style="position: relative;  "><canvas id="UFPI" ></canvas></div></td>
<td><div class="chart-container2" style="position: relative;  "><canvas id="VMC" ></canvas></div></td>
</tr>
<tr>
<td><div class="chart-container2" style="position: relative;  "><canvas id="XAG" ></canvas></div></td>
</tr>
</table>

</body>

<script>

Chart.defaults.interaction.mode = 'nearest';
Chart.defaults.datasets.line.fill = true;
Chart.defaults.datasets.line.spanGaps = 1;
Chart.defaults.datasets.line.borderWidth = .9;
Chart.defaults.animation.duration = 225;
Chart.defaults.datasets.line.pointRadius = 0;

const array = ["ASML", "ANGL", "BEN", "BRKB","C","DBB", "EMB",
"EWJ","FNBGX","GILD","GSL","JPIB", "KHC","KMB", "LKOR" , "LNG", 
"MLN", "MPW", "PDBC","REM","SGOL","SOXX","VMC","UFPI" , "XAG"];

// const array = ["ASML", "ANGL", "BRKB"];

array.forEach(function (item, index) {

    
    $.ajax({
    url: 'datacsv.php?q=posvalues&symbol='+item,
    type: 'GET',
    dataType: 'json',
    
    success:function(data){
    const rvalue=Math.floor(Math.random() * 235);
    const gvalue=Math.floor(Math.random() * 235);
    var bvalue=Math.floor(Math.random() * 235);
    var bgstring = 'rgba(' + rvalue + ',' + gvalue + ',' + bvalue + ',.5)';
    
        var posvalue = [];
        var date = [];
        
        for(var i in data){
          posvalue.push(data[i].posvalue);
          date.push(data[i].date);
            }
        
        var chartdata = {
        labels: date,
        datasets: [{
            label: 'Value',
            backgroundColor: bgstring,
            spanGaps: true,
            data:posvalue,
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


</html>
