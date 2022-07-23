# Copyright (C) 2022 Mike Roetto <mike@roetto.org>
# SPDX-License-Identifier: GPL-3.0-or-later

#!/bin/bash

# updates historical cumulative position values

cd /var/www/html/portfolio
CMD="/usr/bin/sqlite3 portfolio.sqlite"
su='0';

tfile=$(mktemp /tmp/foo.XXXXXXXXX)
echo "a file: $tfile"

cat XAG.csv |sort -r > $tfile
cp $tfile XAG.csv


 for f in ANGL ASML BEN BRK.B BSJN C DBB EMB EWJ FPE  GILD GSL JPIB KMB KHC LKOR LNG MLN MPW PDBC REM SGOL XAG SOXX UFPI VMC ;

do
   
    nu=`$CMD "select ((select sum(units) from transactions \
    where xtype = 'Buy' and  symbol = '$f'))-coalesce(((select sum(units) \
    from transactions where xtype = 'Sell' and symbol = '$f')),0)"`
    
    echo "net units $nu"
    
    buy_cost=`$CMD "select sum(price*units) from transactions where symbol = '$f' and xtype = 'Buy'"`
	echo "buy cost $buy_cost"
    sell_cost=`$CMD "select ifnull(sum(price*units),0) from transactions where symbol = '$f' and xtype = 'Sell'"`
	echo "sell cost $sell_cost"
    
    net_cost=`echo "$buy_cost - $sell_cost" |bc -l`
    
    cbps=`echo "$net_cost/$nu" |bc -l`
    
    cum_divs=`$CMD "select ifnull(sum(price*units),0) from transactions where symbol = '$f' and xtype = 'Div'"`
    cum_rgl=`$CMD "select ifnull(sum(gain),0) from transactions where symbol = '$f' and xtype = 'Sell'"`
    
	
	
	IN=`head -2 $f.csv |grep -vi open`
	arrIN=(${IN//,/ });
	echo "values ${arrIN[0]},${arrIN[4]} $cbps"
	q="insert into security_values (symbol,timestamp,close,shares,cost_basis,cum_divs,cbps,cum_real_gl) \
	values('$f','${arrIN[0]}','${arrIN[4]}', '$nu', '$net_cost','$cum_divs','$cbps','$cum_rgl')" ;

# 	echo "insert into security_values (symbol,timestamp,close,shares) values('$f','${arrIN[0]}','${arrIN[4]}', '$nu')" ;
	
 	$CMD "$q";
	
	done;


rm -f $tfile
