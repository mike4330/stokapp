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

curl "https://metals-api.com/api/latest?access_key=$metalskey&base=USD&symbols=XAG" > m.json

r=`cat m.json |jq -r .rates.XAG`
p=`echo "scale=4;1 / $r " |bc -l` 
ts=`date "+%Y-%m-%d"`
q="update prices set price = $p,lastupdate = '$ts' where symbol = 'XAG'"

echo "$ts,0,0,0,$p,0" >> XAG.csv

$CMD "$q"



