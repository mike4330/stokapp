#!/usr/bin/python3

import yfinance as yf
import sqlite3

import requests_cache
session = requests_cache.CachedSession('yfinance.cache')


snp=yf.Ticker("0386.hk")

price = snp.fast_info['last_price']



hkd=yf.Ticker("HKDUSD=X")

exrate = hkd.fast_info['last_price']

converted_price = float(price*exrate)

print("current price is ",price," HKD")
print("exrate is ",exrate)
print("USD price is ", converted_price, " USD")

con = sqlite3.connect('/var/www/html/portfolio/portfolio.sqlite')

cur = con.cursor()
sym="ztestz"

stmt = "update prices set price = " +str(converted_price)+   " where symbol = '0386.HK'"

print (stmt)
cur.execute(stmt)
con.commit()
