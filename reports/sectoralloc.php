<!DOCTYPE html>
<html>
<head>
   <title>Sector Model Allocations</title>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
<link rel="stylesheet" type="text/css" href="../main.css">
<link rel="stylesheet" type="text/css" href="../nav.css"> 
   <style>
      .chart-grid {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); /* creates a responsive grid with a minimum size of 300px per chart */
         grid-gap: 1em;
         margin: 0 auto;
         position: absolute;
         top: 50px;
         width: 95%;
         /* height: 70%; */
        
      }
      .chart-containerx {
         width: 100%;
      }
   </style>
</head>
<body>
   <?php
   //connect to database
   $db = new SQLite3('../portfolio.sqlite');
   include ("../nav.php");

   //query to get target_alloc of each unique symbol for each sector
   $data_query = "SELECT sector, symbol, SUM(target_alloc) AS target_alloc FROM MPT GROUP BY sector, symbol";

   //initialize an array for Chart.js data
   $sectors_data = array();

   //execute query and store data in array
   $result = $db->query($data_query);
   while ($row = $result->fetchArray()) {
      $sectors_data[$row['sector']]['symbols'][] = $row['symbol'];
      $sectors_data[$row['sector']]['allocs'][] = $row['target_alloc'];
   }

   //close database connection
   $db->close();
   ?>

   <div class="chart-grid">
      <?php foreach ($sectors_data as $sector => $data): ?>
         <div class="chart-containerx">
            <canvas id="<?php echo str_replace(' ', '_', $sector); ?>"></canvas>
         </div>
      <?php endforeach; ?>
   </div>

   <script>
      <?php foreach ($sectors_data as $sector => $data): ?>

      //set Chart.js data
      var data = {
         labels: ['<?php echo implode("', '", $data['symbols']); ?>'],
         datasets: [
            {
               data: [<?php echo implode(", ", $data['allocs']); ?>],
               borderWidth: 0,
               borderColor: "#000000",
               backgroundColor: [
                  "#ff5656",
                  "#00cc00",
                  "#0000ff",
                  "#bbaa00",
                  "#006400",
                  "#700000",
                  "#555555",
                  "#CD853F",
                  "#00CED1",
                  "#dd2233",
                  "#e0e0e0"
               ],
               hoverBackgroundColor: [
                  "#FF5555",
                  "#36A2EB",
                  "#FF9956",
                  "#8B008B",
                  "#006400",
                  "#800000",
                  "#555555",
                  "#CD853F",
                  "#00CED1"
               ]
            }]
      };

      //set Chart.js options
      var options = {
         responsive: true,
         maintainAspectRatio: false,
         title: {
            display: true,
            text: '<?php echo $sector; ?>'
         },
         legend: {
            display: true,
            position: "bottom"
         },

      };

      //initialize and render Chart.js chart
      var piechart = new Chart(document.getElementById('<?php echo str_replace(' ', '_', $sector); ?>'), {type: 'pie', data: data, options: options});

      <?php endforeach; ?>
   </script>
</body>
</html>
