#!/usr/bin/python3


import json
import sys
import sqlite3

def average_growth_rate(data):
    growth_rates = []
    for i in range(1, len(data)):
        dividend_previous = data[i-1][1]
        dividend_current = data[i][1]
        if dividend_previous != 0:
            growth_rate = (dividend_current - dividend_previous) / dividend_previous
            growth_rates.append(growth_rate)
    if len(growth_rates) > 0:
        return sum(growth_rates) / len(growth_rates)
    else:
        return 0


# Command line argument for ticker
ticker = sys.argv[1]

# Connect to the SQLite database
conn = sqlite3.connect('../portfolio.sqlite')
c = conn.cursor()

# Query the dividend amounts and dates for the stock
import datetime

# Specify today's date and subtract 5 years
two_years_ago_date = datetime.date.today() - datetime.timedelta(days=5*365)

# Convert the date to string format (YYYY-MM-DD)
two_years_ago_str = two_years_ago_date.strftime("%Y-%m-%d")

c.execute(f"SELECT declare_date, amount FROM dividends WHERE symbol = '{ticker}' AND declare_date >= '{two_years_ago_str}' ORDER BY declare_date")

dividend_data = c.fetchall()

print("5 yrs dividend data:\n",dividend_data)

avg_growth_rate = average_growth_rate(dividend_data)

# Update the div_growth_rate column for the stock
c.execute(f"UPDATE MPT SET div_growth_rate = {avg_growth_rate} WHERE symbol = '{ticker}'")

# Commit the changes and close the connection
conn.commit()
conn.close()

print (ticker, avg_growth_rate)
