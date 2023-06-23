<!--Copyright (C) 2022 Mike Roetto <mike@roetto.org>
SPDX-License-Identifier: GPL-3.0-or-later-->
<!DOCTYPE html>

<html>
<head>
<script src="/js/jquery-3.1.1.min.js"></script>
<script type="text/javascript" src="/js/chart.js"></script> 
<script src="/js/node_modules/chartjs-plugin-annotation/dist/chartjs-plugin-annotation.min.js"></script>
<link rel="stylesheet" type="text/css" href="main.css">
<link rel="stylesheet" type="text/css" href="nav.css">
<title>Sector Analysis</title>
</head>
<body>
<?php

$dir = 'sqlite:portfolio.sqlite';
include ("nav.php"); 
$dbh  = new PDO($dir) or die("cannot open the database"); 


$pf=gpv();

echo $pf;

echo "<table>";
echo "<th>Sector</th><th>T.Weight</th><th>C.Weight</th><th colspan=4>Diff</th>";

$query = "SELECT sector,sum(target_alloc) as alloc FROM MPT group by sector order by alloc desc ";

foreach ($dbh->query($query) as $row) {
    $sector = $row['sector'];
    
    $subquery = "select sum(close*shares) as sector_value
    from security_values
    where symbol in (select symbol from MPT where sector = '$sector')
    group by timestamp
    order by timestamp desc limit 1";
    $stmt = $dbh->prepare($subquery);$stmt->execute();$zrow = $stmt->fetch();
    
    $a=$zrow['sector_value'];
    $tgt_weight=round($row['alloc']*100,2);
    $cur_sector_wgt = round(($a/$pf)*100,2);
    $wgt_diff = round(100*($row['alloc'] - ($a/$pf)),2);
    
	if ($wgt_diff < 0) {$symbol="⇓";$fstr="#ee0808";}
		else {$symbol="⇑";$fstr="#11cc11";}

    $width_mult=2.6; //scale width of bar chart 
    $a_diff = abs($wgt_diff)*$width_mult;
    
    $wstr = $a_diff . "vw";
    
    echo "<tr>
    <td style=\"font-size: .8vw\">$row[sector]</td>
    <td style=\"font-size: .8vw\">$tgt_weight</td>
    <td style=\"font-size: .8vw\">$cur_sector_wgt</td>
    <td style=\"font-size: .8vw;text-align:right; \">
    <td style=\"font-size: .9vw;text-align:right; \">$symbol</td>
    <td style=\"font-size: .8vw;text-align:right; \"> $wgt_diff</td>
    <td>
    <svg width=\"$wstr\" height=1vw>

  <rect width=\"$wstr\" height=\"1vw\" style=\"fill:$fstr;\"/>
                
</svg>
    
    </td>
    </tr>";
    
    
}

echo "</table>";

echo '<div style="position: absolute;top: 53vh;width: 40vw; height: 36vh; background: white;"><canvas id="sectors"></canvas></div>';

echo '<table style="position: relative;left:22vw;top: 0vh;">
<tr>
  <td><div class="sectorchart"><canvas id="comd"></canvas></div></td>
  <td><div  class="sectorchart"><canvas id="cdisc"></canvas></div></td>
</tr>

<tr>
  <td><div  class="sectorchart"><canvas id="cstaples"></canvas></div></td>
  <td><div  class="sectorchart"><canvas id="energy"></canvas></div></td>
</tr>

<tr>
  <td><div  class="sectorchart"><canvas id="financials"></canvas></div></td>
  <td><div  class="sectorchart"><canvas id="healthcare"></canvas></div></td>
</tr>

<tr>
  <td><div  class="sectorchart"><canvas id="industrials"></canvas></div></td>
  <td><div  class="sectorchart"><canvas id="materials"></canvas></div></td>
</tr>

<tr>
  <td><div  class="sectorchart"><canvas id="pm"></canvas></div></td>
  <td><div  class="sectorchart"><canvas id="tech"></canvas></div></td>
</tr>

<tr>
  <td><div  class="sectorchart"><canvas id="utilities"></canvas></div></td>
</tr>
</table>';


?>

<script>
Chart.defaults.plugins.legend.display = false;

$.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=180',
      type: 'GET',
      dataType: 'json',
      success:function(data){

        var date = [];
        var comd = [];

        for(var i in data){
          date.push(data[i].date);
          comd.push(data[i].comd);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'Commodities',
                backgroundColor: 'rgb(32, 210, 32)',
                pointRadius: 1.5,
                data:comd
                
              }
            ] 
        };

        var ctx = $('#comd');

        var barGraph = new Chart(ctx, {
            type:'line',
            data: chartdata,  
            options: {
                plugins: {title: {text: "Commodities",display: true},
                  annotation: {
                    annotations: {
                      line2: {
                            type: 'line',
                            borderColor: 'rgb(255, 16, 16)',
                            borderWidth:  1.75,
                            yMin: 2.5,
                            yMax: 2.5
                              
                      }
                    }
                  }
                },
                maintainAspectRatio: false,
                responsive: true}
        });
      },

    });


$.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=180',
      type: 'GET',
      dataType: 'json',
      success:function(data){

        var date = [];
        var energy = [];

        for(var i in data){
          date.push(data[i].date);
          energy.push(data[i].energy);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'Energy',
                backgroundColor: 'rgb(32, 45, 255)',
                pointRadius: 1.5,
                data:energy
              }
            ] 
        };

        var ctx = $('#energy');

        var barGraph = new Chart(ctx, {
            type:'line',
            data: chartdata,  
            options: {
                plugins: {title: {text: "Energy",display: true},
                            annotation: {
                    annotations: {
                      line2: {
                            type: 'line',
                            borderColor: 'rgb(255, 16, 16)',
                            borderWidth:  1.75,
                            yMin: 4.01,
                            yMax: 4.01     
                      }
                    }
                  }
                },
                maintainAspectRatio: false,
                responsive: true}
        });
      },

    });

$.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=180',
      type: 'GET',
      dataType: 'json',
      success:function(data){

        var date = [];
        var tech = [];

        for(var i in data){
          date.push(data[i].date);
          tech.push(data[i].tech);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'tech',
                backgroundColor: 'rgb(120, 120, 12)',
                pointRadius: 1.5,
                data:tech
              }
            ] 
        };

        var ctx = $('#tech');
        var barGraph = new Chart(ctx, {
            type:'line',
            data: chartdata,  
            options: {
                plugins: {title: {text: "Tech",display: true},
                            annotation: {
                    annotations: {
                      line2: {
                            type: 'line',
                            borderColor: 'rgb(255, 16, 16)',
                            borderWidth:  1.75,
                            yMin: 8.55,
                            yMax: 8.55
                      }
                    }
                  }
                },
                maintainAspectRatio: false,
                responsive: true}   
        });
      },
    });


$.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=180',
      type: 'GET',
      dataType: 'json',
      success:function(data){

        var date = [];
        var utilities = [];

        for(var i in data){
          date.push(data[i].date);
          utilities.push(data[i].utilities);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'utilities',
                backgroundColor: 'rgb(120, 120, 12)',
                pointRadius: 1.5,
                data:utilities
              }
            ] 
        };

        var ctx = $('#utilities');

        var barGraph = new Chart(ctx, {
            type:'line',
            data: chartdata,  
            options: {
                plugins: {title: {text: "Utilities",display: true},
                            annotation: {
                    annotations: {
                      line2: {
                            type: 'line',
                            borderColor: 'rgb(64, 16, 200)',
                            borderWidth:  1.75,
                            yMin: 4.69,
                            yMax: 4.69
                      }
                    }
                  }
                },
                maintainAspectRatio: false,
                responsive: true}
        });
      },
    });

$.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=180',
      type: 'GET',
      dataType: 'json',
      success:function(data){

        var date = [];
        var healthcare = [];

        for(var i in data){
          date.push(data[i].date);
          healthcare.push(data[i].healthcare);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'Healthcare',
                backgroundColor: 'rgb(120, 120, 12)',
                pointRadius: 1.5,
                data:healthcare
              }
            ] 
        };

        var ctx = $('#healthcare');

        var barGraph = new Chart(ctx, {
            type:'line',
            data: chartdata,  
            options: {
                plugins: {title: {text: "Healthcare",display: true},
                            annotation: {
                    annotations: {
                      line2: {
                            type: 'line',
                            borderColor: 'rgb(64, 16, 200)',
                            borderWidth:  1.75,
                            yMin: 5.54,
                            yMax: 5.54
                              
                      }
                    }
                  }
                },
                maintainAspectRatio: false,
                responsive: true}
            
        });
      },

    });

$.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=180',
      type: 'GET',
      dataType: 'json',
      success:function(data){

        var date = [];
        var financials = [];

        for(var i in data){
          date.push(data[i].date);
          financials.push(data[i].financials);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'Financials',
                backgroundColor: 'rgb(120, 120, 12)',
                pointRadius: 1.5,
                data:financials
              }
            ] 
        };

        var ctx = $('#financials');
        var barGraph = new Chart(ctx, {
            type:'line',
            data: chartdata,  
            options: {
                plugins: {title: {text: "Financials",display: true},
                            annotation: {
                    annotations: {
                      line2: {
                            type: 'line',
                            borderColor: 'rgb(255, 255, 64)',
                            borderWidth:  1.75,
                            yMin: 5.49,
                            yMax: 5.49
                      }
                    }
                  }
                },
                maintainAspectRatio: false,
                responsive: true}
        });
      },
    });

$.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=180',
      type: 'GET',
      dataType: 'json',
      success:function(data){

        var date = [];
        var cstaples = [];

        for(var i in data){
          date.push(data[i].date);
          cstaples.push(data[i].cstaples);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'CStaples',
                backgroundColor: 'rgb(120, 120, 255)',
                pointRadius: 1.5,
                data:cstaples
              }
            ] 
        };

        var ctx = $('#cstaples');
        var barGraph = new Chart(ctx, {
            type:'line',
            data: chartdata,  
            options: {
                plugins: {title: {text: "Consumer Staples",display: true},
                            annotation: {
                    annotations: {
                      line2: {
                            type: 'line',
                            borderColor: 'rgb(200, 200, 200)',
                            borderWidth:  1.75,
                            yMin: 5.52,
                            yMax: 5.52
                      }
                    }
                  }
                },
                maintainAspectRatio: false,
                responsive: true}
        });
      },
    });


$.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=180',
      type: 'GET',
      dataType: 'json',
      success:function(data){

        var date = [];
        var cdisc = [];

        for(var i in data){
          date.push(data[i].date);
          cdisc.push(data[i].cdisc);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'Cdisc',
                backgroundColor: 'rgb(255, 120, 64)',
                pointRadius: 1.5,
                data:cdisc
              }
            ] 
        };

        var ctx = $('#cdisc');
        var barGraph = new Chart(ctx, {
            type:'line',
            data: chartdata,  
            options: {
                plugins: {title: {text: "Consumer Disc.",display: true},
                            annotation: {
                    annotations: {
                      line2: {
                            type: 'line',
                            borderColor: 'rgb(20, 255, 10)',
                            borderWidth:  1.75,
                            yMin: 5.6,
                            yMax: 5.6
                      }
                    }
                  }
                },
                maintainAspectRatio: false,
                responsive: true}
        });
      },
    });

$.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=180',
      type: 'GET',
      dataType: 'json',
      success:function(data){

        var date = [];
        var industrials = [];

        for(var i in data){
          date.push(data[i].date);
          industrials.push(data[i].industrials);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'industrials',
                backgroundColor: 'rgb(255, 120, 64)',
                pointRadius: 1.5,
                data:industrials
              }
            ] 
        };

        var ctx = $('#industrials');
        var barGraph = new Chart(ctx, {
            type:'line',
            data: chartdata,  
            options: {
                plugins: {title: {text: "Industrials",display: true},
                            annotation: {
                    annotations: {
                      line2: {
                            type: 'line',
                            borderColor: 'rgb(255, 12, 12)',
                            borderWidth:  1.75,
                            yMin: 5.53,
                            yMax: 5.53
                      }
                    }
                  }
                },
                maintainAspectRatio: false,
                responsive: true}
        });
      },
    });

$.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=180',
      type: 'GET',
      dataType: 'json',
      success:function(data){

        var date = [];
        var materials = [];

        for(var i in data){
          date.push(data[i].date);
          materials.push(data[i].materials);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'materials',
                backgroundColor: 'rgb(50, 255, 50)',
                pointRadius: 1.5,
                data:materials
              }
            ] 
        };

        var ctx = $('#materials');
        var barGraph = new Chart(ctx, {
            type:'line',
            data: chartdata,  
            options: {
                plugins: {title: {text: "Materials",display: true},
                            annotation: {
                    annotations: {
                      line2: {
                            type: 'line',
                            borderColor: 'rgb(255, 12, 12)',
                            borderWidth:  1.75,
                            yMin: 5.54,
                            yMax: 5.54
                      }
                    }
                  }
                },
                maintainAspectRatio: false,
                responsive: true}
        });
      },
    });


$.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=180',
      type: 'GET',
      dataType: 'json',
      success:function(data){

        var date = [];
        var pm = [];

        for(var i in data){
          date.push(data[i].date);
          pm.push(data[i].pm);
        }

        var chartdata = {
            labels: date,
            datasets: [
              {
                label: 'pm',
                backgroundColor: 'rgb(50, 0, 210)',
                pointRadius: 1.5,
                data:pm
              }
            ] 
        };

        var ctx = $('#pm');
        var barGraph = new Chart(ctx, {
            type:'line',
            data: chartdata,  
            options: {
                plugins: {title: {text: "pm",display: true},
                            annotation: {
                    annotations: {
                      line2: {
                            type: 'line',
                            borderColor: 'rgb(12, 12, 25)',
                            borderWidth:  3.75,
                            yMin: 5,
                            yMax: 5
                      }
                    }
                  }
                },
                maintainAspectRatio: false,
                responsive: true}
        });
      },
    });

//stacked timeseries
$.ajax({
    url: 'datacsv.php?verb=sectorpct&tf=180',
    type: 'GET',
    dataType: 'json',
    success:function(data){
        
        Chart.defaults.datasets.line.pointRadius = 0;
        Chart.defaults.plugins.title.color = 'rgb(0,0,0)';
        

        var date = []; var energy = []; var financials = []; 
        var materials = []; var pm = [];var industrials = []; var tech = [];
        var utilities = [];
        
        for(var i in data){
            date.push(data[i].date);
            energy.push(data[i].energy);
            materials.push(data[i].materials);
            industrials.push(data[i].industrials);
            tech.push(data[i].tech);
            financials.push(data[i].financials);
            utilities.push(data[i].utilities);
            pm.push(data[i].pm);
            }
    var ctx = $('#sectors');
    
   
    
    var chartdata = {
        labels: date,
            datasets: [
                    {
                label: 'Materials',
                borderWidth: 0,
                borderColor: 'rgba(255,255,255,255)',
                backgroundColor: 'rgba(255,0,0,.9)',
                data: materials,
		fill: true
            },
                     {
                label: 'industrials',
                borderWidth: 0,
                borderColor: 'rgba(255,255,255,255)',
                backgroundColor: 'rgba(0,255,0,.8)',
		fill: true,
                data: industrials    
            },
       
               {
                label: 'tech',
                borderWidth: 0,
                borderColor: 'rgba(255,255,255,255)',
                backgroundColor: 'rgba(0,0,255,.8)',
                data: tech,
		fill: true    
               },
               {
                label: 'utilities',
                borderWidth: 0,
                borderColor: 'rgba(255,255,255,255)',
                backgroundColor: 'rgba(255,255,0,.8)',
                data: utilities,
                fill: true    
               },
                {
                  label: 'financials',
                  borderWidth: 0,
                  borderColor: 'rgba(255,255,255,255)',
                  backgroundColor: 'rgba(0,255,255,.8)',
                  data: financials,
                  fill: true    
               },
                    {
                  label: 'P metals',
                  borderWidth: 0,
                  borderColor: 'rgba(255,255,255,255)',
                  backgroundColor: 'rgba(120,120,120,.8)',
                  data: pm,
                  fill: true    
               },
                  {
                  label: 'energy',
                  borderWidth: 0,
                  borderColor: 'rgba(255,255,255,255)',
                  backgroundColor: 'rgba(255,0,255,.8)',
                  data: energy,
                  fill: true    
               }
       
            ]     
    }
    
        var barGraph = new Chart(ctx, {
        type:'line',
        data: chartdata,  
        options: {
        scales: {
            x:{
                
                ticks:{
                    color: 'rgb(0, 0, 0)'
                }
                
            },
            y:{
                stacked: true,
                grid: {color: 'rgb(90,90,90)'},
                ticks:{
                    color: 'rgb(0,0,0)'
                }
            }
            
        },
        maintainAspectRatio: false,
        animation: false,
        responsive: true,
        plugins: {legend: {
                    display: true,
                    position: 'bottom',
                    labels: {color: 'rgb(0,0,0)'}
                        }
                    }
        }    
    }); 
           
    }
})



</script>

</body>
</html>
