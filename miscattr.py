#!/usr/bin/python3
import warnings
warnings.simplefilter(action='ignore', category=FutureWarning)
import yfinance as yf
import pandas as pd
import requests_cache
import sqlite3


session = requests_cache.CachedSession('yfinanceinfo.cache',expire_after=42400)
#session = requests_cache.CachedSession(expire_after=86400)

stocks = ['AMX','ASML','AVGO','BEN','BRK-B','BG','BRT','BSIG','C',
'CNHI','D','DGX','CARR','F','FAF','FRG','HPK','TGS','FTS','GILD','GSL','HUN','INGR',
'IPAR','HTLD','KMB','LYB','NHC','NICE','NVS','NXST','OTIS','PBR','PLD','PNM',
'TAIT','EVC','SAH','SCI','SSNC','VALE','VMC','MPW']

con = sqlite3.connect('/var/www/html/portfolio/portfolio.sqlite')
cur = con.cursor()

df = pd.DataFrame()

for stock in stocks: 
    info = yf.Ticker(stock,session=session).info
    #print("updating ",stock)
    try:
    	industry = info['industry']
    except KeyError:
     industry = ''
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
    #divamt=info['lastDividendValue']
    yrH = info['fiftyTwoWeekHigh']
    close=info['previousClose']
    logo = info['logo_url']    
    range = round((close/yrH),4)
    a200=info['twoHundredDayAverage']
    a50=info['fiftyDayAverage']

    if close > a200 and close > a50:
        flag2="A50A200"
        #print("above both",flag2)
    
    if close < a200 and close < a50:
        flag2="B50B200"
        #print("below both",flag2)
    
    if close < a200 and close > a50:
        flag2="A50B200"
        #print("mixed",flag2)
    
    if close > a200 and close < a50:
        flag2="A200B50"
        #print("mixed",flag2)
    
    try:
        marketcap = info['marketCap']
    except KeyError:
        print ("key exception")
        marketcap = 0 
        caplabel=''    
    
    marketcap2=round((marketcap/1e9),2)
        
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
    
    industry = info['industry']
    sector = info['sector']
    recm=info['recommendationKey']
    print(stock,"\t$",marketcap2,"B",caplabel,"\t52 range",range,recm)
	
    if stock == 'BRK-B':
     stock = 'BRK.B'
     print("brk fix")

    df =df.append(
        {'Stock':stock,'range':range,'marketcap':marketcap,'sector':sector,'industry':industry,'logo':logo}, ignore_index=True)
    cur.execute("update MPT set recm = ?,beta = ?,pe = ?,range = ?,  industry = ? ,market_cap = ?, avgflag = ? where symbol = ?",(recm,beta,pe,range,industry,caplabel,flag2,stock))
    con.commit()
    
print(df)

df.to_csv("miscinfo.csv", index=False)
