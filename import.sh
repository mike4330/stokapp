#!/bin/bash

INF=semivariance.csv

sed -i s/BRK-B/BRK.B/ $INF

dos2unix $INF
sqlite3 portfolio.sqlite "update MPT set target_alloc = NULL;"
while IFS=, read symbol weight
do 
  #echo "Do something with $symbol $weight"
  	sqlite3 portfolio.sqlite "update MPT set target_alloc=round('$weight',15) where symbol = '$symbol'"
  
	weight=`echo $weight*100|bc -l`
	sqlite3 portfolio.sqlite "update prices set alloc_target=round('$weight',4) where symbol = '$symbol'"
done < $INF
