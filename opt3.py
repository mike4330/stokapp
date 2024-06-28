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

# yf.enable_debug_mode()

with open("tickers.txt", "r") as f:
    tickers = [line.strip() for line in f if not line.startswith("#")]

with open("sectormap.txt", "r") as f:
    sector_mapper = dict(line.strip().split(",") for line in f)

bonds_total = 0.3
dbonds_lower = bonds_total * 0.695
fbonds_lower = bonds_total * 0.305

dbonds_upper = dbonds_lower + 0.01
fbonds_upper = fbonds_lower + 0.01


sector_lower = {
    "DBonds": dbonds_lower,
    "FBonds": fbonds_lower,
    "Commodities": 0.025,
    "Misc": 0.0125,
    "Communication Services": 0.0527,
    "Consumer Discretionary": 0.0557,
    "Consumer Staples": 0.0557,
    "Energy": 0.0433,
    "Financials": 0.0557,
    "Healthcare": 0.0557,
    "Industrials": 0.0557,
    "Materials": 0.0557,
    "Tech": 0.0775,
    "Real Estate": 0.0557,
    "Precious Metals": 0.05,
    "Utilities": 0.0491,
}

sector_upper = {
    "DBonds": dbonds_upper,
    "FBonds": fbonds_upper,
    "Commodities": 0.061,
    "Communication Services": 0.061818,
    "Consumer Discretionary": 0.061818,
    "Consumer Staples": 0.061818,
    "Energy": 0.061818,
    "Financials": 0.061818,
    "Healthcare": 0.081818,
    "Industrials": 0.061818,
    "Materials": 0.061818,
    # "Precious Metals": .061,
    "Real Estate": 0.061818,
    "Tech": 1,
    "Utilities": 0.061818,
}
import requests_cache

session = requests_cache.CachedSession("yfinance.cache")


from datetime import date, timedelta
from datetime import datetime

today = date.today()
now = datetime.now()
# Calculate date 10 years ago from today
ten_years_ago = today - timedelta(days=365 * 10)

ohlc = yf.download(tickers, start=ten_years_ago,end=today,interval="1d",ignore_tz=True,session=session)
prices = ohlc["Adj Close"].dropna(how="all")
prices.to_csv("pricedataset.csv", index=True)

import sys

gammainput = sys.argv[1]
rgoal = float(sys.argv[2])
lb = float(sys.argv[3])
ub = float(sys.argv[4])

prices = pd.read_csv("pricedataset.csv", parse_dates=True, index_col="Date")

prices = prices[prices.columns.intersection(tickers)]

# print(prices)

#### semivariance
# from pypfopt import expected_returns, EfficientSemivariance

# df = prices
# mu = expected_returns.mean_historical_return(prices)
# historical_returns = expected_returns.returns_from_prices(prices)

# es = EfficientSemivariance(mu, historical_returns,weight_bounds=(lb, .1))
# es.add_objective(objective_functions.L2_reg, gamma=gammainput)
# es.add_sector_constraints(sector_mapper, sector_lower, sector_upper)
# es.efficient_return(rgoal)

from pypfopt.efficient_frontier import EfficientFrontier
from pypfopt.expected_returns import mean_historical_return
from pypfopt.risk_models import CovarianceShrinkage
from pypfopt import objective_functions
from pypfopt import expected_returns, EfficientSemivariance

mu = mean_historical_return(prices)
S = CovarianceShrinkage(prices).ledoit_wolf()
ef = EfficientFrontier(mu, S, weight_bounds=(lb, ub), verbose=False)

# print(mu)

ef.add_sector_constraints(sector_mapper, sector_lower, sector_upper)
ef.add_objective(objective_functions.L2_reg, gamma=gammainput)

# weights = ef.max_sharpe()
# weights=ef.min_volatility()
# weights=ef.efficient_return(target_return=rgoal, market_neutral=False)
weights = ef.efficient_risk(rgoal)

print("------------------------------")

# We can use the same helper methods as before
# weights = es.clean_weights()
# pprint.pprint(weights)
# es.portfolio_performance(verbose=True)
ef.portfolio_performance(verbose=True)

# cla = CLA(mu, S)
# cla.max_sharpe()
# fig, ax = plt.subplots()
# plt.tight_layout()
# plt.grid()
# plotting.plot_efficient_frontier(cla, showfig=False,ax=ax,show_tickers="True",filename="ef.png")

print("------------------------------")
for sector in set(sector_mapper.values()):
    total_weight = 0
    for t, w in weights.items():
        if sector_mapper[t] == sector:
            total_weight += w
    print(f"{sector}: {total_weight:.3f}")

import csv

with open("semivariance.csv", "w", newline="") as csvfile:
    fieldnames = ["symbol", "weight"]
    writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
    writer.writeheader()
    for key in weights:
        writer.writerow({"symbol": key, "weight": weights[key]})
today = date.today()

with open("weights.log", "a") as f:
    for key, value in weights.items():
        f.write("%s %s %s\n" % (today, key, value))

# model perfm log
expected_return, volatility, sharpe_ratio, *_ = ef.portfolio_performance(verbose=False)
expected_return = round(expected_return,6)
sharpe_ratio = round(sharpe_ratio,6)

with open("modelrun.log", "a") as w:
    w.write(
        "%s,upper %s,lower %s,gamma %s,maxvolat %s," % (now, ub, lb, gammainput, rgoal)
    )
    w.write("%s,%s,%s\n" % (expected_return, volatility, sharpe_ratio))

import sqlite3

conn = sqlite3.connect('portfolio.sqlite')
cursor = conn.cursor()

for key, value in weights.items():
    if (key == 'BRK-B') :
        key = 'BRK.B'

    cursor.execute("INSERT INTO weights (timestamp, symbol, weight) VALUES (?, ?, ?)",
                   (today, key, value))

conn.commit()
conn.close()
