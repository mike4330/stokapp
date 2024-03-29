#!/bin/bash
# dividend yield updater script

cd /var/www/html/portfolio || exit

# akey=$(< alpha.key)
source apikeys

echo "start updating portfolio dividend yields" | logger -t "stockportfolio"


for s  in $(sqlite3 portfolio.sqlite "select symbol from prices\
		 where symbol IS NOT 'TGS' and class IS NOT NULL and asset_class LIKE '%Stock' order by symbol")
	do

    dy_prev=$(jq -r '.DividendYield' "datafiles/$s.json")

    curl -s "https://www.alphavantage.co/query?function=OVERVIEW&symbol=$s&apikey=$AVkey" -o datafiles/$s.json ;

    dy=$(jq -r '.DividendYield' datafiles/$s.json)
    echo "previous dy of $s is $dy_prev current dy of $s is $dy";
    ts=$(date +%s);

    sqlite3 portfolio.sqlite "update prices set symbol='$s',lastupdate='$ts',divyield='$dy' where symbol = '$s'";

    	sqlite3 portfolio.sqlite "update MPT set divyield='$dy' where symbol = '$s'";
    sleep 13;
done;


#ETFs and mutuals
echo -e "\n calculating ETF and mutuals"

for s in ANGL DBB EMB EWJ FPE LKOR MLN JPIB PDBC REM VCSH; do
	curl -s "https://api.polygon.io/v3/reference/dividends?ticker=$s&apiKey=$POLYkey" -o datafiles/$s.json
    	divamt=$(cat datafiles/$s.json| jq -r .results[0].cash_amount );
    	frequency=`cat datafiles/$s.json| jq -r .results[0].frequency `;
    	price=`sqlite3 portfolio.sqlite "select price from prices where symbol = '$s'"`;
    	dy=$(echo "scale=4;($divamt*$frequency)/$price" |bc -l);
    	echo -e "div for $s is $divamt\t price is $price\tfrequency is $frequency\t annual yield is $dy";
    	sqlite3 portfolio.sqlite "update prices set lastupdate='$ts',divyield='$dy' where symbol = '$s'";
    	sqlite3 portfolio.sqlite "update MPT set divyield='$dy' where symbol = '$s'";
	sleep 13;
done;

echo "finished updating div yield" | logger  -t "stockportfolio"

