#!/usr/bin/python3
# RSI (Relative Strength Index) calculator for portfolio holdings
# Calculates RSI technical indicators from CSV price data and updates database
# Copyright (C) 2024 Mike Roetto <mike@roetto.org>
# SPDX-License-Identifier: GPL-3.0-or-later

import pandas as pd
import pandas_ta as ta
import sqlite3

# Read ticker symbols from file, skip commented lines
with open("../tickers.txt", "r") as f:
    tickers = [line.strip() for line in f if not line.startswith("#")]

# Connect to portfolio database
con = sqlite3.connect("/var/www/html/portfolio/portfolio.sqlite")
cur = con.cursor()

# Process each ticker symbol
for symbol in tickers:
    # Handle special case for Berkshire Hathaway ticker format
    if symbol == "BRK-B":
        symbol = "BRK.B"
    
    current_rsi = 0
    fn = symbol + ".csv"
    print("processing", fn)

    try:
        # Read CSV price data (no header expected)
        df = pd.read_csv("../" + fn, header=None)
        
        # Clean data by removing rows with missing values
        df = df.dropna()
        
        # Calculate RSI using pandas_ta on price column (index 1)
        # RSI measures momentum - values > 70 suggest overbought, < 30 oversold
        df["RSI"] = ta.rsi(df[1])
        
        # Get the most recent RSI value
        current_rsi = df["RSI"].iloc[-1]
        
        # Round RSI to 2 decimal places if valid
        if current_rsi is not None:
            current_rsi = round(current_rsi, 2)
        
        print(symbol, current_rsi)
        
        # Update MPT table with calculated RSI value
        cur.execute("update MPT set RSI = ? where symbol = ?", (current_rsi, symbol))
        con.commit()
        
    except FileNotFoundError:
        print(f"Warning: CSV file not found for {symbol}")
        continue
    except Exception as e:
        print(f"Error processing {symbol}: {e}")
        continue
