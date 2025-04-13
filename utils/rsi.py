#!/usr/bin/python3
# Copyright (C) 2024 Mike Roetto <mike@roetto.org>
# SPDX-License-Identifier: GPL-3.0-or-later

import pandas as pd
import pandas_ta as ta
import sqlite3

with open("../tickers.txt", "r") as f:
    tickers = [line.strip() for line in f if not line.startswith("#")]

con = sqlite3.connect("/var/www/html/portfolio/portfolio.sqlite")
cur = con.cursor()

for symbol in tickers:
    if symbol == "BRK-B":
        symbol = "BRK.B"
    current_rsi = 0
    fn = symbol + ".csv"

    print("processing ", fn)

    # Read the CSV file with no header
    df = pd.read_csv("../" + fn, header=None)

    # Drop rows with missing data
    df = df.dropna()

    # Calculate the RSI on the second column (index 1)
    df["RSI"] = ta.rsi(df[1])

    # Assign the last RSI value to a variable
    current_rsi = df["RSI"].iloc[-1]

    if current_rsi is not None:
        current_rsi = round(current_rsi, 2)

    print(symbol, current_rsi)
    cur.execute("update MPT set RSI = ? where symbol = ?", (current_rsi, symbol))
    con.commit()
