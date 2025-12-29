#!/bin/bash

DBCMD='sqlite3 ../portfolio.sqlite'

cd /var/www/html/portfolio/datafiles || exit

s=$1
 
echo "get divgrowth for $s";
curl -s "https://api.polygon.io/v3/reference/dividends?ticker=$s&apiKey=GC8y97B4PppN29oUQzBXWVVHNAkQk5Dj"  -o div$s.json ; 
#echo $s ; 
./load.py div$s.json;./grrate2.py $s


divdate=$(jq -r '.results[0].pay_date' < div$1.json)
amount=$(jq -r '.results[0].cash_amount' < div$1.json)
#$DBCMD "update aux_attributes set dividend_date = '$divdate' where symbol = '$1'"
$DBCMD "INSERT INTO aux_attributes (symbol, dividend_date) VALUES ('$1', '$divdate') ON CONFLICT(symbol) DO UPDATE SET dividend_date = '$divdate';"
#$DBCMD "update aux_attributes set expected_amount = '$amount' where symbol = '$1'"
$DBCMD "INSERT INTO aux_attributes (symbol, expected_amount) VALUES ('$1', '$amount') ON CONFLICT(symbol) DO UPDATE SET expected_amount = '$amount';"

echo "next dividend of $amount on $divdate"

