#!/bin/bash

cd /var/www/html/portfolio || exit
CMD="/usr/bin/sqlite3 portfolio.sqlite"

#su mike
#./download.py

for sym in $(sqlite3 portfolio.sqlite "select symbol from prices where class IS NOT NULL order by symbol"); do
    x=$(tail -51 $sym.csv | awk -F, '{print $2}' | st -q --mean)
    $CMD "update prices set mean50 = '$x' where symbol='$sym'"
    x=$(tail -201 $sym.csv | awk -F, '{print $2}' | st -q --mean)
    $CMD "update prices set mean200 = '$x' where symbol='$sym'"

    echo "compute stats for $sym"
    stats=("sd" "min" "max" "mean")

    for stat in "${stats[@]}"; do
        values=$(tail -252 $sym.csv | awk -F, '{print $2}' | /usr/local/bin/st -q --$stat)
        declare $stat="$values"
    done

    volat=$(echo "scale=4;$sd/$mean" | bc -l)

    cprice=$($CMD "select price from prices where symbol = '$sym'")
    midpoint=$(echo "scale=4;($min+$max)/2" | bc -l)
    #
    hlr=$(echo "scale=4;$cprice / $max" | bc -l)

    ts=$(date +%s)

    q="update prices set stdev = '$sd', laststatupdate = '$ts', volat = '$volat', hlr='$hlr'  where symbol = '$sym'"

    cidx=1

    echo -e "$sym min: $min  max: $max  HLR $hlr idx $cidx  ppct $ppct volat $volat mean $mean" | logger -t "stockportfolio"
    $CMD "$q"
    echo "$sym $x"

done
