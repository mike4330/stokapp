#!/usr/bin/python3

import pandas as pd
import pandas_ta as ta
import sqlite3

tickers = [
    "ANGL",
    "AVGO",
    "AMX",
    "BRK-B",
    "ASML",
    "BG",
    "BRT",
    "BSIG",
    "C",
    "CARR",
    "BAH",
    "D",
    "DBB",
    "DGX",
    "EMB",
    "EVC",
    "EWJ",
    "F",
    "FAF",
    "FAGIX",
    "FNBGX",
    "FDGFX",
    "FPE",
    "FRG",
    "FTS",
    "GILD",
    "HTLD",
    "HUN",
    "INGR",
    "IPAR",
    "JPIB",
    "KMB",
    "HPK",
    "LKOR",
    "LYB",
    "MLN",
    "MPW",
    "NHC",
    "NICE",
    "NVS",
    "NXST",
    "OTIS",
    'PANW',
    "PBR",
    "PDBC",
    "PLD",
    "PNM",
    "REM",
    "SCI",
    "SGOL",
    "SIVR",
    "SJNK",
    "SSNC",
    "TAIT",
    "TSLA",
    "ULTA",
    "TGS",
    "VALE",
    "VCSH",
    "VMC",
]

con = sqlite3.connect('/var/www/html/portfolio/portfolio.sqlite')
cur = con.cursor()

for symbol in tickers:
    if (symbol == 'BRK-B') :
        symbol = 'BRK.B'
    current_rsi = 0
    fn = symbol + ".csv"



    # Read the CSV file with no header
    df = pd.read_csv('../'+fn, header=None)

    # Drop rows with missing data
    df = df.dropna()

    # Calculate the RSI on the second column (index 1)
    df['RSI'] = ta.rsi(df[1])

    # Assign the last RSI value to a variable
    current_rsi = df['RSI'].iloc[-1]
    
    if current_rsi is not None:
        current_rsi=round(current_rsi,2)

    print (symbol,current_rsi)
    cur.execute("update MPT set RSI = ? where symbol = ?",(current_rsi,symbol))
    con.commit()

    from termcolor import colored

    # # Print the current RSI value to the console
    # if current_rsi is not None and current_rsi > 70 :
    #     # print("symbol overbought ",symbol," ",current_rsi)
    #     print(colored(symbol+" overbought",'white','on_blue'),current_rsi)
    # if current_rsi is not None and current_rsi < 30 :
    #     # print("symbol oversold ",symbol," ",current_rsi)
    #     print(colored(symbol+" oversold",'black','on_green'),current_rsi)
    
