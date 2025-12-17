#!/bin/bash
# Portfolio lot size and holding period analytics
# Calculates average effective lot sizes and holding periods for position sizing analysis

cd /var/www/html/portfolio/utils

# Query to calculate average effective units for open positions
# Uses units_remaining if available (partial sales), otherwise uses original units
QRY="SELECT round(AVG(effective_units),4) AS average_effective_units
FROM (
    SELECT 
        CASE
            WHEN units_remaining IS NOT NULL THEN units_remaining
            ELSE units
        END AS effective_units
    FROM transactions
	where xtype = 'Buy' and disposition IS NULL
) subquery"

# Execute query and get average lot size
z=`sqlite3 ../portfolio.sqlite "$QRY"`

# Display and log average lot size with timestamp
echo "Average lot size for $(date +%Y-%m-%d): $z units"
echo `date +%Y-%m-%d` $z >> ../lotsize.log

# Calculate average holding period for open positions
# Uses Julian day difference between now and purchase date
QRY="select avg(julianday('now')-julianday(date_new)) from open_lots"
z=`sqlite3 ../portfolio.sqlite "$QRY"`

# Log average holding period
echo "Average holding period: $z days"
echo `date +%Y-%m-%d` $z >> ../lotlength.log


