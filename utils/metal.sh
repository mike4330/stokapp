#!/bin/bash
# 
# Copyright (C) 2022 Mike Roetto <mike@roetto.org>
# SPDX-License-Identifier: GPL-3.0-or-later

cd /var/www/html/portfolio
source apikeys
CMD="/usr/bin/sqlite3 /var/www/html/portfolio/portfolio.sqlite"

#https://metals-api.com/api/latest
#? access_key = API_KEY
#& base = USD
#& symbols = GBP,JPY,EUR

curl -s "https://metals-api.com/api/latest?access_key=$metalskey&base=USD&symbols=XAG" > m.json

r=`cat m.json |jq -r .rates.XAG`
p=`echo "scale=4;1 / $r " |bc -l` 
ts=`date "+%Y-%m-%d"`
tsu=`date "+%s"`
q="update prices set price = $p,lastupdate = '$tsu' where symbol = 'XAG'"

nu=`$CMD "select ((select sum(units) from transactions \
    where xtype = 'Buy' and  symbol = 'XAG'))-coalesce(((select sum(units) \
    from transactions where xtype = 'Sell' and symbol = 'XAG')),0)"`

buy_cost=`$CMD "select sum(price*units) from transactions where symbol = 'XAG' and xtype = 'Buy'"`

sell_cost=`$CMD "select ifnull(sum(price*units),0) from transactions where symbol = '$f' and xtype = 'Sell'"`
echo "sell cost $sell_cost"

net_cost=`echo "$buy_cost - $sell_cost" |bc -l`

cbps=`echo "$net_cost/$nu" |bc -l`

cum_divs=`$CMD "select ifnull(sum(price*units),0) from transactions where symbol = '$f' and xtype = 'Div'"`
cum_rgl=`$CMD "select ifnull(sum(gain),0) from transactions where symbol = '$f' and xtype = 'Sell'"`

	q="insert into security_values (symbol,timestamp,close,shares,cost_basis,cum_divs,cbps,cum_real_gl) \
	values('XAG','$ts','$p', '$nu', '$net_cost','$cum_divs','$cbps','$cum_rgl')" ;
	
echo "net units of XAG $nu"
echo "current price is $p"
echo "buy cost $buy_cost"
echo "sell cost $sell_cost"
echo $q
  
    
 echo "$ts,0,0,0,$p,0" >> XAG.csv

$CMD "$q"



