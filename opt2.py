#!/usr/bin/python3
import pprint
import pandas_datareader.data as web
import datetime
from functools import reduce

import yfinance as yf
#import matplotlib.pyplot as plt
import pandas as pd
import numpy as np
from pypfopt import objective_functions

tickers = ["ANGL","ASML","AVGO","AMX","BEN","BG","BRK-B",
"BRT","BSIG","C","CARR","CNHI","D","DBB","DGX","EMB","EVC","EWJ",
"F","FAF","TGS","FNBGX","FTS","GILD",
"JBL","GSL","HUN","INGR","IPI","IPAR","JPIB","KMB","LKOR",
"LYB","MLN","NHC","NXST","SSNC","PBR","PDBC","PLD","PNM","MPW","NVS","REM","RL","SAH",
"SGOL","SNP","SOXX","TAIT","VALE","VCSH","VMC" ]

sector_mapper = {    
    "AMX": "Communication Services",
    "ANGL": "Bonds",
    "ASML": "Tech",
    "AVGO": "Tech",
    "BEN": "Financials",
    "BSIG": "Financials",
    "BG": "Consumer Staples",
    "BRK-B": "Financials",
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
    "TGS": "Energy",
    "FNBGX": "Bonds",
    "GILD": "Healthcare",
    "GIS": "Consumer Staples",
    "GSL": "Industrials",
    "HUN": "Materials",
    "IPI": "Materials",
    "INGR": "Consumer Staples",
    "IPAR": "Consumer Staples",
    "JBL": "Tech",
    "JPIB": "Bonds",
    "KMB": "Consumer Staples",
    "LKOR": "Bonds",
    "LYB": "Materials",
    "MLN": "Bonds",
    "MLI": "Industrials",
    "NHC": "Healthcare",
    "NVS": "Healthcare",
    "NXST": "Communication Services",
    "MPW": "Real Estate",
    "SSNC": "Tech",
    "PDBC": "Commodities",
    "PBR": "Energy",
    "PNM": "Utilities",
    "RL": "Consumer Discretionary",
    "SAH": "Consumer Discretionary",
    "SNP": "Energy",
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
}

sector_lower = {
    "Bonds": 0.38, # at least 5% to tech
    "Commodities": 0.02,
    "Communication Services": .01826,
    "Consumer Discretionary": .07380,
    "Consumer Staples": .03798,
    "Energy": .02216,
    "Financials": .04444,
    "Healthcare": .07319,
    "Industrials": .04405,
    "Materials": .04446,
    "Tech": 0.121 ,
    "Real Estate": .03210,
    "Precious Metals": .0509,
    "Utilities": .03676
}

sector_upper = {
    "Bonds": .47,
    "Commodities": 0.061,
    "Communication Services": .02232,
    "Consumer Discretionary": .09020,
    "Consumer Staples": .04642,
    "Energy": .02709,
    "Financials": .05432,
    "Healthcare": .08946,
    "Industrials": .05383,
    "Materials": .05434,
    "Precious Metals": .061,
    "Real Estate": .03923,
    "Tech": 0.17293,
    "Utilities": .04493
}

#ohlc = yf.download(tickers, start="2012-08-12", end="2022-08-11")
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
