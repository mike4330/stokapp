<!--Copyright (C) 2022 Mike Roetto <mike@roetto.org>
SPDX-License-Identifier: GPL-3.0-or-later-->
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" type="text/css" href="../main.css">
    <link rel="stylesheet" type="text/css" href="../nav.css">
    <title>Pair Analysis</title>
</head>

<body>

    <?php
    // Connect to SQLite database
    $db = new SQLite3('../portfolio.sqlite');

    // SQL query
    $sql = "SELECT 
        pos.symbol AS positive_symbol, 
        pos.sector AS sector, 
        pos.overamt AS positive_overamt, 
        pos_prices.hlr AS positive_hlr,
        neg.symbol AS negative_symbol, 
        neg.overamt AS negative_overamt,
        neg_prices.hlr AS negative_hlr
    FROM 
        MPT AS pos
    JOIN 
        prices AS pos_prices
    ON 
        pos.symbol = pos_prices.symbol
    JOIN 
        MPT AS neg
    ON 
        pos.sector = neg.sector
    JOIN 
        prices AS neg_prices
    ON 
        neg.symbol = neg_prices.symbol
    WHERE 
        pos.overamt > 5 AND neg.overamt < -5
    ORDER BY 
        pos_prices.hlr DESC,
        neg_prices.hlr ASC";

    // Execute query
    $result = $db->query($sql);
    ?>

    <!DOCTYPE html>
    <html>

    <body>

        <style>
            td {
                font-size: 1.3vw;
                border-bottom: 1px solid black;
                padding: .85vh .8vw;
            }

            td.pos {
                background-color: #002200;
            }

            th.pos {
                background-color: #001100;
            }

            td.neg {
                background-color: #550000;
            }

            th.neg {
                background-color: #330000;
            }

            table {
                width: auto;
                left: 1vw;
            }
        </style>

        <table>
            <tr>
                <th></th>
                <th class="pos" colspan=3>SELLING</th>
                <th class="neg" colspan=3 style='border-left: 5px solid black;'>BUYING</th>
                <th></th> <th rowspan=2>Recommendations</th>
            </tr>
            <tr>
                <th>Sector</th>
                <th class="pos">Symbol</th>

                <th class="pos">Overweight</th>
                <th class="pos">HLR</th>
                <th class="neg" style='border-left: 5px solid black;'>Symbol</th>
                <th class="neg">Underweight</th>
                <th class="neg">HLR</th><th></th>
               
            </tr>

            <?php
            include("../nav.php");

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {

                $rquery = "select returnpct from returns where symbol = '" . $row['positive_symbol'] . "'";
                $zresult = $db->query($rquery);
                $zrow = $zresult->fetchArray(SQLITE3_ASSOC);
                $prtn = $zrow['returnpct'];

                if ($prtn > 0) {
                    $flag = " XX ";
                } else {
                    $flag = "";
                    continue;
                }
            if (abs($row['negative_overamt']) < $row['positive_overamt']) {
                $prose = "Sell $" . abs($row['negative_overamt']) . " of " . $row['positive_symbol'] . "";
                $prose .= ". Buy $" . abs($row['negative_overamt']) . " of " . $row['negative_symbol'] . "";
            } else {
                $prose = "Sell $" . $row['positive_overamt'] . " of " . $row['positive_symbol'] . "";
                $prose .= ". Buy $" . $row['positive_overamt'] . " of " . $row['negative_symbol'] . "";
            }

                $count[$row['positive_symbol']]++;

                if ($count[$row['positive_symbol']] > 1) {continue;}
                $spread = ($row['positive_hlr'] - $row['negative_hlr'])*100;
                $spread=round($spread,2);

                if ($spread < 0) {continue;}

                echo "<tr>";
                echo "<td>" . $row['sector'] . "</td>";
                echo '<td class="pos">' . $row['positive_symbol'] . ' ' .  '</td>';
                echo "<td class=\"pos\">" . number_format($row['positive_overamt'], 2) . "</td>";
                echo "<td class=\"pos\">" . $row['positive_hlr'] . "</td>";
                echo "<td class=\"neg\" style='border-left: 5px solid black;'>" . $row['negative_symbol'] . "</td>";
                echo "<td class=\"neg\">" . number_format($row['negative_overamt'], 2) . "</td>";
                echo "<td class=\"neg\">" . $row['negative_hlr'] . "</td>";
                echo "<td>spread $spread</td>";
                echo "<td style='border-left: 1px solid black; font-family: serif;'>$prose</td>";


                echo "</tr>";
            }
            ?>
        </table>

    </body>

    </html>