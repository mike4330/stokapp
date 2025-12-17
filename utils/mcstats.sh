#!/bin/bash
# Current market cap allocation statistics generator
# Analyzes portfolio value distribution across market cap categories (Micro, Small, Medium, Large, Mega)

cd /var/www/html/portfolio || exit

# Database command setup
dbc='sqlite3 /var/www/html/portfolio/portfolio.sqlite'

# Calculate total portfolio value from most recent security_values
pvq='select sum(close*shares) from security_values group by timestamp order by timestamp desc limit 1'
pv=$($dbc "$pvq")

echo "Total portfolio value: $pv"

# Analyze allocation across each market cap category
for class in Micro Small Medium Large Mega; do
	echo "Processing $class cap stocks..."
	
	# Query to calculate allocation for this market cap class
	# Gets: timestamp, market_cap, total value, percentage of portfolio
	q="select timestamp,market_cap,sum(close*shares),sum(close*shares)/$pv
	from security_values,MPT
	where MPT.symbol IN (select MPT.symbol from MPT where market_cap = '$class')
	and security_values.symbol = MPT.symbol
	group by timestamp
	order by timestamp desc limit 1"

	# Append results to market cap allocation log
	$dbc "$q" >> marketcap.log
done
