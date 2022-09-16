#!/bin/bash
# Copyright (C) 2022 Mike Roetto <mike@roetto.org>
# SPDX-License-Identifier: GPL-3.0-or-later
#
# updates risk scores (compidx)
# downloads datafiles

cd /var/www/html/portfolio
source apikeys

CMD="/usr/bin/sqlite3 portfolio.sqlite"

pvalue=`$CMD "select value from historical order by date desc limit 1"`;

echo "updating historial prices and risk indices" | logger -t "stockportfolio"

$CMD "update prices set compidx2 = compidx where divyield IS NULL;" 

 for sym  in `sqlite3 portfolio.sqlite "select symbol from prices where class IS NOT NULL"`;
#for sym in  FNBGX ;

#retreive csv files
do
	echo $sym 
    curl -s "https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=$sym&apikey=$AVkey&datatype=csv&outputsize=full" -o $sym.csv;

    ts=`date +%s`;

    cprice=`$CMD "select price from prices where symbol = '$sym'"`;

    sd=`head -378 $sym.csv |grep 2 |awk -F, '{print $5}' |/usr/local/bin/st -stddev`;
    min=`head -378 $sym.csv |grep 2 |awk -F, '{print $5}' |/usr/local/bin/st --min`;
    max=`head -378 $sym.csv |grep 2 |awk -F, '{print $5}' |/usr/local/bin/st --max`;
    mean=`head -378 $sym.csv |grep 2 |awk -F, '{print $5}' |/usr/local/bin/st --mean`;

    midpoint=`echo "scale=4;($min+$max)/2" |bc -l`;
    #hlr=`echo " ($midpoint-$min)/($max-$min)" | bc -l`;
    hlr=`echo "scale=4;$cprice / $max" |bc -l`;

    nu=`$CMD "select ((select sum(units) from transactions \
    where xtype = 'Buy' and  symbol = '$sym'))-coalesce(((select sum(units) \
    from transactions where xtype = 'Sell' and symbol = '$sym')),0)"`

    cval=`echo "scale=4;$nu*$cprice" |bc -l`
    ppct=`echo "scale=4;$cval/$pvalue" |bc -l`

    #stdev relative to  price
    #volat=`echo "scale=4;$sd/$cprice" |bc -l`
    volat=`echo "scale=4;$sd/$mean" |bc -l`
    
    #compute index value
    cidx=`echo "scale=4;($ppct*100)*($hlr*100)*($volat*100)" |bc -l`

    #insert stdev
    q="update prices set stdev = '$sd', laststatupdate = '$ts', volat = '$volat', compidx='$cidx', hlr='$hlr'  where symbol = '$sym'";
    echo -e "$sym min: $min  max: $max  HLR $hlr idx $cidx  ppct $ppct volat $volat mean $mean"  |logger -t "stockportfolio";
    $CMD "$q";
	#update compidxv2
	q='update prices set compidx2 = round(compidx/(divyield*100),1)  where divyield IS NOT NULL';

    $CMD "$q";

    sleep 21;

done;

q="update prices set compidx2 = compidx where divyield = 0"
$CMD "$q";

echo "finished updating historical" | logger -t "stockportfolio"


./hist2.sh
./rtn2.plot
./movingaverages.sh
