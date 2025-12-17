#!/bin/bash
# New symbol initialization script
# Adds a new trading symbol to all necessary database tables and ticker files
# Usage: ./newsymbol.sh SYMBOL

cd /var/www/html/portfolio

# Validate input parameter
if [ -z "$1" ]; then
    echo "Usage: $0 SYMBOL"
    echo "Initializes a new symbol in the portfolio system"
    exit 1
fi

# Database command setup
CMD="sqlite3 /var/www/html/portfolio/portfolio.sqlite"

echo "Initializing new symbol: $1"

# Insert symbol into all required database tables
echo "Adding to prices table..."
$CMD "insert into prices (symbol) values ('$1')"

echo "Adding to MPT table..."
$CMD "insert into MPT (symbol) values ('$1')"

echo "Adding to sectors table..."
$CMD "insert into sectors (symbol) values ('$1')"

echo "Adding to asset_classes table..."
$CMD "insert into asset_classes (symbol) values ('$1')"

# Add symbol to ticker files for monitoring systems
echo "Adding to ticker files..."
echo $1 >> tickers.txt       # General ticker list
echo $1 >> tickersloop.txt   # Price monitoring loop ticker list

echo "Symbol $1 successfully initialized in portfolio system"
