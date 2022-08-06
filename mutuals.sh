#!/bin/bash

# updates historical cumulative position values



cd /var/www/html/portfolio
CMD="/usr/bin/sqlite3 portfolio.sqlite"
su='0';



 for f in FNBGX BRK.B; 



do
	curl -s "https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol=$f&apikey=SORP23ZPO9SOO9YD&datatype=csv&outputsize=full" -o $f.csv;

   
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


