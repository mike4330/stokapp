<?php
include ("../nav.php"); 
require_once 'vendor/autoload.php';

// Database connection details
$db_path = '../portfolio.sqlite';
$db_username = '';
$db_password = '';

// Connect to the database
try {
  $dbh = new PDO("sqlite:$db_path", $db_username, $db_password);
} catch (PDOException $e) {
  die("Error connecting to database: " . $e->getMessage());
}

// Define the SQL query
$sql = "SELECT
  substr(date_new, 0, 8) AS m,
  SUM(CASE WHEN xtype = 'Sell' THEN gain ELSE 0 END) AS g,
  SUM(CASE WHEN xtype = 'Div' THEN units * price ELSE 0 END) AS d,
  SUM(CASE WHEN xtype = 'Sell' THEN gain WHEN xtype = 'Div' THEN units * price ELSE 0 END) AS inc
FROM transactions
WHERE date_new >= date('now','-36 months')
GROUP BY m
ORDER BY m";

// Prepare and execute the query
$statement = $dbh->prepare($sql);
$statement->execute();

// Fetch the results
$data = $statement->fetchAll(PDO::FETCH_ASSOC);

// Extract the data for Chart.js
$labels = array_map(function ($row) {
  return $row['m'];
}, $data);

$gainData = array_map(function ($row) {
  return $row['g'];
}, $data);

$dividendData = array_map(function ($row) {
  return $row['d'];
}, $data);

$incomeData = array_map(function ($row) {
  return $row['inc'];
}, $data);

// Close the database connection
$dbh = null;

?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" type="text/css" href="../main.css">
<link rel="stylesheet" type="text/css" href="../nav.css">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Income Chart</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<div style="background-color: #080808;position: absolute;width: 95%;top: 5vh;">
  <canvas id="myChart"></canvas></div>

  <script>
    const ctx = document.getElementById('myChart').getContext('2d');
    const chart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [
          {
            label: 'Rlzd Gain',
            data: <?php echo json_encode($gainData); ?>,
            backgroundColor: 'rgba(32, 128, 32, 0.8)',
            borderColor: 'rgb(255, 99, 132)',
            fill: false,
          },
          {
            label: 'Dividend',
            data: <?php echo json_encode($dividendData); ?>,
            backgroundColor: 'rgba(64, 64, 200, 0.8)',
            borderColor: 'rgba(54, 162, 235, 1)',
            fill: false,
          },
          // {
          //   label: 'Total Income',
          //   data: <?php echo json_encode($incomeData); ?>,
          //   backgroundColor: 'rgb(153, 102, 255)',
          //   borderColor: 'rgba(153, 102, 255, 1)',
          //   fill: false,
          // },
        ]
      },
      options: {
        title: {
          display: true,
          text: 'Income by Month'
        },
        legend: {
          display: true
        },
        scales: {
            x: {
                stacked: true,
                ticks: {
                    color: "green", // change color of tickmarks to green
                    font: {
                        family: 'monospace' // switch to monospace font for tick labels
                    }
                }
            },
            y: {
                stacked: true,
                grid: {
                        color: "rgba(0, 255, 0, 0.2)" // change color of gridlines to brighter green
                    },
                ticks: {
                    color: "green", // change color of tickmarks to green
                    font: {
                        family: 'monospace' // switch to monospace font for tick labels
                    }
                }
            }
        }
      }
    });
  </script>
</body>
</html>
