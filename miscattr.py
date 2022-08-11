#!/usr/bin/python3

import yfinance as yf
import pandas as pd
import requests_cache
import sqlite3


session = requests_cache.CachedSession('yfinanceinfo.cache',expire_after=86400)
#session = requests_cache.CachedSession(expire_after=86400)

stocks = ['ASML','AVGO','AMX','BEN','BRK-B','BG','BRT','BSIG','C',
'CNHI','D','DGX','CARR','F','FAF','TGS','FTS','GILD','GSL','HUN','INGR',
'IPAR','IPI','JBL','KMB','LYB','MLI','NHC','NVS','NXST','PBR','PLD','PNM',
'RL','TAIT','EVC','SAH','SNP','SSNC','VALE','VMC','MPW']

con = sqlite3.connect('/var/www/html/portfolio/portfolio.sqlite')
cur = con.cursor()

df = pd.DataFrame()

for stock in stocks: 
    info = yf.Ticker(stock,session=session).info
    print("updating ",stock)
    #industry = info['industry']
    flag2=''
    try:
        beta = info['beta']
    except KeyError:
        beta = 0
    try:
        pe = info['trailingPE']
    except KeyError:
        pe=0 
    try:
        dy = info['dividendYield']    
    except KeyError:
        dividendYield=0;
    divamt=info['lastDividendValue']
    yrH = info['fiftyTwoWeekHigh']
    close=info['previousClose']
    logo = info['logo_url']    
    range = (close/yrH)
    a200=info['twoHundredDayAverage']
    a50=info['fiftyDayAverage']

    if close > a200 and close > a50:
        flag2="A50A200"
        print("above both",flag2)
    
    if close < a200 and close < a50:
        flag2="B50B200"
        print("below both",flag2)
    
    if close < a200 and close > a50:
        flag2="A50B200"
        print("mixed",flag2)
    
    if close > a200 and close < a50:
        flag2="A200B50"
        print("mixed",flag2)
    
    try:
        marketcap = info['marketCap']
    except KeyError:
        print ("key exception")
        marketcap = 0 
        caplabel=''    
    
    if marketcap < 3e+8:  
        caplabel = "Micro" 
    
    if marketcap > 3e+8 and marketcap < 2e+9:
        caplabel = "Small"
    
    if marketcap > 2e+9 and marketcap < 10e+9:
        caplabel = "Medium"
     
    if marketcap > 10e+9 and marketcap < 2e+11:
        caplabel = "Large"
    
    if marketcap > 2e+11:
        caplabel = "Mega"
    
    print(stock,marketcap,caplabel,range)
    sector = info['sector']
    industry = info['industry']

    df =df.append(
        {'Stock':stock,'PE':pe,'Beta':beta,'dy':dy,'range':range,'marketcap':marketcap,'sector':sector,'industry':industry,'logo':logo,'divamt':divamt}, ignore_index=True)
    cur.execute("update MPT set range = ?,  industry = ? ,market_cap = ?, avgflag = ? where symbol = ?",(range,industry,caplabel,flag2,stock))
    con.commit()
    
print(df)

df.to_csv("miscinfo.csv", index=False)
