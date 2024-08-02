#!/bin/bash
# Copyright (C) 2022 Mike Roetto <mike@roetto.org>
# SPDX-License-Identifier: GPL-3.0-or-later
#
# updates historical cumulative position values into security_values
# takes last updated price from prices table

cd /var/www/html/portfolio
CMD="/usr/bin/sqlite3 portfolio.sqlite"
su='0';

tfile=$(mktemp /tmp/foo.XXXXXXXXX)
echo "a file: $tfile"

cat XAG.csv |sort -r > $tfile
cp $tfile XAG.csv


 for f  in $(sqlite3 portfolio.sqlite "select symbol from prices where class IS NOT NULL and symbol !='0386.HK'") 

do
    echo "current symbol $f"  
    # nu=$($CMD "select ((select sum(units) from transactions \
    # where xtype = 'Buy' and  symbol = '$f'))-coalesce(((select sum(units) \
    # from transactions where xtype = 'Sell' and symbol = '$f')),0)")

        nu=$($CMD  "SELECT SUM(result) AS total_sum
        FROM (
        SELECT CASE
            WHEN units_remaining IS NULL THEN units
            ELSE units_remaining
        END AS result
        FROM transactions
        WHERE xtype = 'Buy'
        AND symbol = '$f'
        AND disposition IS NULL
        ) subquery;"
        )
    
    echo "net units $nu"
    
    buy_cost=$($CMD "select sum(price*units) from transactions where symbol = '$f' and xtype = 'Buy'")
	echo "buy cost $buy_cost"
    sell_cost=$($CMD "select ifnull(sum(price*units),0) from transactions where symbol = '$f' and xtype = 'Sell'")
	echo "sell cost $sell_cost"
    
#     net_cost=`echo "$buy_cost - $sell_cost" |bc -l`
    net_cost=$(php functions.php $f)
    
    cbps=$(echo "$net_cost/$nu" |bc -l)
    
    cum_divs=$($CMD "select ifnull(sum(price*units),0) from transactions where symbol = '$f' and xtype = 'Div'")
    cum_rgl=$($CMD "select ifnull(sum(gain),0) from transactions where symbol = '$f' and xtype = 'Sell'")
    
	CLOSE=$($CMD "select price from prices where symbol = '$f'")
	
# 	IN=`head -2 $f.csv |grep -vi open`
# 	arrIN=(${IN//,/ });

	TS=$(date +%Y-%m-%d)
	echo "values ${arrIN[0]},${arrIN[4]} $cbps"
	q="insert into security_values (symbol,timestamp,close,shares,cost_basis,cum_divs,cbps,cum_real_gl) \
	values('$f','$TS','$CLOSE', '$nu', '$net_cost','$cum_divs','$cbps','$cum_rgl')" ;

# 	echo "insert into security_values (symbol,timestamp,close,shares) values('$f','${arrIN[0]}','${arrIN[4]}', '$nu')" ;
	
 	$CMD "$q";

    sleep .5;
	
	done;


rm -f $tfile

#./divdata.sh
/usr/bin/dos2unix *.csv

# utils/snpdaily.sh
./movingaverages.sh

# for f in `sqlite3 portfolio.sqlite "select distinct symbol from prices"`; 
# 	do z=`grep 2 $f.csv|head -65 |awk -F, '{print $6}' |/usr/local/bin/st -f="%f" --mean`;
# 	echo -e "$z,$f";
# 	sqlite3 portfolio.sqlite "update prices set vol90 = '$z' where symbol = '$f'";
# 	done
