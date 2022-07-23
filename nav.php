    <!--Copyright (C) 2022 Mike Roetto <mike@roetto.org>
    SPDX-License-Identifier: GPL-3.0-or-later-->

<?php

$dir = 'sqlite:portfolio.sqlite';





echo "<div class=nav>";
echo "<a href=\"/portfolio\" class=\"button2\"><span  STYLE=\"font-size:225%\"> 📒 </span>Transactions</a>\n";
echo "<a href=\"/portfolio/holdings.php\" class=\"button2\"><span  STYLE=\"font-size:225%\"> 💎 </span>Holdings</a>\n";
echo "<a href=\"/portfolio/div.php\" class=\"button2\"><span  STYLE=\"font-size:225%\"> 🌋 </span>Value Trends</a>\n";
echo "<a href=\"/portfolio/chart3.php\" class=\"button2\"><span  STYLE=\"font-size:225%\">♒ </span>Pos. Size Charts</a>\n";
echo "<a href=\"/portfolio/posvalues.php\" class=\"button2\"><span  STYLE=\"font-size:225%\">🫐 </span>Pos. Value Charts</a>\n";
echo "<a href=\"/portfolio/gainloss.php\" class=\"button2\"><span  STYLE=\"font-size:225%\">🥧</span>Gain/Loss Chart</a>\n";
echo "<a href=\"/portfolio/alloc.php\" class=\"button2\"><span  STYLE=\"font-size:225%\"> 🔱</span>Allocations</a>";
echo "<a href=\"/portfolio/divcharts.php\" class=\"button2\"><span  STYLE=\"font-size:225%; vertical-align: bottom;\"> ☕ </span>Div. Charts</a>";
echo "</div>";
?>
