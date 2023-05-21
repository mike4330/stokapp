#!/usr/bin/python3
import sqlite3
import syslog
import yfinance as yf
import datetime
import time
import requests_cache
from random import randint
from time import sleep

session = requests_cache.CachedSession("yfinance.cache", expire_after=45)
now = datetime.datetime.now()
print(now.year, now.month, now.day, now.hour, now.minute, now.second)
# 2015 5 6 8 53 40

elapsed = 0

if now.hour > 17:
    print("outside of time window current time is ", now.hour, now.minute)
    exit()

con = sqlite3.connect("/var/www/html/portfolio/portfolio.sqlite")
cur = con.cursor()

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
    "PBR",
    "PDBC",
    "PLD",
    "PNM",
    "REM",
    "SCI",
    "SGOL",
    "SIVR",
    "SOXX",
    "SSNC",
    "TAIT",
    "TGS",
    "VALE",
    "VCSH",
    "VMC",
]

hour = now.hour
while hour < 17:
    starttime = time.time()

    msg = "[stockprice] starting cycle"

    syslog.syslog(msg)

    for ticker in tickers:
        stock = yf.Ticker(ticker, session=session)

        try:
            price = stock.fast_info["last_price"]
        except:
            print("data download fail for ", ticker)
            continue

        if price == None:
            print("bad data for ", ticker, " cont")
            continue

        ts = time.time()
        print(ts, ticker, price)
        msg = "[stockprice] update" + ticker + str(price)
        syslog.syslog(msg)

        if ticker == "BRK-B":
            ticker = "BRK.B"

        try:
            cur.execute(
                "update prices set price = ?, lastupdate = ? where symbol = ?",
                (price, ts, ticker),
            )
            con.commit()
        except:
            print("temp fail")

        elapsed = round((ts - starttime), 1)
        sleep(randint(2, 7))

    print("elapsed cycle time ", elapsed)

    msg = "[stockprice] finished cycle. elapsed time: " + str(elapsed)

    syslog.syslog(msg)

    time.sleep(90)
    now = datetime.datetime.now()
    hour = now.hour


# while minute < 46 :
# print "test"
# time.sleep(5)
# now = datetime.datetime.now()
# minute = now.minute
