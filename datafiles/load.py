#!/home/mike/.pyenv/shims/python3

import sqlite3
import json
import sys

# Get the JSON filename from the command line
json_filename = sys.argv[1]

# Connect to the SQLite database (it will be created if it doesn't exist)
conn = sqlite3.connect('../portfolio.sqlite')

# Create a new cursor
c = conn.cursor()



# Load the JSON data from the file
with open(json_filename, 'r') as f:
    data = json.load(f)

for result in data['results']:
    symbol = result['ticker']
    declare_date = result['declaration_date']
    amount = result['cash_amount']

    # Insert the data into the table, or update if the row already exists
    c.execute('''
        INSERT INTO dividends (symbol, declare_date, amount)
        VALUES (?, ?, ?)
        ON CONFLICT(symbol, declare_date) DO UPDATE SET
        amount = excluded.amount
    ''', (symbol, declare_date, amount))

    # Commit the changes after each insert
    conn.commit()

# Close the connection
conn.close()

import yfinance as yf
import requests_cache
session = requests_cache.CachedSession('yfinanceinfo3.cache')

print("symbol is",symbol)

ticker = yf.Ticker(symbol)
dy=ticker.info["dividendYield"]

print("dy for",symbol,"is",dy)

conn = sqlite3.connect('../portfolio.sqlite')
c = conn.cursor()
c.execute(f"UPDATE prices SET divyield = {dy} WHERE symbol = '{symbol}'")
conn.commit()
conn.close()



