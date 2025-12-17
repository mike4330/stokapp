#!/usr/bin/python3
# S&P 500 sector P/E ratio scraper from worldperatio.com
# Scrapes sector P/E ratios and updates portfolio database with current market valuations

import pandas as pd
import requests
from bs4 import BeautifulSoup
import sqlite3

# Sector name mapping dictionary to match scraped names with database names
SECTOR_NAME_MAPPING = {
    'Health Care': 'Healthcare',
    'Information Technology': 'Tech',
    # Add more mappings as needed when sector names don't match
    # 'Scraped Name': 'Database Name',
}

def extract_sp500_sectors_pe(url):
    """Extract S&P 500 sector P/E ratios from worldperatio.com"""
    
    # Fetch webpage content with browser headers to avoid blocking
    try:
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        }
        response = requests.get(url, headers=headers)
        response.raise_for_status()
        html_content = response.text
        
        # Debug output: show first part of response to verify page load
        print("\nFirst 500 characters of response:")
        print(html_content[:500])
        
    except requests.RequestException as e:
        print(f"Error fetching URL: {e}")
        return None

    # Parse HTML content using BeautifulSoup
    soup = BeautifulSoup(html_content, 'html.parser')
    
    # Debug: Count and identify tables on the page
    all_tables = soup.find_all('table')
    print(f"\nNumber of tables found on page: {len(all_tables)}")
    
    # Debug: Show table classes to help identify the correct table
    print("\nClasses of tables found:")
    for idx, table in enumerate(all_tables):
        print(f"Table {idx + 1} classes: {table.get('class', 'No class')}")
    
    # Attempt to find the sectors P/E table using expected class name
    table = soup.find('table', class_='money home-all sortable w3-table -f14 pd44')
    
    # Fallback table selection if main class doesn't match
    if table is None:
        print("\nCould not find the sectors table. Trying alternative selectors...")
        # Try partial class matches as website may change styling
        table = soup.find('table', class_='money')
        if table is None:
            table = soup.find('table', class_='sortable')
            if table is None:
                print("Still couldn't find the table with alternative selectors")
                return None
    
    # Initialize lists to store extracted data
    sectors = []
    pe_ratios = []
    
    try:
        # Find table body containing the data rows
        tbody = table.find('tbody')
        if tbody is None:
            print("Found table but couldn't find tbody")
            return None
            
        rows = tbody.find_all('tr')
        print(f"\nNumber of rows found in target table: {len(rows)}")
        
        # Process each data row in the table
        for row in rows:
            cols = row.find_all('td')
            # Verify row has expected number of columns (sector, P/E, etc.)
            if len(cols) >= 5:
                # Extract sector name (column 2) and P/E ratio (column 3)
                sector_name = cols[2].text.strip()
                pe_text = cols[3].text.strip()
                print(f"\nProcessing row: Sector={sector_name}, P/E={pe_text}")
                
                try:
                    # Convert P/E text to float for database storage
                    pe_ratio = float(pe_text)
                    sectors.append(sector_name)
                    pe_ratios.append(pe_ratio)
                except ValueError as e:
                    print(f"Error converting P/E ratio for {sector_name}: {e}")
            else:
                print(f"Row has insufficient columns: {len(cols)}")
        
    except Exception as e:
        print(f"Error processing table data: {e}")
        return None
    
    # Validate that data was successfully extracted
    if not sectors:
        print("No data was extracted from the table")
        return None
        
    # Create DataFrame with extracted sector P/E data
    df = pd.DataFrame({
        'Sector': sectors,
        'P/E Ratio': pe_ratios
    })
    
    # Sort sectors by P/E ratio (highest first) for analysis
    df = df.sort_values('P/E Ratio', ascending=False)
    
    return df

def map_sector_names(df):
    """
    Maps scraped sector names to database sector names using SECTOR_NAME_MAPPING
    This handles cases where website sector names differ from database names
    """
    # Create copy to avoid modifying original DataFrame
    mapped_df = df.copy()
    
    # Apply sector name mapping using dictionary lookup
    # If no mapping exists, keep original name
    mapped_df['Sector'] = mapped_df['Sector'].apply(
        lambda x: SECTOR_NAME_MAPPING.get(x, x)
    )
    
    # Show any sectors that were renamed for verification
    print("\nSector names after mapping:")
    for original, mapped in zip(df['Sector'], mapped_df['Sector']):
        if original != mapped:
            print(f"  {original} -> {mapped}")
    
    return mapped_df

def get_database_sectors(conn):
    """
    Retrieve all sector names from database to validate mapping accuracy
    """
    cursor = conn.cursor()
    cursor.execute("SELECT sector FROM sectors")
    db_sectors = [row[0] for row in cursor.fetchall()]
    return db_sectors

# Main execution starts here
# URL of the webpage to scrape
url = "https://worldperatio.com/sp-500-sectors/"

# Extract P/E data from website
df = extract_sp500_sectors_pe(url)
if df is not None:
    print("\nOriginal S&P 500 Sectors P/E Ratios:")
    print(df.to_string(index=False))
    
    # Apply sector name mapping to match database schema
    mapped_df = map_sector_names(df)
    
    print("\nMapped S&P 500 Sectors P/E Ratios:")
    print(mapped_df.to_string(index=False))

    # Connect to portfolio database
    conn = sqlite3.connect('../portfolio.sqlite')
    
    # Get existing sectors from database for validation
    db_sectors = get_database_sectors(conn)
    print("\nSectors in database:", db_sectors)
    
    # Validate that all scraped sectors have matching database entries
    unmatched_sectors = [sector for sector in mapped_df['Sector'] if sector not in db_sectors]
    if unmatched_sectors:
        print("\nWARNING: The following sectors still don't match any in the database:")
        for sector in unmatched_sectors:
            print(f"  - {sector}")
        print("Consider updating SECTOR_NAME_MAPPING dictionary")
    
    # Prepare data for database update (P/E ratio, sector name)
    sector_data = [(row['P/E Ratio'], row['Sector']) for index, row in mapped_df.iterrows()]
    
    cursor = conn.cursor()
    # Update sectors table with current P/E ratios
    updated_count = 0
    for pe, sector in sector_data:
        cursor.execute('UPDATE sectors SET average_pe = ? WHERE sector = ?', (pe, sector))
        updated_count += cursor.rowcount
    
    # Commit changes to database
    conn.commit()
    print(f"\nUpdated {updated_count} sector records in database")
    
    # Alert if some sectors weren't found in database
    if updated_count < len(sector_data):
        print(f"WARNING: {len(sector_data) - updated_count} sectors were not updated!")
        print("This may indicate missing sectors in database or mapping issues")
    
    conn.close()

    print("\nFinal sector data processed:")
    print(sector_data)
