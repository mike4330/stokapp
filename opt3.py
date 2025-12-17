#!/usr/bin/python3
"""
Portfolio Optimization Script (opt3.py)
This script performs portfolio optimization using PyPortfolioOpt library.
It handles sector constraints, risk management, and historical data analysis.
"""

import argparse
import sys
from datetime import date, datetime, timedelta

# Parse command line arguments early for report mode
parser = argparse.ArgumentParser(description='Portfolio Optimization Script')
parser.add_argument('gamma', type=float, nargs='?', default=0.0, help='Gamma parameter for regularization')
parser.add_argument('rgoal', type=float, nargs='?', default=0.0, help='Target return/risk goal')
parser.add_argument('lb', type=float, nargs='?', default=0.0, help='Lower bound for individual asset weights')
parser.add_argument('ub', type=float, nargs='?', default=0.0, help='Upper bound for individual asset weights')
parser.add_argument('-x', '--no-io', action='store_true',
                    help='Disable all downloads, database writes, and logfile writes')
parser.add_argument('-r', '--report', action='store_true',
                    help='Display sector constraint summary and exit')
args = parser.parse_args()

# Set flag for disabling I/O operations
DISABLE_IO = args.no_io

if DISABLE_IO:
    print("*** Running in no-I/O mode: downloads, database writes, and logfile writes are DISABLED ***")
    print()

# Load tickers and sector mapping from configuration files
with open("tickers.txt", "r") as f:
    tickers = [line.strip() for line in f if not line.startswith("#")]

with open("sectormap.txt", "r") as f:
    sector_mapper = dict(line.strip().split(",") for line in f)

# Define bond allocation parameters
bonds_total = 0.215
dbonds_lower = bonds_total * 0.695
fbonds_lower = bonds_total * 0.305

dbonds_upper = dbonds_lower + 0.01
fbonds_upper = fbonds_lower + 0.01

print("dbondslower,",dbonds_lower)
print("fbondslower,",fbonds_lower)

tolerance = 0.01
baseline_lower = .0621

# Define sector allocation constraints
sector_lower = {
    "DBonds": dbonds_lower,
    "FBonds": fbonds_lower,
"Commodities":0.025,
"Communication Services":baseline_lower,
"Consumer Discretionary":baseline_lower,
"Consumer Staples":baseline_lower,
"Energy":baseline_lower,
"Financials":baseline_lower,
"Healthcare":baseline_lower,
"Industrials":baseline_lower,
"Materials":baseline_lower,
"Tech":0.0659,
"Real Estate":0.0579,
"Precious Metals":0.0433,
"Utilities":0.0592,
}


sector_upper = {
    "DBonds": dbonds_upper,
    "FBonds": fbonds_upper,
    "Commodities": 0.059,
    "Communication Services": (tolerance + baseline_lower),
    "Consumer Discretionary": (tolerance + baseline_lower),
    "Consumer Staples": (tolerance + baseline_lower),
    "Energy": (tolerance + baseline_lower),
    "Financials": (tolerance + baseline_lower),
    "Healthcare": (tolerance + baseline_lower),
    "Industrials": 0.0674,
    "Materials": 0.0674,
    "Precious Metals": .0596,
    "Real Estate": 0.0640,
    "Tech": .072,
    "Utilities": 0.0646,
}

def print_sector_report():
    """
    Prints a summary report of sector constraints including totals and ranges.
    """
    print("\n" + "=" * 80)
    print("SECTOR CONSTRAINT SUMMARY")
    print("=" * 80)

    # Calculate totals
    total_lower = sum(sector_lower.values())
    total_upper = sum(sector_upper.values())

    print(f"\nTotal Lower Bound: {total_lower:.4f} ({total_lower*100:.2f}%)")
    print(f"Total Upper Bound: {total_upper:.4f} ({total_upper*100:.2f}%)")
    print(f"Total Range:       {total_upper - total_lower:.4f} ({(total_upper - total_lower)*100:.2f}%)")

    print("\n" + "-" * 80)
    print(f"{'Sector':<25} {'Lower':>12} {'Upper':>12} {'Range':>12} {'Mid':>12}")
    print("-" * 80)

    # Get all sectors and sort them alphabetically
    all_sectors = sorted(set(sector_lower.keys()) | set(sector_upper.keys()))

    for sector in all_sectors:
        lower = sector_lower.get(sector, 0.0)
        upper = sector_upper.get(sector, 0.0)
        range_val = upper - lower
        mid_val = (lower + upper) / 2

        print(f"{sector:<25} {lower:>11.4f} {upper:>11.4f} {range_val:>11.4f} {mid_val:>11.4f}")

    print("-" * 80)
    print(f"{'TOTALS':<25} {total_lower:>11.4f} {total_upper:>11.4f} {total_upper-total_lower:>11.4f}")
    print("=" * 80 + "\n")

# Check if report mode is enabled
if args.report:
    print_sector_report()
    sys.exit(0)

# Heavy imports only if not in report mode
import csv
import pprint
import sqlite3

# Set matplotlib to use non-interactive backend before importing pyplot
import matplotlib
matplotlib.use('Agg')  # Use non-interactive backend for headless operation
import matplotlib.pyplot as plt

# Third-party imports
import numpy as np
import pandas as pd
import requests_cache
import yfinance as yf
from curl_cffi import requests

# PyPortfolioOpt imports
from pypfopt import CLA, expected_returns, objective_functions, plotting
from pypfopt.efficient_frontier import EfficientFrontier
from pypfopt.expected_returns import mean_historical_return
from pypfopt.risk_models import CovarianceShrinkage
from pypfopt import EfficientSemivariance

# yf.enable_debug_mode()

# Set up caching for API requests
session = requests_cache.CachedSession("yfinance.cache")
session = requests.Session(impersonate="chrome")

# Calculate date range for historical data
today = date.today()
now = datetime.now()
# Calculate date 10 years ago from today
ten_years_ago = today - timedelta(days=365 * 10)

# Download and process historical price data
if not DISABLE_IO:
    ohlc = yf.download(tickers, start=ten_years_ago, end=today, session=session)
    #prices = ohlc["Adj Close"].dropna(how="all")
    prices = ohlc["Close"].dropna(how="all")
    prices.to_csv("pricedataset.csv", index=True)
else:
    print("Skipping download - using existing pricedataset.csv")

# Set parameters from command line arguments
gammainput = args.gamma
rgoal = args.rgoal
lb = args.lb
ub = args.ub

# Load and filter price data
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

# Portfolio optimization setup
from pypfopt.efficient_frontier import EfficientFrontier
from pypfopt.expected_returns import mean_historical_return
from pypfopt.risk_models import CovarianceShrinkage
from pypfopt import objective_functions
from pypfopt import expected_returns, EfficientSemivariance

# Calculate expected returns and covariance matrix
mu = mean_historical_return(prices)

def write_expected_returns_db(mu, run_id):
    """
    Writes expected returns for each security to the database.

    Args:
        mu: Expected returns Series (from mean_historical_return)
        run_id: The run_id from mpt_results table
    """
    if DISABLE_IO:
        print(f"Skipping expected returns database write (no-I/O mode)")
        return

    conn = sqlite3.connect("portfolio.sqlite")
    cursor = conn.cursor()

    for ticker, er_value in mu.items():
        cursor.execute(
            "INSERT INTO expected_returns (run_id, symbol, expected_return) VALUES (?, ?, ?)",
            (run_id, ticker, float(er_value))
        )

    conn.commit()
    conn.close()
    print(f"Expected returns written to database for run_id {run_id}")

S = CovarianceShrinkage(prices).ledoit_wolf()
ef = EfficientFrontier(mu, S, weight_bounds=(lb, ub), verbose=False)

# Apply constraints and optimize
ef.add_sector_constraints(sector_mapper, sector_lower, sector_upper)
#ef.add_objective(objective_functions.L2_reg, gamma=gammainput)
ef.add_objective(objective_functions.L2_with_weight_smoothing, gamma_l2=gammainput, gamma_smooth=0.37)
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

# Save results to CSV
if not DISABLE_IO:
    import csv
    with open("semivariance.csv", "w", newline="") as csvfile:
        fieldnames = ["symbol", "weight"]
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
        writer.writeheader()
        for key in weights:
            writer.writerow({"symbol": key, "weight": weights[key]})
else:
    print("Skipping CSV write (no-I/O mode)")

# Store model run results in database
expected_return, volatility, sharpe_ratio, *_ = ef.portfolio_performance(verbose=False)

# Calculate standard deviation of weights
weights_values = np.array(list(weights.values()))
weights_stdev = np.std(weights_values)

# Insert into mpt_results and get run_id
if not DISABLE_IO:
    conn = sqlite3.connect("portfolio.sqlite")
    cursor = conn.cursor()
    cursor.execute(
        """INSERT INTO mpt_results
           (timestamp, upper_bound, lower_bound, gamma, max_volatility,
            expected_return, volatility, sharpe_ratio, weights_stdev)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)""",
        (now, ub, lb, gammainput, rgoal, expected_return, volatility, sharpe_ratio, weights_stdev)
    )
    run_id = cursor.lastrowid
    conn.commit()
    conn.close()

    print(f"Model run saved to database with run_id: {run_id}")

    # Write expected returns to database
    write_expected_returns_db(mu, run_id)
else:
    print("Skipping database writes (no-I/O mode)")
    run_id = None  # Set to None when not writing to database

# Legacy log files (kept for backwards compatibility)
if not DISABLE_IO:
    today = date.today()
    with open("weights.log", "a") as f:
        for key, value in weights.items():
            f.write("%s %s %s\n" % (today, key, value))

    with open("modelrun.log", "a") as w:
        w.write(
            "%s,upper %s,lower %s,gamma %s,maxvolat %s," % (now, ub, lb, gammainput, rgoal)
        )
        w.write("%s,%s,%s,%s\n" % (expected_return, volatility, sharpe_ratio, weights_stdev))
else:
    print("Skipping logfile writes (no-I/O mode)")

# Plot efficient frontier
def plot_efficient_frontier_custom(mu, S, weights, tickers, filename="ef.png"):
    """
    Creates a custom visualization of the efficient frontier with individual assets.
    
    Args:
        mu: Expected returns
        S: Covariance matrix
        weights: Optimal portfolio weights
        tickers: List of asset tickers
        filename: Output filename for the plot
    """
    # Calculate individual asset metrics
    individual_vols = np.sqrt(np.diag(S))
    individual_returns = mu
    
    # Create figure with large size
    plt.figure(figsize=(14, 8))
    
    # Plot individual assets
    plt.scatter(individual_vols, individual_returns, 
                marker='o', s=50, alpha=0.6,
                label='Individual Assets')
    
    # Add labels for each point
    for i, txt in enumerate(tickers):
        plt.annotate(txt, (individual_vols[i], individual_returns[i]),
                    xytext=(3, 3), textcoords='offset points',
                    fontsize=6)
    
    # Calculate and plot efficient frontier with more points for smoother line
    cla = CLA(mu, S)
    ef_returns, ef_vols, _ = cla.efficient_frontier(points=200)  # Increased from 100 to 200 points
    plt.plot(ef_vols, ef_returns, 'b-', linewidth=1.5, label='Efficient Frontier')
    
    # Plot optimal portfolio
    opt_vol = np.sqrt(np.dot(weights.T, np.dot(S, weights)))
    opt_ret = np.dot(weights, mu)
    plt.scatter(opt_vol, opt_ret, 
                marker='*', s=150, c='red',
                label='Optimal Portfolio')
    
    # Customize plot
    plt.title('Efficient Frontier with Individual Assets', fontsize=12)
    plt.xlabel('Volatility (Standard Deviation)', fontsize=10)
    plt.ylabel('Expected Return', fontsize=10)
    plt.grid(True, linestyle='--', alpha=0.7)
    plt.legend(fontsize=8)
    
    # Adjust layout and save
    plt.tight_layout()
    plt.savefig(filename, dpi=300, bbox_inches='tight')
    plt.close()

if not DISABLE_IO:
    plot_efficient_frontier_custom(mu, S, np.array(list(weights.values())), list(weights.keys()))
else:
    print("Skipping efficient frontier plot (no-I/O mode)")

#updates historical weights table
def insertweights():
    """
    Updates the historical weights table in the SQLite database.
    Handles special case for BRK-B ticker symbol.
    """
    if DISABLE_IO:
        print(f"Skipping weights database insert (no-I/O mode)")
        return

    import sqlite3

    conn = sqlite3.connect("portfolio.sqlite")
    cursor = conn.cursor()

    for key, value in weights.items():
        if key == "BRK-B":
            key = "BRK.B"

        cursor.execute(
            "INSERT INTO weights (timestamp, symbol, weight) VALUES (?, ?, ?)",
            (today, key, value),
        )

    conn.commit()
    conn.close()
    print(f"Weights inserted with timestamp {today}")

insertweights()

def plot_covariance_matrix(S, tickers, filename="covariance.png"):
    """
    Creates a heatmap visualization of the covariance matrix.
    
    Args:
        S: Covariance matrix
        tickers: List of asset tickers
        filename: Output filename for the plot
    """
    # Create figure with large size
    plt.figure(figsize=(14, 12))
    
    # Create heatmap
    plt.imshow(S, cmap='coolwarm', aspect='auto')
    
    # Add colorbar
    cbar = plt.colorbar()
    cbar.set_label('Covariance', fontsize=10)
    
    # Add tickers as labels
    plt.xticks(range(len(tickers)), tickers, rotation=90, fontsize=6)
    plt.yticks(range(len(tickers)), tickers, fontsize=6)
    
    # Add grid
    plt.grid(False)
    
    # Add title with timestamp
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    plt.title(f'Asset Covariance Matrix - {timestamp}', fontsize=12, pad=20)
    
    # Adjust layout and save
    plt.tight_layout()
    plt.savefig(filename, dpi=300, bbox_inches='tight')
    plt.close()

def generate_covariance_report(S, tickers, filename="cvreport.txt"):
    """
    Generates a report of covariance pairs ranked by value, separated into intra-sector
    and inter-sector correlations. Mutual funds are excluded from the analysis.
    
    Args:
        S: Covariance matrix (pandas DataFrame)
        tickers: List of asset tickers
        filename: Output filename for the report
    """
    # Convert covariance to correlation matrix
    std_devs = np.sqrt(np.diag(S))
    correlation_matrix = S / np.outer(std_devs, std_devs)
    
    # Create separate lists for intra and inter sector pairs
    intra_sector_pairs = []
    inter_sector_pairs = []
    
    # Hardcoded list of mutual funds to exclude
    mutual_funds = {'FDGFX', 'FNBGX', 'FAGIX'}
    
    # Sector abbreviations mapping
    sector_abbr = {
        "Communication Services": "Comm",
        "Consumer Discretionary": "ConsD",
        "Consumer Staples": "ConsS",
        "Energy": "Energy",
        "Financials": "Fin",
        "Healthcare": "Health",
        "Industrials": "Ind",
        "Materials": "Mat",
        "Tech": "Tech",
        "Real Estate": "RE",
        "Utilities": "Util",
        "DBonds": "DB",
        "FBonds": "FB",
        "Commodities": "Comm",
        "Misc": "Misc",
        "Precious Metals": "PM",
        "Unknown": "Unk"
    }
    
    for i in range(len(tickers)):
        for j in range(i+1, len(tickers)):
            t1, t2 = tickers[i], tickers[j]
            
            # Skip if either asset is a mutual fund
            if t1 in mutual_funds or t2 in mutual_funds:
                continue
                
            corr = correlation_matrix.iloc[i, j]
            
            # Check if assets are in the same sector
            if sector_mapper.get(t1) == sector_mapper.get(t2):
                sector = sector_abbr.get(sector_mapper.get(t1, "Unknown"), "Unk")
                intra_sector_pairs.append((t1, t2, corr, sector))
            else:
                sector1 = sector_abbr.get(sector_mapper.get(t1, "Unknown"), "Unk")
                sector2 = sector_abbr.get(sector_mapper.get(t2, "Unknown"), "Unk")
                inter_sector_pairs.append((t1, t2, corr, sector1, sector2))
    
    # Sort both lists by absolute correlation value
    intra_sector_pairs.sort(key=lambda x: abs(x[2]), reverse=True)
    inter_sector_pairs.sort(key=lambda x: abs(x[2]), reverse=True)
    
    # Calculate statistics for both types
    def calculate_stats(pairs):
        correlations = [abs(p[2]) for p in pairs]
        return {
            'mean': np.mean(correlations),
            'median': np.median(correlations),
            'std': np.std(correlations),
            'max': max(correlations),
            'min': min(correlations),
            'threshold': np.percentile(correlations, 90) if correlations else 0
        }
    
    intra_stats = calculate_stats(intra_sector_pairs)
    inter_stats = calculate_stats(inter_sector_pairs)
    
    # Filter for top correlations (above 90th percentile)
    intra_significant = [p for p in intra_sector_pairs if abs(p[2]) >= intra_stats['threshold']]
    inter_significant = [p for p in inter_sector_pairs if abs(p[2]) >= inter_stats['threshold']]
    
    # Write to file
    with open(filename, 'w') as f:
        f.write(f"Covariance Report - {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
        f.write("=" * 80 + "\n")
        
        # Write statistics for both types
        f.write("Correlation Statistics (excluding mutual funds):\n")
        f.write("-" * 80 + "\n")
        f.write("Intra-Sector Statistics:\n")
        f.write(f"Mean absolute correlation:   {intra_stats['mean']:.4f}\n")
        f.write(f"Median absolute correlation: {intra_stats['median']:.4f}\n")
        f.write(f"Standard deviation:         {intra_stats['std']:.4f}\n")
        f.write(f"Maximum correlation:        {intra_stats['max']:.4f}\n")
        f.write(f"Minimum correlation:        {intra_stats['min']:.4f}\n")
        f.write(f"90th percentile threshold:  {intra_stats['threshold']:.4f}\n")
        f.write("\n")
        f.write("Inter-Sector Statistics:\n")
        f.write(f"Mean absolute correlation:   {inter_stats['mean']:.4f}\n")
        f.write(f"Median absolute correlation: {inter_stats['median']:.4f}\n")
        f.write(f"Standard deviation:         {inter_stats['std']:.4f}\n")
        f.write(f"Maximum correlation:        {inter_stats['max']:.4f}\n")
        f.write(f"Minimum correlation:        {inter_stats['min']:.4f}\n")
        f.write(f"90th percentile threshold:  {inter_stats['threshold']:.4f}\n")
        f.write("\n")
        
        # Write significant intra-sector correlations
        f.write("Significant Intra-Sector Correlations:\n")
        f.write("=" * 80 + "\n")
        f.write("Rank  Pair                    Sector    Correlation\n")
        f.write("-" * 80 + "\n")
        for i, (t1, t2, corr, sector) in enumerate(intra_significant, 1):
            f.write(f"{i:4d}  {t1:6s} - {t2:6s}    {sector:6s}    {corr:12.4f}\n")
        
        f.write("\n")
        
        # Write significant inter-sector correlations
        f.write("Significant Inter-Sector Correlations:\n")
        f.write("=" * 80 + "\n")
        f.write("Rank  Pair                    Sectors              Correlation\n")
        f.write("-" * 80 + "\n")
        for i, (t1, t2, corr, sector1, sector2) in enumerate(inter_significant, 1):
            f.write(f"{i:4d}  {t1:6s} - {t2:6s}    {sector1:6s} - {sector2:6s}    {corr:12.4f}\n")

# After calculating S, add:
if not DISABLE_IO:
    plot_covariance_matrix(S, list(weights.keys()))
    generate_covariance_report(S, list(weights.keys()))
else:
    print("Skipping covariance matrix plot and report (no-I/O mode)")


