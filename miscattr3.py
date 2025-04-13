#!/usr/bin/python3

"""
Stock Metadata Scraper
----------------------
This script retrieves financial data for stocks listed in a text file
using the yfinance API and updates a SQLite database with the obtained metrics.
The script includes error handling to ensure the loop continues even when
encountering problems with specific stocks or data fields.

Features:
- Cached API requests to reduce network calls
- Error handling for missing data fields
- Random delays between requests to avoid rate limiting
- Calculation of financial metrics like FCF/NI ratio
"""

import yfinance as yf
import random
import sqlite3
from time import sleep
from random import randint
import requests_cache
import pandas as pd
import logging

# Set up logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Constants
DATABASE_PATH = "/var/www/html/portfolio/portfolio.sqlite"
TICKERS_FILE = "tickers.txt"
CACHE_NAME = "yfinance.cache"
CACHE_EXPIRE = 45  # seconds
EXCLUDED_TICKERS = [
    'ANGL', 'BNDX', 'DBB', 'EMB', 'EWJ', 'FAGIX', 'FDGFX', 'FNBGX', 'FPE', 
    'LKOR', 'JPIB', 'MLN', 'PDBC', 'PGHY', 'REM', 'SGOL', 'SIVR', 'SJNK', 
    'TDTF', 'VCSH'
]


def get_market_cap_label(market_cap):
    """Determine market cap category label based on value in dollars."""
    if market_cap < 3e8:
        return "Micro"
    elif market_cap < 2e9:
        return "Small"
    elif market_cap < 10e9:
        return "Medium"
    elif market_cap < 2e11:
        return "Large"
    else:
        return "Mega"


def safe_get(dictionary, key, default=None):
    """Safely retrieve a value from a dictionary without raising KeyError."""
    try:
        return dictionary[key]
    except (KeyError, TypeError):
        return default


def calculate_fcf_ni_ratio(ticker_obj):
    """Calculate the ratio of Free Cash Flow to Net Income."""
    try:
        cashflow = ticker_obj.quarterly_cashflow
        fcf_row = cashflow.loc['Free Cash Flow']
        mean_fcf = fcf_row.mean()
        ni_row = cashflow.loc['Net Income From Continuing Operations']
        mean_ni = ni_row.mean()
        
        if mean_ni and mean_ni != 0:  # Avoid division by zero
            return round((mean_fcf / mean_ni), 4)
        return 0
    except Exception as e:
        logger.warning(f"Error calculating FCF/NI ratio: {e}")
        return 0


def random_delay():
    """Generate a random delay between API calls to avoid rate limiting."""
    ms_delay = randint(1, 2500)
    sec_delay = ms_delay / 1000
    logger.info(f"Sleeping for {ms_delay} ms ({sec_delay:.3f} s)")
    sleep(sec_delay)
    return sec_delay


def main():
    """Main function to run the stock metadata scraper."""
    logger.info("Starting stock metadata scraper")
    
    # Load ticker symbols from file
    try:
        with open(TICKERS_FILE, "r") as f:
            stocks = [line.strip() for line in f if not line.startswith("#")]
        
        # Randomize the order of processing
        random.shuffle(stocks)
        logger.info(f"Loaded {len(stocks)} stocks from {TICKERS_FILE}")
    except FileNotFoundError:
        logger.error(f"Tickers file not found: {TICKERS_FILE}")
        return
    
    # Set up cached session for API requests
    session = requests_cache.CachedSession(CACHE_NAME, expire_after=CACHE_EXPIRE)
    
    # Connect to SQLite database
    try:
        con = sqlite3.connect(DATABASE_PATH)
        cur = con.cursor()
        logger.info(f"Connected to database: {DATABASE_PATH}")
    except sqlite3.Error as e:
        logger.error(f"Database error: {e}")
        return
    
    processed_count = 0
    error_count = 0
    
    # Process each stock
    for stock in stocks:
        # Skip excluded tickers
        if stock in EXCLUDED_TICKERS:
            logger.info(f"Skipping excluded ticker: {stock}")
            continue
        
        logger.info(f"Processing {stock}")
        
        try:
            # Get ticker object
            ticker_obj = yf.Ticker(stock, session=session)
            info = ticker_obj.info
            
            # Calculate FCF/NI ratio
            fcf_ni_ratio = calculate_fcf_ni_ratio(ticker_obj)
            
            # Extract and prepare data, handling missing keys
            industry = safe_get(info, "industry", "")
            beta = safe_get(info, "beta", 0)
            recm = safe_get(info, "recommendationKey", "none")
            pe = safe_get(info, "trailingPE", 0)
            
            # Check for earnings growth data - if missing, log and continue
            if "earningsQuarterlyGrowth" not in info:
                logger.warning(f"{stock}: Missing earnings growth data, continuing")
            
            # Market cap calculation and labeling
            market_cap = safe_get(info, "marketCap", 0)
            market_cap_billions = round((market_cap / 1e9), 2) if market_cap else 0
            cap_label = get_market_cap_label(market_cap)
            
            # Log the extracted data
            logger.info(
                f"{stock} | Industry: {industry} | Beta: {beta} | Recommendation: {recm} | "
                f"Size: {cap_label} | P/E: {pe} | FCF/NI: {fcf_ni_ratio}"
            )
            
            # Update database
            cur.execute(
                "UPDATE MPT SET pe = ?, market_cap_val = ?, market_cap = ?, "
                "recm = ?, industry = ?, fcf_ni_ratio = ? WHERE symbol = ?",
                (pe, market_cap, cap_label, recm, industry, fcf_ni_ratio, stock)
            )
            con.commit()
            processed_count += 1
            
        except Exception as e:
            # Catch any unexpected errors to prevent loop from breaking
            error_count += 1
            logger.error(f"Error processing {stock}: {e}")
            # Continue with the next stock
            continue
        finally:
            # Add a random delay between requests regardless of success/failure
            random_delay()
    
    # Close the database connection
    con.close()
    logger.info(f"Completed processing {processed_count} stocks with {error_count} errors")


if __name__ == "__main__":
    main()
