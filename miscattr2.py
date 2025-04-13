#!/usr/bin/python3

import yfinance as yf
import random
import sqlite3
from time import sleep
from random import randint
import requests_cache
import pandas as pd


with open("tickers.txt", "r") as f:
    stocks  = [line.strip() for line in f if not line.startswith("#")]

random.shuffle(stocks)

session = requests_cache.CachedSession("yfinance.cache", expire_after=45)

con = sqlite3.connect("/var/www/html/portfolio/portfolio.sqlite")
cur = con.cursor()

for stock in stocks:
    if stock in ['ANGL','BNDX','DBB','EMB','EWJ','FAGIX','FDGFX','FNBGX','FPE','LKOR','JPIB','MLN','PDBC','PGHY','REM','SGOL','SIVR','SJNK','TDTF','VCSH']:
     continue
   
    info = yf.Ticker(stock,session=session).info
    print("updating ", stock)

    cashflow=yf.Ticker(stock,session=session).quarterly_cashflow
    row = cashflow.loc['Free Cash Flow'] 
    mean_fcf = row.mean()
    row = cashflow.loc['Net Income From Continuing Operations']
    mean_ni = row.mean()

    fcf_ni_r=round((mean_fcf/mean_ni),4)
    
    try:
        industry = info["industry"]
        print("ind: ", industry)
    except KeyError:
        industry = ""
    flag2 = ""

    try:
        beta = info["beta"]
    except KeyError:
        beta = 0

    try:
        recm = info["recommendationKey"]
    except KeyError:
        recommendationKey = 0   

    try:
        marketcap = info["marketCap"]
        marketcap2 = round((marketcap / 1e9), 2)
    except KeyError:
        print("key exception market cap")
        marketcap = 0
        caplabel = "" 

    if marketcap < 3e8:
        caplabel = "Micro"

    if marketcap > 3e8 and marketcap < 2e9:
        caplabel = "Small"

    if marketcap > 2e9 and marketcap < 10e9:
        caplabel = "Medium"

    if marketcap > 10e9 and marketcap < 2e11:
        caplabel = "Large"

    if marketcap > 2e11:
        caplabel = "Mega"   

    try:
        pe = info["trailingPE"]
    except KeyError:
        pe = 0 
    
    try:
        z=info["earningsQuarterlyGrowth"]
        print(z)
    except KeyError:
        continue
    

    print("%s %s beta=%s recm=%s %s pe=%s mean_fcf=%s mean_ni=%s fcf_ni_r= %s" % (stock,industry,beta,recm,caplabel,pe,mean_fcf,mean_ni,fcf_ni_r))

    cur.execute(
        "update MPT set pe = ?,market_cap_val = ?,market_cap = ?,recm = ?,industry = ?, fcf_ni_ratio = ?  where symbol = ?",
        (pe,marketcap,caplabel,recm,industry,fcf_ni_r,stock),
    )
    con.commit()
    i=randint(1,2500)
    int=i/1000
    print("sleep for ",i," ms")
    sleep(int)

