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
  include("nav.php");
  $dbh  = new PDO($dir) or die("cannot open the database");


  $pf = gpv();

  echo $pf;

  echo "<table>";
  echo "<th>Sector</th><th>T.Weight</th><th>C.Weight</th><th colspan=4>Diff</th>";

  $query = "SELECT sector,sum(target_alloc) as alloc FROM MPT group by sector order by alloc desc ";

  foreach ($dbh->query($query) as $row) {
    $sector = $row['sector'];

    $subquery = "select sum(close*shares) as sector_value
    from security_values
    where symbol in (select symbol from sectors where sector = '$sector')
    group by timestamp
    order by timestamp desc limit 1";
    $stmt = $dbh->prepare($subquery);
    $stmt->execute();
    $zrow = $stmt->fetch();

    $a = $zrow['sector_value'];
    $tgt_weight = round($row['alloc'] * 100, 2);
    $cur_sector_wgt = round(($a / $pf) * 100, 2);
    $wgt_diff = round(100 * ($row['alloc'] - ($a / $pf)), 2);

    if ($wgt_diff < 0) {
      $symbol = "⇓";
      $fstr = "#ee0808";
    } else {
      $symbol = "⇑";
      $fstr = "#11cc11";
    }

    $width_mult = 2.6; //scale width of bar chart 
    $a_diff = abs($wgt_diff) * $width_mult;

    $wstr = $a_diff . "vw";

    $fs = '1.1vh';

    echo "<tr>
    <td style=\"font-size: $fs\">$row[sector]</td>
    <td style=\"font-size: $fs\">$tgt_weight</td>
    <td style=\"font-size: $fs\">$cur_sector_wgt</td>
    <td style=\"font-size: $fs ;text-align:right; \">
    <td style=\"font-size: .9vw;text-align:right; \">$symbol</td>
    <td style=\"font-size: $fs;text-align:right; \"> $wgt_diff</td>
    <td>
    <svg width=\"$wstr\" height=1vw>

  <rect width=\"$wstr\" height=\"1vw\" style=\"fill:$fstr;\"/>
                
</svg>
    
    </td>
    </tr>";
  }

  echo "</table>";

  echo '<div style="position: absolute;top: 53vh;width: 40vw; height: 36vh; background: white;"><canvas id="sectors"></canvas></div>';

  echo '<table style="position: relative;left:21vw;top: 2vh;">
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
  <td><div  class="sectorchart"><canvas id="commsvc"></canvas></div></td>
</tr>

<tr>
  <td><div  class="sectorchart"><canvas id="bonds"></canvas></div></td>
  <td><div  class="sectorchart"><canvas id="re"></canvas></div></td>
 
</tr>
</table>';


  ?>

  <script>
    Chart.defaults.plugins.legend.display = false;
    Chart.defaults.datasets.line.borderWidth = 1.8
    Chart.defaults.datasets.line.tension = .3;
    Chart.defaults.datasets.line.pointRadius = 0;
    Chart.defaults.font.size = 9;
    var tfval = 185;
    var dashcolor = 'rgb(128,128,128)';
    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=' + 210,
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        var date = [];
        var comd = [];

        for (var i in data) {
          date.push(data[i].date);
          comd.push(data[i].comd);
        }

        var chartdata = {
          labels: date,
          datasets: [{
            label: 'Commodities',
            borderColor: 'rgb(235, 220, 16)',
            data: comd

          }]
        };

        var ctx = $('#comd');

        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            plugins: {
              title: {
                text: "Commodities",
                display: true
              },
              annotation: {
                annotations: {
                  line2: {
                    type: 'line',
                    borderColor: 'rgb(255, 16, 16)',
                    borderWidth: 1.75,
                    yMin: 2.5,
                    yMax: 2.5
                  }
                }
              }
            },
            maintainAspectRatio: false,
            responsive: true
          }
        });
      },
    });


    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=' + 365,
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        var date = [];
        var energy = [];

        for (var i in data) {
          date.push(data[i].date);
          energy.push(data[i].energy);
        }

        var chartdata = {
          labels: date,
          datasets: [{
            label: 'Energy',
            borderColor: 'rgb(32, 45, 255)',
            data: energy
          }]
        };

        var ctx = $('#energy');

        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            plugins: {
              title: {
                text: "Energy",
                display: true
              },
              annotation: {
                annotations: {
                  line2: {
                    type: 'line',
                    borderColor: 'rgb(255, 16, 16)',
                    borderWidth: 1.75,
                    yMin: 4.3,
                    yMax: 4.3
                  }
                }
              }
            },
            maintainAspectRatio: false,
            responsive: true
          }
        });
      },
    });

    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=' + 285,
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        var date = [];
        var tech = [];

        for (var i in data) {
          date.push(data[i].date);
          tech.push(data[i].tech);
        }

        var chartdata = {
          labels: date,
          datasets: [{
            label: 'tech',
            borderColor: 'rgb(120, 120, 12)',
            data: tech
          }]
        };

        var ctx = $('#tech');
        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            plugins: {
              title: {
                text: "Tech",
                display: true
              },
              annotation: {
                annotations: {
                  line2: {
                    type: 'line',
                    borderColor: dashcolor,
                    borderDash: [6, 2],
                    borderWidth: 2,
                    yMin: 7.84,
                    yMax: 7.84
                  }
                }
              }
            },
            maintainAspectRatio: false,
            responsive: true
          }
        });
      },
    });


    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=' + 320,
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        var date = [];
        var utilities = [];

        for (var i in data) {
          date.push(data[i].date);
          utilities.push(data[i].utilities);
        }

        var chartdata = {
          labels: date,
          datasets: [{
            label: 'utilities',
            borderColor: 'rgb(118, 184, 0)',
            data: utilities
          }]
        };

        var ctx = $('#utilities');
        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            plugins: {
              title: {
                text: "Utilities",
                display: true
              },
              annotation: {
                annotations: {
                  line2: {
                    type: 'line',
                    borderColor: dashcolor,
                    borderWidth: 2,
                    borderDash: [6, 2],
                    yMin: 4.88,
                    yMax: 4.88
                  }
                }
              }
            },
            maintainAspectRatio: false,
            responsive: true
          }
        });
      },
    });

    //healthcare
    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=' + 240,
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        var date = [];
        var healthcare = [];

        for (var i in data) {
          date.push(data[i].date);
          healthcare.push(data[i].healthcare);
        }

        var chartdata = {
          labels: date,
          datasets: [{
            label: 'Healthcare',
            borderColor: 'rgb(12, 205, 12)',
            data: healthcare
          }]
        };

        var ctx = $('#healthcare');

        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            plugins: {
              title: {
                text: "Healthcare",
                display: true
              },
              annotation: {
                annotations: {
                  line2: {
                    type: 'line',
                    borderColor: dashcolor,
                    borderWidth: 2,
                    borderDash: [6, 2],
                    yMin: 5.57,
                    yMax: 5.57

                  }
                }
              }
            },
            maintainAspectRatio: false,
            responsive: true
          }

        });
      },

    });

    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=' + 300,
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        var date = [];
        var financials = [];

        for (var i in data) {
          date.push(data[i].date);
          financials.push(data[i].financials);
        }

        var chartdata = {
          labels: date,
          datasets: [{
            label: 'Financials',
            borderColor: 'rgb(255, 12, 12)',
            data: financials
          }]
        };

        var ctx = $('#financials');
        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            plugins: {
              title: {
                text: "Financials",
                display: true
              },
              annotation: {
                annotations: {
                  line2: {
                    type: 'line',
                    borderColor: 'rgb(255, 255, 64)',
                    borderWidth: 2,
                    borderDash: [6, 2],
                    yMin: 5.57,
                    yMax: 5.57
                  }
                }
              }
            },
            maintainAspectRatio: false,
            responsive: true
          }
        });
      },
    });

    //cstaples
    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=' + 365,
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        var date = [];
        var cstaples = [];

        for (var i in data) {
          date.push(data[i].date);
          cstaples.push(data[i].cstaples);
        }

        var chartdata = {
          labels: date,
          datasets: [{
            label: 'CStaples',
            borderColor: 'rgb(32, 156, 156)',
            backgroundWidth: 2,
            data: cstaples
          }]
        };

        var ctx = $('#cstaples');
        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            plugins: {
              title: {
                text: "Consumer Staples",
                display: true
              },
              annotation: {
                annotations: {
                  line2: {
                    type: 'line',
                    borderColor: 'rgb(200, 200, 200)',
                    borderWidth: 2,
                    borderDash: [6, 2],
                    yMin: 5.57,
                    yMax: 5.57
                  }
                }
              }
            },
            maintainAspectRatio: false,
            responsive: true
          }
        });
      },
    });

//cdisc
    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=' + 230,
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        var date = [];
        var cdisc = [];

        for (var i in data) {
          date.push(data[i].date);
          cdisc.push(data[i].cdisc);
        }

        var chartdata = {
          labels: date,
          datasets: [{
            label: 'Cdisc',
            borderColor: 'rgb(245, 120, 34)',
            data: cdisc
          }]
        };

        var ctx = $('#cdisc');
        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            plugins: {
              title: {
                text: "Consumer Disc.",
                display: true
              },
              annotation: {
                annotations: {
                  line2: {
                    type: 'line',
                    borderColor: dashcolor,
                    borderWidth: 2,
                    borderDash: [6, 2],
                    yMin: 5.57,
                    yMax: 5.57
                  }
                }
              }
            },
            maintainAspectRatio: false,
            responsive: true
          }
        });
      },
    });

    // industrials
    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=' + 295,
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        var date = [];
        var industrials = [];

        for (var i in data) {
          date.push(data[i].date);
          industrials.push(data[i].industrials);
        }

        var chartdata = {
          labels: date,
          datasets: [{
            label: 'industrials',
            borderColor: 'rgb(129, 120, 240)',
            data: industrials
          }]
        };

        var ctx = $('#industrials');
        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            plugins: {
              title: {
                text: "Industrials",
                display: true
              },
              annotation: {
                annotations: {
                  line2: {
                    type: 'line',
                    borderColor: dashcolor,
                    borderDash: [6, 2],
                    borderWidth: 2,
                    yMin: 5.57,
                    yMax: 5.57
                  }
                }
              }
            },
            maintainAspectRatio: false,
            responsive: true
          }
        });
      },
    });

   //materials
    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=' + 215,
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        var date = [];
        var materials = [];

        for (var i in data) {
          date.push(data[i].date);
          materials.push(data[i].materials);
        }

        var chartdata = {
          labels: date,
          datasets: [{
            label: 'materials',
            borderColor: 'rgb(128, 128, 190)',
            data: materials
          }]
        };

        var ctx = $('#materials');
        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            plugins: {
              title: {
                text: "Materials",
                display: true
              },
              annotation: {
                annotations: {
                  line2: {
                    type: 'line',
                    borderColor: dashcolor,
                    borderDash: [6, 2],
                    borderWidth: 2,
                    yMin: 5.57,
                    yMax: 5.57
                  }
                }
              }
            },
            maintainAspectRatio: false,
            responsive: true
          }
        });
      },
    });

    //pm
    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=365',
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        var date = [];
        var pm = [];

        for (var i in data) {
          date.push(data[i].date);
          pm.push(data[i].pm);
        }

        var chartdata = {
          labels: date,
          datasets: [{
            label: 'pm',
            backgroundColor: 'rgb(50, 255, 210)',
            borderColor: 'rgb(128, 128,32)',
            pointRadius: 0,
            data: pm
          }]
        };

        var ctx = $('#pm');
        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            plugins: {
              title: {
                text: "pm",
                display: true
              },
              annotation: {
                annotations: {
                  line2: {
                    type: 'line',
                    borderColor: dashcolor,
                    borderWidth: 2,
                    borderDash: [6, 2],
                    yMin: 7,
                    yMax: 7
                  }
                }
              }
            },
            maintainAspectRatio: false,
            responsive: true
          }
        });
      },
    });

    // commsvc
    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=365',
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        var date = [];
        var commsvc = [];

        for (var i in data) {
          date.push(data[i].date);
          commsvc.push(data[i].commsvc);
        }

        var chartdata = {
          labels: date,
          datasets: [{
            label: 'commsvc',
            borderColor: 'rgb(50, 50, 50)',
            data: commsvc
          }]
        };

        var ctx = $('#commsvc');
        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            plugins: {
              title: {
                text: "commsvc",
                display: true
              },
              annotation: {
                annotations: {
                  line2: {
                    type: 'line',
                    borderColor: dashcolor,
                    borderWidth: 2,
                    borderDash: [6, 2],
                    yMin: 5.22,
                    yMax: 5.22
                  }
                }
              }
            },
            maintainAspectRatio: false,
            responsive: true
          }
        });
      },
    });

    // bonds
    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=365',
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        var date = [];
        var bonds = [];

        for (var i in data) {
          date.push(data[i].date);
          bonds.push(data[i].bonds);
        }

        var chartdata = {
          labels: date,
          datasets: [{
            label: 'bonds',
            borderColor: 'rgb(50, 132, 50)',
            data: bonds
          }]
        };

        var ctx = $('#bonds');
        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            plugins: {
              title: {
                text: "bonds",
                display: true
              },
              annotation: {
                annotations: {
                  line2: {
                    type: 'line',
                    borderColor: dashcolor,
                    borderWidth: 2,
                    borderDash: [6, 2],
                    yMin: 30,
                    yMax: 30
                  }
                }
              }
            },
            maintainAspectRatio: false,
            responsive: true
          }
        });
      },
    });

    // real estate
    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=220',
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        var date = [];
        var re = [];

        for (var i in data) {
          date.push(data[i].date);
          re.push(data[i].re);
        }

        var chartdata = {
          labels: date,
          datasets: [{
            label: 're',
            borderColor: 'rgb(255, 170, 170)',
            data: re
          }]
        };

        var ctx = $('#re');
        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            plugins: {
              title: {
                text: "re",
                display: true
              },
              annotation: {
                annotations: {
                  line2: {
                    type: 'line',
                    borderColor: dashcolor,
                    borderWidth: 2,
                    borderDash: [6, 2],
                    yMin: 5.57,
                    yMax: 5.57
                  }
                }
              }
            },
            maintainAspectRatio: false,
            responsive: true
          }
        });
      },
    });

    //stacked timeseries
    $.ajax({
      url: 'datacsv.php?verb=sectorpct&tf=180',
      type: 'GET',
      dataType: 'json',
      success: function(data) {

        Chart.defaults.datasets.line.pointRadius = 0;
        Chart.defaults.plugins.title.color = 'rgb(0,0,0)';


        var date = [];
        var energy = [];
        var financials = [];
        var materials = [];
        var pm = [];
        var industrials = [];
        var tech = [];
        var utilities = [];

        for (var i in data) {
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

        var lw = 2.1;

        var chartdata = {
          labels: date,
          datasets: [{
              label: 'Materials',
              borderWidth: lw,
              borderColor: 'rgb(0,0,192)',
              data: materials,
              fill: false
            },
            {
              label: 'industrials',
              borderWidth: lw,
              borderColor: 'rgb(142,142,255)',
              fill: false,
              data: industrials
            },

            {
              label: 'tech',
              borderWidth: lw,
              borderColor: 'rgb(255,116,116)',
              data: tech,
              fill: false
            },
            {
              label: 'utilities',
              borderWidth: lw,
              borderColor: 'rgb(0,211,0)',
              data: utilities,
              fill: false
            },
            {
              label: 'financials',
              borderWidth: lw,
              borderColor: 'rgb(248,231,52)',
              data: financials,
              fill: false
            },
            {
              label: 'P metals',
              borderWidth: lw,
              borderColor: 'rgb(239,195,99)',
              data: pm,
              fill: false
            },
            {
              label: 'energy',
              borderWidth: lw,
              borderColor: 'rgb(129,129,129)',
              data: energy,
              fill: false
            }

          ]
        }

        var barGraph = new Chart(ctx, {
          type: 'line',
          data: chartdata,
          options: {
            scales: {
              x: {

                ticks: {
                  color: 'rgb(0, 0, 0)'
                }

              },
              y: {
                stacked: false,
                grid: {
                  color: 'rgb(90,90,90)'
                },
                ticks: {
                  color: 'rgb(0,0,0)'
                }
              }

            },
            maintainAspectRatio: false,
            animation: false,
            responsive: true,
            plugins: {
              legend: {
                display: true,
                position: 'bottom',
                labels: {
                  color: 'rgb(0,0,0)'
                }
              }
            }
          }
        });

      }
    })
  </script>

</body>

</html>
