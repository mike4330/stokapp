<!--Copyright (C) 2022 Mike Roetto <mike@roetto.org>
SPDX-License-Identifier: GPL-3.0-or-later-->
<!DOCTYPE html>
<html>

<head>
<meta http-equiv="refresh" content="301">
<title>portfolio gain/loss</title>
<link rel="stylesheet" type="text/css" href="main.css">
<script src="/js/jquery-3.1.1.min.js"></script>
<script type="text/javascript" src="/js/chart.js"></script> 
</head>
<body>

<?php include ("nav.php"); ?> 

<div class="canvasback">
<canvas id="GLchart" style="width:1100px;height:684px"></canvas>
</div>
<script>

$.ajax({  
    url: 'datacsv.php?q=gl',
    type: 'GET',
    dataType: 'json',
    success:function(data){
        var symbol = [];var dollarreturn = [];      
        for(var i in data){
        symbol.push(data[i].symbol);
        dollarreturn.push(data[i].dollarreturn);
        }
            
        var chartdata = {
            labels: symbol,
            datasets: [
            {
                label: '$ return',
                radius: 0,
                backgroundColor: ['rgb(32, 89, 132)','rgb(0, 255, 0)'] , 
                borderColor: 'rgb(255, 0, 0)',
                borderWidth: 1,
                data:dollarreturn
            }
            ] 
            };
            
        var ctx = $('#GLchart');
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

<?php $date = date('Y-m-d H:i:s');
echo "<div class=statusmessage>$date</span>"
?>

</body>
</html>
