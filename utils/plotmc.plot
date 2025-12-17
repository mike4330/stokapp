#!/usr/bin/gnuplot

set datafile sep '|'

set term pngcairo size 1950,1080
set output "mc.png"

set grid lt -1 lc rgb "#c0c0c0"

set xdata time
set timefmt "%Y-%m-%d"

set title "`date`"

plot "< grep Small ../marketcap.log" using 1:4 t "Small" with l sm bez  lc rgb "red" ,\
 "< grep Micro ../marketcap.log" using 1:4 with l sm bez t "Micro" ,\
 "< grep Medium ../marketcap.log" using 1:4 with l sm bez lc rgb "#11bb11" lw 1.9 t "Medium" ,\
 "< grep Large ../marketcap.log" using 1:4 with l sm bez  lc rgb "#dddd00" lw 1.9 t "Large" ,\
"< grep Mega ../marketcap.log" using 1:4 with l sm bez  lc rgb "#660000ff" lw 1.9 t "Mega"


