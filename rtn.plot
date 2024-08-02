#! /usr/bin/gnuplot

cd "/var/www/html/portfolio/"

set xdata time
set timefmt "%Y-%m-%d"

#set term pngcairo dashed size 100,990 font "mono,9"

set term svg size 1850,1600 font "mono,9" 
set datafile sep "|"

set grid lc rgb "#0000aa" lt -1 lw .4
set ylabel "%"

set output "/var/www/html/portfolio/returnplots.svg"
#set output "/var/www/html/portfolio/retplots.html"

myp=.6
set xtics tc rgb "black" 
set ytics tc rgb "black"

do for [t=1:50] {
set lt t pi 10 ps 1.1 lw 2
}

set colors classic
set lt 1 lc rgb "#eeee00"
set lt 2 lc rgb "black"
set lt 4 lc rgb "#ff5555"
set lt 5 lc rgb "gray"
set lt 6 lc rgb "#00aa00"

set ls 10 lc rgb "#ff4545" lw 2.5 dashtype 2 pt 2 pi 0


set size 1,1
set origin 0,0
set multiplot layout 4,3 title "Portfolio Returns generated at `date`" \
font "mono,12" tc rgb "black" margins 0.023, 0.993, 0.96, 0.04 spacing .027,0.039
set format x "%y%m%d"
set key tc rgb "black" opaque fc  rgb "#cc010101"

set key top left
set title "Domestic Bond Returns" tc rgb "black" font "serif,14"
tf=110
plot "< ./getsymdata.sh FNBGX ".tf  using 1:2 with lines sm bez t "FNBGX rtn",\
"< ./getsymdata.sh FAGIX ".tf  using 1:2 with lines sm bez t "FAGIX rtn",\
"< ./getsymdata.sh EMB ".tf  using 1:2 with lines sm bez t "EMB rtn",\
"< ./getsymdata.sh VCSH ".tf  using 1:2 with lines sm bez t "VCSH rtn",\
0 ls 10 t "" 

set key bottom left
tf=225
set title "Domestic Bond Returns Cont." tc rgb "black" font "serif,14"
plot "< ./getsymdata.sh ANGL ".tf  using 1:2 with lines sm bez   t "ANGL rtn",\
"< ./getsymdata.sh LKOR ".tf using 1:2 with lines sm bez t "LKOR rtn",\
"< ./getsymdata.sh MLN ".tf using 1:2 with lines sm bez t "MLN rtn",\
"< ./getsymdata.sh JPIB ".tf  using 1:2 with lines sm bez  t "JPIB rtn",\
0 ls 10 t "" 

set title "Information Technology Stock Returns"
set key top left
tf=105
plot "< ./getsymdata.sh SSNC ".tf using 1:2 with lines sm bez  t "SSNC rtn",\
"< ./getsymdata.sh ASML ".tf  using 1:2 with l sm bez t "ASML rtn",\
"< ./getsymdata.sh NICE ".tf  using 1:2 with lines sm bez t "NICE rtn",\
"< ./getsymdata.sh TAIT ".tf  using 1:2 with lines sm bez t "TAIT rtn",\
"< ./getsymdata.sh AVGO ".tf  using 1:2 with l sm bez t "AVGO rtn",\
"< ./getsymdata.sh SOXX ".tf  using 1:2 with l sm bez t "SOXX rtn",\
0 ls 10 t "" 


set title "Industrials Returns"
set key top left
tf=125
plot "< ./getsymdata.sh CARR ".tf using 1:2 with l sm bez  t "CARR rtn",\
"< ./getsymdata.sh HTLD ".tf  using 1:2 with lines sm bez t "HTLD rtn",\
"< ./getsymdata.sh BAH ".tf  using 1:2 with lines sm bez t "BAH rtn",\
"< ./getsymdata.sh OTIS ".tf  using 1:2 with lines sm bez t "OTIS rtn",\
0 ls 10 t "" 


tf=130
set title "Financials Returns"
plot "< ./getsymdata.sh BRK.B ".tf using 1:2 with l sm bez  t "BRK.B rtn",\
"< ./getsymdata.sh FAF ".tf  using 1:2 with lines sm bez t "FAF rtn",\
"< ./getsymdata.sh BSIG ".tf  using 1:2 with lines sm bez t "BSIG rtn",\
0 ls 10 t "" 

set key top left
set title "Materials Returns"
tf=135
plot "< ./getsymdata.sh HUN ".tf using 1:2 with lines sm bez  t "HUN rtn",\
"< ./getsymdata.sh LYB ".tf  using 1:2 with lines sm bez t "LYB rtn",\
"< ./getsymdata.sh VALE ".tf  using 1:2 with lines sm bez t "VALE rtn",\
"< ./getsymdata.sh VMC ".tf  using 1:2 with lines sm bez t "VMC rtn",\
0 ls 10 t ""

set title "Healthcare Returns"
plot "< ./getsymdata.sh GILD ".tf using 1:2 with lines sm bez  t "GILD rtn",\
"< ./getsymdata.sh NVS ".tf  using 1:2 with lines sm bez t "NVS rtn",\
"< ./getsymdata.sh NHC ".tf  using 1:2 with lines sm bez t "NHC rtn",\
"< ./getsymdata.sh DGX ".tf  using 1:2 with l sm bez t "DGX rtn",\
0 ls 10 t ""

set title "Consumer Discretionary"
set key bottom left
tf=110
plot "< ./getsymdata.sh FRG ".tf using 1:2 with lines sm bez  t "FRG rtn",\
"< ./getsymdata.sh F ".tf  using 1:2 with lines sm bez t "F rtn",\
"< ./getsymdata.sh SCI ".tf  using 1:2 with lines sm bez t "SCI rtn",\
0 ls 10 t ""

set title "Consumer Staples"
tf=115
plot "< ./getsymdata.sh KMB ".tf using 1:2 with l sm bez  t "KMB rtn",\
"< ./getsymdata.sh BG ".tf  using 1:2 with lines smooth be t "BG rtn",\
"< ./getsymdata.sh IPAR ".tf  using 1:2 with lines smooth be t "IPAR rtn",\
"< ./getsymdata.sh INGR ".tf  using 1:2 with lines smooth be t "INGR rtn",\
0 ls 10 t ""

set title "Energy"
plot "< ./getsymdata.sh TGS ".tf using 1:2 with l sm bez  t "TGS rtn",\
"< ./getsymdata.sh PBR ".tf  using 1:2 with lines smooth be t "PBR rtn",\
"< ./getsymdata.sh HPK ".tf  using 1:2 with lines smooth be t "HPK rtn",\
0 ls 10 t ""

set title "Utilities"
tf=135
plot "< ./getsymdata.sh PNM ".tf using 1:2 with l sm bez  t "PNM rtn",\
"< ./getsymdata.sh D ".tf  using 1:2 with lines smooth be t "D rtn",\
"< ./getsymdata.sh FTS ".tf  using 1:2 with lines smooth be t "FTS rtn",\
0 ls 10 t ""

set title "Real Estate"
set key bottom left
plot "< ./getsymdata.sh PLD ".tf using 1:2 with l sm bez  t "PLD rtn",\
"< ./getsymdata.sh REM ".tf  using 1:2 with lines smooth be t "REM rtn",\
"< ./getsymdata.sh BRT ".tf  using 1:2 with lines smooth be t "BRT rtn",\
"< ./getsymdata.sh MPW ".tf  using 1:2 with lines smooth be t "MPW rtn",\
0 ls 10 t ""

unset y2tics
unset multiplot
set term svg size 1900,4000 font "Consolas,9" background rgb "#050505"
set output "rtnsingle.svg"
unset title
set size 1,1
set origin 0,0

# left,right,bottom,top
set multiplot layout 15,3 title "Portfolio Returns generated at `date`" font "Consolas,12" tc rgb "white" \
margins 0.011,0.98,0.05,0.985 spacing 0.016,0.015


unset ylabel
set key right tc rgb "white"

set colors classic
set lt 1 lc rgb "#e69999" lw 3
set lt 2 lc rgb "#99e699" lw 3
set lt 3 lc rgb "#e699e6" lw 3
set lt 4 lc rgb "#9999e6" lw 6
set lt 5 lc rgb "#ffd1b3" lw 3
set lt 6 lc rgb "#b3cceb" lw 3
set lt 7 lc rgb "#aacc8c" lw 3
set lt 8 lc rgb "#ddb39a" lw 3


set format x "%y%m"
do for [s in "AMX ANGL ASML AVGO BG BSIG C CARR BAH D DGX EMB EVC F FAF FAGIX FNBGX \
FRG FTS GILD HTLD HUN INGR IPAR JPIB LKOR LYB MLN MPW NHC NXST NVS \
OTIS PDBC PNM REM SGOL SCI SIVR SSNC TAIT TSLA VALE VCSH" ] {
zoo=(rand(0)*8)+1
set title s . " return" font "Consolas,13" tc  rgb "white" offset 0,-1
set xtics tc rgb "white" font "Consolas,13"
set ytics tc rgb "white" font "Consolas,13"
set grid lc rgb "#cccccc"
set border lc rgb "#888888"
unset key
plot "< ./getsymdata.sh ".s. " 240" using 1:2 with l sm bez ls zoo lw 1.4 t s." rtn",0 ls 10 t ""
}
