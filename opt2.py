#!/usr/bin/python3
import pprint
import pandas_datareader.data as web
import datetime
from functools import reduce
from datetime import date

import yfinance as yf
import matplotlib.pyplot as plt
import pandas as pd
import numpy as np
from pypfopt import objective_functions
from pypfopt import CLA, plotting

tickers = ["ANGL","ASML","AVGO","AMX","BEN","BG","BRK-B","FRG",
"BRT","BSIG","C","CARR","CNHI","D","DBB","DGX","EMB","EVC",
"EWJ","F","FAF","FPE","TGS",
"FNBGX","FAGIX",
"FTS","GILD","GSL",
"HUN","INGR","IPAR","JPIB","KMB","HTLD","LYB","MLN","NHC","NICE",
"NXST","SSNC","PBR","PDBC","PLD","PNM","MPW","NVS","REM",
"SAH","SCI","SGOL","OTIS","0386.HK","SOXX","TAIT","VALE","VCSH","VMC",  ]

sector_mapper = {    
    "AMX": "Communication Services",
    "ANGL": "Bonds",
    "ASML": "Tech",
    "AVGO": "Tech",
    "BEN": "Financials",
    "BSIG": "Financials",
    "BG": "Consumer Staples",
    "BRK-B": "Financials",
    "FBMS": "Financials",
    "BRT": "Real Estate",
    "C": "Financials",
    "CARR": "Industrials",
    "CNHI": "Industrials",
    "DBB": "Commodities",
    "DGX": "Healthcare",
    "EMB": "Bonds",
    "EVC": "Communication Services",
    "EWJ": "Industrials",
    "F": "Consumer Discretionary",
    "FAF": "Financials",
    "FPE": "Bonds",
    "TGS": "Energy",
    "FNBGX": "Bonds",
    "FAGIX": "Bonds",
    "GILD": "Healthcare",
    "GIS": "Consumer Staples",
    "GSL": "Industrials",
    "HUN": "Materials",
    "HTLD": "Materials",
    "INGR": "Consumer Staples",
    "IPAR": "Consumer Staples",
    "JPIB": "Bonds",
    "KMB": "Consumer Staples",
    "LYB": "Materials",
    "MLN": "Bonds",
    "NHC": "Healthcare",
    "NVS": "Healthcare",
    "NICE": "Tech",
    "NXST": "Communication Services",
    "MPW": "Real Estate",
    "OTIS": "Industrials",
    "SSNC": "Tech",
    "PDBC": "Commodities",
    "PBR": "Energy",
    "PNM": "Utilities",
    "SAH": "Consumer Discretionary",
    "SCI": "Consumer Discretionary",
    "0386.HK": "Energy",
    "SGOL": "Precious Metals",
    "VALE": "Materials",
    "VMC": "Materials",
    "PLD": "Real Estate",
    "REM": "Real Estate",
    "TAIT": "Tech",
    "SOXX": "Tech",
    "VCSH": "Bonds",
    "FTS": "Utilities",
    "D": "Utilities",
    "PNM": "Utilities",
    "FRG": "Consumer Discretionary"
}

#use only 5-10 yr. avg now

sector_lower = {
    "Bonds": 0.355,     
    "Commodities": 0.025,
    "Communication Services": .001636,
    "Consumer Discretionary": .083791,
    "Consumer Staples": .041259,
    "Energy": .006627,
    "Financials": .028536,
    "Healthcare": .081285,
    "Industrials": .024663,
    "Materials": .032810,
    "Tech": 0.151344 ,
    "Real Estate": .002150,
    "Precious Metals": .05,
    "Utilities": .037139
}

sector_upper = {
    "Bonds": .47,
    "Commodities": 0.061,
    "Communication Services": .001999,
    "Consumer Discretionary": .102411,
    "Consumer Staples": .050427,
    "Energy": .008100,
    "Financials": .034877,
    "Healthcare": .099348,
    "Industrials": .034877,
    "Materials": .040102,
    #"Precious Metals": .061,
    "Real Estate": .002627,
    "Tech": 0.184976,
    "Utilities": .045392
}

#ohlc = yf.download(tickers, start="2012-09-26", end="2022-09-28")
#prices = ohlc["Adj Close"].dropna(how="all")
#prices.to_csv("pricedataset.csv", index=True)

import sys
gammainput = sys.argv[1]
rgoal = float(sys.argv[2])
lb = float(sys.argv[3])


prices = pd.read_csv("pricedataset.csv",parse_dates=True, index_col="Date")
#print(prices)


#### semivariance
#from pypfopt import expected_returns, EfficientSemivariance

#df = prices
#mu = expected_returns.mean_historical_return(prices)
#historical_returns = expected_returns.returns_from_prices(prices)

#es = EfficientSemivariance(mu, historical_returns,weight_bounds=(lb, .1))
#es.add_objective(objective_functions.L2_reg, gamma=gammainput)
#es.add_sector_constraints(sector_mapper, sector_lower, sector_upper)
#es.efficient_return(rgoal)


from pypfopt.efficient_frontier import EfficientFrontier
from pypfopt.expected_returns import mean_historical_return
from pypfopt.risk_models import CovarianceShrinkage
from pypfopt import objective_functions
from pypfopt import expected_returns, EfficientSemivariance

mu = mean_historical_return(prices)
S = CovarianceShrinkage(prices).ledoit_wolf()
ef = EfficientFrontier(mu, S,weight_bounds=(lb,.06),verbose=False)


#print(mu)


ef.add_sector_constraints(sector_mapper, sector_lower, sector_upper)
ef.add_objective(objective_functions.L2_reg, gamma=gammainput)

#weights = ef.max_sharpe()
#weights=ef.min_volatility()
#weights=ef.efficient_return(target_return=rgoal, market_neutral=False)
weights=ef.efficient_risk(rgoal)

print("------------------------------")



# We can use the same helper methods as before
#weights = es.clean_weights()
#pprint.pprint(weights)
#es.portfolio_performance(verbose=True)
ef.portfolio_performance(verbose=True)


#cla = CLA(mu, S)
#cla.max_sharpe()
#fig, ax = plt.subplots()
plt.tight_layout()
plt.grid()
#plotting.plot_efficient_frontier(cla, showfig=False,ax=ax,show_tickers="True",filename="ef.png")


print("------------------------------")
for sector in set(sector_mapper.values()):
    total_weight = 0
    for t,w in weights.items():
        if sector_mapper[t] == sector:
            total_weight += w
    print(f"{sector}: {total_weight:.3f}")


import csv

with open('semivariance.csv', 'w', newline='') as csvfile:
    fieldnames = ['symbol', 'weight']
    writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
    writer.writeheader()
    for key in weights:
        writer.writerow({'symbol': key, 'weight': weights[key]})
today = date.today()


#with open("weights.log", 'a') as f: 
    #for key, value in weights.items(): 
        #f.write("%s %s %s\n" % (today, key, value))


