#!/usr/bin/python3

# Import necessary libraries
import requests
import pprint
from time import sleep
import json
from prettytable import PrettyTable
from prettytable import SINGLE_BORDER, DOUBLE_BORDER
import prettytable
import sqlite3
import os
import math
import datetime
import csv
from colorama import Fore, Back, Style

# Color definitions for table styling
class TableColors:
    HEADER = Fore.CYAN + Style.BRIGHT
    BUY_TABLE = Fore.GREEN + Style.BRIGHT    # Brighter green to override terminal
    SELL_TABLE = Fore.RED + Style.BRIGHT     # Brighter red to override terminal
    BORDER = Fore.WHITE + Style.DIM          # Dim white for dark borders
    BORDER_ALT = Fore.MAGENTA                # Alternative border color
    RESET = Style.RESET_ALL
    
    # ANSI escape codes as backup (more forceful)
    BUY_ANSI = "\033[1;32m"      # Bright green
    SELL_ANSI = "\033[1;31m"     # Bright red  
    BORDER_ANSI = "\033[2;37m"   # Dim white
    RESET_ANSI = "\033[0m"       # Reset

# Import custom modules
from loading import load_csv_to_associative_array
from apicalls import getcash
from dbops import getlots, getprice


def get_positions(token):
    """
    Get current positions from Tastyworks API.

    Args:
        token (str): Authentication token

    Returns:
        list: List of positions with symbol and quantity
    """
    url = produrlbase + "/accounts/5WY68491/positions"

    headers = {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "Authorization": token,
    }

    try:
        response = requests.request("GET", url, headers=headers)
        data = json.loads(response.text)

        positions = []
        for position in data["data"]["items"]:
            if position["instrument-type"] == "Equity":
                positions.append(
                    {
                        "symbol": position["symbol"],
                        "quantity": float(position["quantity"]),
                    }
                )

        return positions
    except Exception as e:
        print(f"Error getting positions: {str(e)}")
        return []


# Load stability data from CSV file
filename = "../stab.csv"
associative_array = load_csv_to_associative_array(filename)

# API endpoints
testurlbase = "https://api.cert.tastyworks.com"  # Testing environment
produrlbase = "https://api.tastyworks.com"  # Production environment

# Set up authentication to Tastyworks API
url = produrlbase + "/sessions"
payload = json.dumps(
    {"login": "mike@roetto.org", "password": ";%:|S17<ZkPP", "remember-me": True}
)
headers = {
    "Accept": "application/json",
    "Content-Type": "application/json",
}

# Authenticate with Tastyworks
response = requests.request("POST", url, headers=headers, data=payload)
st = json.loads(response.text)
token = st["data"]["session-token"]

print("token is", token)


def execute_query(query, overweight_min_thresh, params=None):
    """
    Executes the given SQLite query and returns the results.

    Args:
        query (str): The SQL query to execute
        overweight_min_thresh (int): Minimum threshold for overweight positions
        params (tuple, optional): Parameters for the query

    Returns:
        list: Query results as a list of tuples
    """
    conn = sqlite3.connect("../portfolio.sqlite")
    cur = conn.cursor()

    if params:
        cur.execute(query, params)
    else:
        cur.execute(query)

    rows = cur.fetchall()
    conn.close()

    return rows


def insert_order(symbol, size, price, xtype):
    """
    Insert a new transaction record into the database.

    Args:
        symbol (str): Stock symbol
        size (float): Number of shares
        price (float): Share price
        xtype (str): Transaction type ('Buy' or 'Sell')

    Returns:
        str: Status indicator
    """
    today = datetime.date.today()
    formatted_date = today.strftime("%Y-%m-%d")
    conn = sqlite3.connect("../portfolio.sqlite")
    cur = conn.cursor()
    print("insert into db", symbol, size, price)
    cur.execute(
        "INSERT INTO transactions (acct,date_new,symbol, xtype, units, price) VALUES (?,?,?, ?, ?, ?)",
        ("TT", formatted_date, symbol, xtype, size, price),
    )
    conn.commit()
    return "xxx"


def orderstatus(orderid):
    """
    Check the status of an order by ID.

    Args:
        orderid (str): Order ID to check

    Returns:
        tuple: Fill status, size, and price
    """
    url = produrlbase + "/accounts/5WY68491/orders/" + str(orderid)
    headers = {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "Authorization": token,
    }

    try:
        response = requests.request("GET", url, headers=headers)
        data = json.loads(response.text)

        # Check for error response
        if "error" in data:
            print(f"Error checking order status: {data['error']}")
            return "Error", 0, 0

        # Get order data - it's directly under "data", not nested under "order"
        order_data = data.get("data", {})
        if not order_data:
            print("No order data found in response")
            return "Unknown", 0, 0

        # Get fill status and details
        fillstatus = order_data.get("status", "Unknown")
        fillsize = 0
        fillprice = 0

        # Check for fills
        if "legs" in order_data and len(order_data["legs"]) > 0:
            leg = order_data["legs"][0]
            if "fills" in leg and len(leg["fills"]) > 0:
                fill = leg["fills"][0]
                fillsize = float(fill.get("quantity", 0))
                fillprice = float(fill.get("fill-price", 0))

        return fillstatus, fillsize, fillprice

    except Exception as e:
        print(f"Error checking order status: {str(e)}")
        return "Error", 0, 0


def test_colors():
    """
    Test different color approaches to see what works in the terminal.
    """
    print("\n=== COLOR TEST ===")
    print("If colors don't show up, we'll switch to ANSI codes or symbols")
    
    # Test colorama colors
    print(f"{TableColors.BUY_TABLE}Colorama Green (Buy){TableColors.RESET}")
    print(f"{TableColors.SELL_TABLE}Colorama Red (Sell){TableColors.RESET}")
    print(f"{TableColors.BORDER}Colorama Border{TableColors.RESET}")
    
    # Test ANSI codes
    print(f"{TableColors.BUY_ANSI}ANSI Green (Buy){TableColors.RESET_ANSI}")
    print(f"{TableColors.SELL_ANSI}ANSI Red (Sell){TableColors.RESET_ANSI}")
    print(f"{TableColors.BORDER_ANSI}ANSI Border{TableColors.RESET_ANSI}")
    
    print("=== END COLOR TEST ===\n")


def get_effective_colors():
    """
    Return the most effective color codes for this terminal.
    Auto-detects the best approach based on environment.
    """
    import sys
    
    # Try to detect if we're in a color-capable terminal
    term = os.environ.get('TERM', '').lower()
    colorterm = os.environ.get('COLORTERM', '').lower()
    
    # Check for common terminals that support colors well
    supports_color = any([
        'color' in term,
        'xterm' in term,
        'screen' in term,
        'tmux' in term,
        colorterm in ['truecolor', '24bit']
    ])
    
    if supports_color:
        # Try ANSI codes first (more reliable than colorama)
        return {
            'buy': TableColors.BUY_ANSI,
            'sell': TableColors.SELL_ANSI,
            'border': TableColors.BORDER_ANSI, 
            'reset': TableColors.RESET_ANSI
        }
    else:
        # Fallback to symbols if colors aren't supported
        return {
            'buy': '[BUY] ',
            'sell': '[SELL] ',
            'border': '│',
            'reset': ''
        }
    
    # Manual override options (comment/uncomment as needed):
    
    # Option 1: Force colorama
    # return {
    #     'buy': TableColors.BUY_TABLE,
    #     'sell': TableColors.SELL_TABLE, 
    #     'border': TableColors.BORDER,
    #     'reset': TableColors.RESET
    # }
    
    # Option 2: Force ANSI codes  
    # return {
    #     'buy': TableColors.BUY_ANSI,
    #     'sell': TableColors.SELL_ANSI,
    #     'border': TableColors.BORDER_ANSI, 
    #     'reset': TableColors.RESET_ANSI
    # }
    
    # Option 3: Force symbols/text
    # return {
    #     'buy': '[BUY] ',
    #     'sell': '[SELL] ',
    #     'border': '│',
    #     'reset': ''
    # }



def create_styled_table(field_names):
    """
    Create a properly styled PrettyTable with borders and colors.
    
    Args:
        field_names (list): List of column names
        
    Returns:
        PrettyTable: Configured table with proper styling
    """
    table = PrettyTable()
    table.field_names = field_names
    table.set_style(DOUBLE_BORDER)
    
    # Ensure all borders are properly displayed
    table.border = True
    table.hrules = prettytable.ALL
    table.vrules = prettytable.ALL
    
    return table


def slow_print(text):
    """
    Prints a string slowly to the console, one character at a time.

    Args:
        text (str): Text to print
    """
    for char in text:
        print(char, end="", flush=True)
        sleep(0.001)
    print()


def refresh_tables():
    """
    Refreshes both buy and sell recommendation tables.
    
    Returns:
        tuple: Updated result_list, result_list2, buy_count, and available_cash
    """
    # Define threshold for overweight positions
    overweight_min_thresh = 100
    
    print("\nRegenerating position tables...")
    
    # Query for underweight positions (potential buys)
    query_buy = "select prices.symbol,overamt, \
        (coalesce((pe-average_pe),0)/9)+(RSI/100)+volat+((price-mean50)/price)+((price-mean200)/price)-(prices.divyield/2) - (div_growth_rate/3) - (fcf_ni_ratio/2) as zeta \
    from prices,MPT,sectors \
        where MPT.overamt < -7 and  prices.symbol = MPT.symbol \
        and prices.symbol = sectors.symbol \
            order by zeta"
    results_buy = execute_query(query_buy, overweight_min_thresh)
    result_list = [row for i, row in enumerate(results_buy)]
    buy_count = len(result_list)
    
    # Get positions for sell recommendations
    positions = get_positions(token)
    result_list2 = []
    
    if positions:
        # Create a list of symbols we own
        owned_symbols = [pos["symbol"] for pos in positions]
        
        # Query for overweight positions that we actually own
        query_sell = "select prices.symbol,overamt, \
            (coalesce((pe-average_pe),0)/9)+(RSI/100)+volat+((price-mean50)/price)+((price-mean200)/price)-(prices.divyield/2) - (div_growth_rate/3) - (fcf_ni_ratio/2) as zeta \
        from prices,MPT,sectors \
            where MPT.overamt > 5 \
            and prices.symbol = MPT.symbol \
            and prices.symbol = sectors.symbol \
            and prices.symbol in ({}) \
            order by zeta DESC".format(
            ",".join(["?"] * len(owned_symbols))
        )
        
        results = execute_query(query_sell, overweight_min_thresh, owned_symbols)
        result_list2 = [row for i, row in enumerate(results)]
    
    # Recreate tables with proper styling
    table = create_styled_table([
        "id",
        "symbol",
        "price",
        "diffamt",
        "addshares",
        "zeta",
        "mod.stab",
    ])

    # Populate the buy recommendations table
    for i, row in enumerate(result_list):
        symbol = row[0]
        diffamt = row[1]
        stab = round(associative_array[symbol] * 100, 2)
        cprice = round(getprice(symbol), 4)
        needed_shares = round((abs(diffamt) / cprice), 4)

        table.add_row([i, symbol, cprice, diffamt, needed_shares, int(row[2] * 1000), stab])
        
    # Set up table for overweight positions with P/L data
    table2 = create_styled_table(["id", "symbol", "diffamt", "stab", "val", "P/L($)", "P/L(%)", "Cur.Val"])

    # Get current positions for P/L calculations
    positions = get_positions(token)
    position_lookup = {pos["symbol"]: pos for pos in positions}
    
    # Populate the overweight positions table with continued ID numbering
    for i, rowb in enumerate(result_list2):
        symbol = rowb[0]
        val = getlots(symbol)
        try:
            stab = round(associative_array[symbol] * 100, 2)
        except:
            continue

        # Get P/L data from position lookup
        position_data = position_lookup.get(symbol, {})
        quantity = position_data.get("quantity", 0)
        
        # Calculate P/L if we have position data
        if quantity > 0:
            current_price = getprice(symbol)
            # Use estimated cost basis from val/quantity if available
            if val > 0:
                estimated_cost_basis = val / quantity
                unrealized_pnl = (current_price - estimated_cost_basis) * quantity
                unrealized_pnl_pct = ((current_price - estimated_cost_basis) / estimated_cost_basis) * 100 if estimated_cost_basis > 0 else 0
                current_value = current_price * quantity
            else:
                unrealized_pnl = 0
                unrealized_pnl_pct = 0
                current_value = current_price * quantity
        else:
            unrealized_pnl = 0
            unrealized_pnl_pct = 0
            current_value = 0
        
        # Format P/L display
        pnl_str = f"+${unrealized_pnl:,.0f}" if unrealized_pnl > 0 else f"${unrealized_pnl:,.0f}"
        pnl_pct_str = f"+{unrealized_pnl_pct:.1f}%" if unrealized_pnl_pct > 0 else f"{unrealized_pnl_pct:.1f}%"
        current_value_str = f"${current_value:,.0f}"

        # Continue ID numbering from buy table
        continued_id = i + buy_count
        table2.add_row([continued_id, symbol, rowb[1], stab, val, pnl_str, pnl_pct_str, current_value_str])

    # Get string representations of both tables
    table_string1 = str(table)
    table_string2 = str(table2)

    # Determine maximum column widths for alignment
    max_width1 = max(len(row) for row in table_string1.splitlines())
    max_width2 = max(len(row) for row in table_string2.splitlines())

    # Print headers side by side with colors
    buy_header = f"{colors['buy']}*******CURRENT MODEL BUY RECOMMENDATIONS*******{colors['reset']}"
    sell_header = f"{colors['sell']}*******CURRENT MODEL SELL RECOMMENDATIONS*******{colors['reset']}"
    # Calculate width without color codes for alignment
    buy_header_clean = "*******CURRENT MODEL BUY RECOMMENDATIONS*******"
    sell_header_clean = "*******CURRENT MODEL SELL RECOMMENDATIONS*******"
    header_line = buy_header.ljust(max_width1 + len(buy_header) - len(buy_header_clean)) + f" {colors['border']}|{colors['reset']} " + sell_header
    slow_print(header_line)

    # Print tables side by side with alignment and colors
    for line1, line2 in zip(table_string1.splitlines(), table_string2.splitlines()):
        separator = f" {colors['border']}|{colors['reset']} "
        # Color the entire table lines
        colored_line1 = f"{colors['buy']}{line1}{colors['reset']}"
        colored_line2 = f"{colors['sell']}{line2}{colors['reset']}"
        combined_line = colored_line1.ljust(max_width1 + len(colors['buy']) + len(colors['reset'])) + separator + colored_line2.ljust(max_width2 + len(colors['sell']) + len(colors['reset']))
        slow_print(combined_line)
    
    # Add visual separator after tables
    print()
    
    # Update available cash
    available_cash = float(getcash(token))
    print("cash available: $", available_cash)
    
    return result_list, result_list2, buy_count, available_cash


def getchoice():
    """
    Get user input for row selection.

    Returns:
        int or None: Selected row number or None if quitting
    """
    choice = input(
        "Enter the row number you want to act on (or 'q' to quit, 'r' to return): "
    )
    if choice.lower() == "q":
        return None  # Indicate exit
    elif choice.lower() == "r":
        return "return"  # Indicate return to menu
    else:
        return int(choice)


def format_order_details(payload):
    """
    Format order details in a human-readable way.

    Args:
        payload (str): JSON payload of the order

    Returns:
        str: Formatted order details
    """
    order = json.loads(payload)
    leg = order["legs"][0]

    # Build the order details string
    details = f"""
Order Details:
-------------
Type: {order['order-type']}
Time in Force: {order['time-in-force']}"""

    # Add value effect and value for notional orders
    if "value-effect" in order:
        details += f"\nEffect: {order['value-effect']}"
        details += f"\nValue: ${order['value']}"

    # Add quantity for quantity-based orders
    if "quantity" in leg:
        details += f"\nQuantity: {leg['quantity']} shares"

    details += f"""

Leg Details:
-----------
Instrument: {leg['instrument-type']}
Action: {leg['action']}
Symbol: {leg['symbol']}
"""

    return details


def log_order(orderid, symbol, action, quantity, price, value, status, data=None):
    """
    Log order details to a human-readable log file.

    Args:
        orderid (str): Order ID
        symbol (str): Stock symbol
        action (str): Buy/Sell action
        quantity (float): Number of shares
        price (float): Price per share
        value (float): Total value
        status (str): Order status
        data (dict, optional): Full order response data for P/L info
    """
    # Get P/L data if available
    pl_data = ""
    if data and "data" in data and "order" in data["data"]:
        order_data = data["data"]["order"]
        if "legs" in order_data and len(order_data["legs"]) > 0:
            leg = order_data["legs"][0]
            if "fills" in leg and len(leg["fills"]) > 0:
                fill = leg["fills"][0]
                if "realized-pnl" in fill:
                    pl_data = f" | P/L: ${fill['realized-pnl']:.2f}"

    log_entry = (
        f"{datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')} | "
        f"OrderID: {orderid} | "
        f"Symbol: {symbol} | "
        f"Action: {action} | "
        f"Qty: {quantity} | "
        f"Price: ${price:.2f} | "
        f"Value: ${value:.2f} | "
        f"Status: {status}"
        f"{pl_data}\n"
    )

    with open("order_log.txt", "a") as f:
        f.write(log_entry)


# Clear the terminal screen
os.system("clear")

# Test colors to see what works in this terminal
test_colors()

# Get effective colors for this session
colors = get_effective_colors()

# Define overweight threshold (original location, but now only used in initial query)
overweight_min_thresh = 100

# Query for underweight positions (potential buys)
query = "select prices.symbol,overamt, \
    (coalesce((pe-average_pe),0)/9)+(RSI/100)+volat+((price-mean50)/price)+((price-mean200)/price)-(prices.divyield/2) - (div_growth_rate/3) - (fcf_ni_ratio/2) as zeta \
from prices,MPT,sectors \
    where MPT.overamt < -7 and  prices.symbol = MPT.symbol \
    and prices.symbol = sectors.symbol \
        order by zeta"
results = execute_query(query, overweight_min_thresh)

result_list = [row for i, row in enumerate(results)]

# Store the count of buy recommendations to continue ID numbering
buy_count = len(result_list)

# Query for overweight positions (potential sells)
#print("\nDEBUG: Starting sell recommendations process...")
positions = get_positions(token)
#print(f"DEBUG: Retrieved {len(positions)} positions from API")

if positions:
    # Create a list of symbols we own
    owned_symbols = [pos["symbol"] for pos in positions]
    #print(f"DEBUG: Owned symbols: {owned_symbols}")

    # Query for overweight positions that we actually own
    query = "select prices.symbol,overamt, \
        (coalesce((pe-average_pe),0)/9)+(RSI/100)+volat+((price-mean50)/price)+((price-mean200)/price)-(prices.divyield/2) - (div_growth_rate/3) - (fcf_ni_ratio/2) as zeta \
    from prices,MPT,sectors \
        where MPT.overamt > 5 \
        and prices.symbol = MPT.symbol \
        and prices.symbol = sectors.symbol \
        and prices.symbol in ({}) \
        order by zeta DESC".format(
        ",".join(["?"] * len(owned_symbols))
    )

    #print(f"DEBUG: SQL Query: {query}")
    #print(f"DEBUG: Query parameters: {owned_symbols}")

    results = execute_query(query, overweight_min_thresh, owned_symbols)
    #print(f"DEBUG: Found {len(results)} overweight positions")
    result_list2 = [row for i, row in enumerate(results)]
else:
    #print("DEBUG: No positions found from API")
    result_list2 = []

# Set up table for buy recommendations
table = create_styled_table([
    "id",
    "symbol",
    "price",
    "diffamt",
    "addshares",
    "zeta",
    "mod.stab",
])

# Populate the buy recommendations table
for i, row in enumerate(result_list):
    symbol = row[0]
    diffamt = row[1]
    stab = round(associative_array[symbol] * 100, 2)
    cprice = round(getprice(symbol), 4)
    needed_shares = round((abs(diffamt) / cprice), 4)

    table.add_row([i, symbol, cprice, diffamt, needed_shares, int(row[2] * 1000), stab])

# Set up table for overweight positions
table2 = create_styled_table(["id", "symbol", "diffamt", "stab", "val"])

# Populate the overweight positions table with continued ID numbering
for i, rowb in enumerate(result_list2):
    symbol = rowb[0]
    val = getlots(symbol)
    try:
        stab = round(associative_array[symbol] * 100, 2)
    except:
        continue

    # Continue ID numbering from buy table
    continued_id = i + buy_count
    table2.add_row([continued_id, symbol, rowb[1], stab, val])

# Get string representations of both tables
table_string1 = str(table)
table_string2 = str(table2)

# Determine maximum column widths for alignment
max_width1 = max(len(row) for row in table_string1.splitlines())
max_width2 = max(len(row) for row in table_string2.splitlines())

# Print headers side by side with colors
buy_header = f"{colors['buy']}*******CURRENT MODEL BUY RECOMMENDATIONS*******{colors['reset']}"
sell_header = f"{colors['sell']}*******CURRENT MODEL SELL RECOMMENDATIONS*******{colors['reset']}"
# Calculate width without color codes for alignment
buy_header_clean = "*******CURRENT MODEL BUY RECOMMENDATIONS*******"
sell_header_clean = "*******CURRENT MODEL SELL RECOMMENDATIONS*******"
header_line = buy_header.ljust(max_width1 + len(buy_header) - len(buy_header_clean)) + f" {colors['border']}|{colors['reset']} " + sell_header
slow_print(header_line)

# Print tables side by side with alignment and colors
for line1, line2 in zip(table_string1.splitlines(), table_string2.splitlines()):
    separator = f" {colors['border']}|{colors['reset']} "
    # Color the entire table lines
    colored_line1 = f"{colors['buy']}{line1}{colors['reset']}"
    colored_line2 = f"{colors['sell']}{line2}{colors['reset']}"
    combined_line = colored_line1.ljust(max_width1 + len(colors['buy']) + len(colors['reset'])) + separator + colored_line2.ljust(max_width2 + len(colors['sell']) + len(colors['reset']))
    slow_print(combined_line)

# Add visual separator after tables
print()

# Get available cash balance
available_cash = float(getcash(token))
print("cash available: $", available_cash)

# Main interaction loop
while True:
    choice = getchoice()
    if choice is None:
        break  # Exit the loop if 'q' was entered
    elif choice == "return":
        # Clear screen for fresh display
        os.system("clear")
        
        # Refresh buy recommendations
        query = "select prices.symbol,overamt, \
            (coalesce((pe-average_pe),0)/9)+(RSI/100)+volat+((price-mean50)/price)+((price-mean200)/price)-(prices.divyield/2) - (div_growth_rate/3) - (fcf_ni_ratio/2) as zeta \
        from prices,MPT,sectors \
            where MPT.overamt < -7 and  prices.symbol = MPT.symbol \
            and prices.symbol = sectors.symbol \
                order by zeta"
        results = execute_query(query, overweight_min_thresh)
        result_list = [row for i, row in enumerate(results)]
        buy_count = len(result_list)
        
        # Refresh sell recommendations
        positions = get_positions(token)
        if positions:
            owned_symbols = [pos["symbol"] for pos in positions]
            query = "select prices.symbol,overamt, \
                (coalesce((pe-average_pe),0)/9)+(RSI/100)+volat+((price-mean50)/price)+((price-mean200)/price)-(prices.divyield/2) - (div_growth_rate/3) - (fcf_ni_ratio/2) as zeta \
            from prices,MPT,sectors \
                where MPT.overamt > 5 \
                and prices.symbol = MPT.symbol \
                and prices.symbol = sectors.symbol \
                and prices.symbol in ({}) \
                order by zeta DESC".format(
                ",".join(["?"] * len(owned_symbols))
            )
            results = execute_query(query, overweight_min_thresh, owned_symbols)
            result_list2 = [row for i, row in enumerate(results)]
        else:
            result_list2 = []
        
        # Reset tables
        table = create_styled_table([
            "id",
            "symbol",
            "price",
            "diffamt",
            "addshares",
            "zeta",
            "mod.stab",
        ])
        
        # Populate buy table
        for i, row in enumerate(result_list):
            symbol = row[0]
            diffamt = row[1]
            stab = round(associative_array[symbol] * 100, 2)
            cprice = round(getprice(symbol), 4)
            needed_shares = round((abs(diffamt) / cprice), 4)
            table.add_row([i, symbol, cprice, diffamt, needed_shares, int(row[2] * 1000), stab])
        
        # Reset sell table
        table2 = create_styled_table(["id", "symbol", "diffamt", "stab", "val"])
        
        # Populate sell table
        for i, rowb in enumerate(result_list2):
            symbol = rowb[0]
            val = getlots(symbol)
            try:
                stab = round(associative_array[symbol] * 100, 2)
            except:
                continue
            continued_id = i + buy_count
            table2.add_row([continued_id, symbol, rowb[1], stab, val])
        
        # Get string representations of both tables
        table_string1 = str(table)
        table_string2 = str(table2)
        
        # Determine maximum column widths for alignment
        max_width1 = max(len(row) for row in table_string1.splitlines())
        max_width2 = max(len(row) for row in table_string2.splitlines())
        
        # Print headers side by side with colors
        buy_header = f"{colors['buy']}*******CURRENT MODEL BUY RECOMMENDATIONS*******{colors['reset']}"
        sell_header = f"{colors['sell']}*******CURRENT MODEL SELL RECOMMENDATIONS*******{colors['reset']}"
        # Calculate width without color codes for alignment
        buy_header_clean = "*******CURRENT MODEL BUY RECOMMENDATIONS*******"
        sell_header_clean = "*******CURRENT MODEL SELL RECOMMENDATIONS*******"
        header_line = buy_header.ljust(max_width1 + len(buy_header) - len(buy_header_clean)) + f" {colors['border']}|{colors['reset']} " + sell_header
        slow_print(header_line)
        
        # Print tables side by side with alignment and colors
        for line1, line2 in zip(table_string1.splitlines(), table_string2.splitlines()):
            separator = f" {colors['border']}|{colors['reset']} "
            # Color the entire table lines
            colored_line1 = f"{colors['buy']}{line1}{colors['reset']}"
            colored_line2 = f"{colors['sell']}{line2}{colors['reset']}"
            combined_line = colored_line1.ljust(max_width1 + len(colors['buy']) + len(colors['reset'])) + separator + colored_line2.ljust(max_width2 + len(colors['sell']) + len(colors['reset']))
            slow_print(combined_line)
        
        # Add visual separator after tables
        print()
        
        # Update available cash
        available_cash = float(getcash(token))
        print("cash available: $", available_cash)
        
        continue  # Return to menu

    # Determine if the choice is from buy or sell table
    if choice < buy_count:
        # Selection is from buy table
        symbol = result_list[choice][0]
        current_price = getprice(symbol)

        # Get the target value from overamt, but cap it at available cash minus 5% buffer
        # Round to 2 decimal places for API requirements
        target_value = round(min(abs(result_list[choice][1]), available_cash * 0.95), 2)

        print(
            f"Construct BUY order for {symbol} - ${target_value:.2f} notional value @ ${current_price:.2f}"
        )
        print(
            f"Note: Using 95% of available cash (${available_cash:.2f}) to account for margin requirements"
        )

        # Set up order API endpoint
        url = produrlbase + "/accounts/5WY68491/orders"

        # Prepare order payload - Notional Market order
        payload = json.dumps(
            {
                "order-type": "Notional Market",
                "value-effect": "Debit",
                "time-in-force": "Day",
                "value": target_value,
                "legs": [
                    {
                        "instrument-type": "Equity",
                        "action": "Buy to Open",
                        "symbol": symbol,
                    }
                ],
            }
        )

        # Display formatted order details
        print(format_order_details(payload))
        action = input("Enter 'e' to execute, 'r' to return, 'q' to quit: ")
        if action.lower() == "r":
            # Clear screen for fresh display
            os.system("clear")
            
            # Refresh buy recommendations
            query = "select prices.symbol,overamt, \
                (coalesce((pe-average_pe),0)/9)+(RSI/100)+volat+((price-mean50)/price)+((price-mean200)/price)-(prices.divyield/2) - (div_growth_rate/3) - (fcf_ni_ratio/2) as zeta \
            from prices,MPT,sectors \
                where MPT.overamt < -7 and  prices.symbol = MPT.symbol \
                and prices.symbol = sectors.symbol \
                    order by zeta"
            results = execute_query(query, overweight_min_thresh)
            result_list = [row for i, row in enumerate(results)]
            buy_count = len(result_list)
            
            # Refresh sell recommendations
            positions = get_positions(token)
            if positions:
                owned_symbols = [pos["symbol"] for pos in positions]
                query = "select prices.symbol,overamt, \
                    (coalesce((pe-average_pe),0)/9)+(RSI/100)+volat+((price-mean50)/price)+((price-mean200)/price)-(prices.divyield/2) - (div_growth_rate/3) - (fcf_ni_ratio/2) as zeta \
                from prices,MPT,sectors \
                    where MPT.overamt > 5 \
                    and prices.symbol = MPT.symbol \
                    and prices.symbol = sectors.symbol \
                    and prices.symbol in ({}) \
                    order by zeta DESC".format(
                    ",".join(["?"] * len(owned_symbols))
                )
                results = execute_query(query, overweight_min_thresh, owned_symbols)
                result_list2 = [row for i, row in enumerate(results)]
            else:
                result_list2 = []
            
            # Reset tables
            table = create_styled_table([
                "id",
                "symbol",
                "price",
                "diffamt",
                "addshares",
                "zeta",
                "mod.stab",
            ])
            
            # Populate buy table
            for i, row in enumerate(result_list):
                symbol = row[0]
                diffamt = row[1]
                stab = round(associative_array[symbol] * 100, 2)
                cprice = round(getprice(symbol), 4)
                needed_shares = round((abs(diffamt) / cprice), 4)
                table.add_row([i, symbol, cprice, diffamt, needed_shares, int(row[2] * 1000), stab])
            
            # Reset sell table
            table2 = create_styled_table(["id", "symbol", "diffamt", "stab", "val"])
            
            # Populate sell table
            for i, rowb in enumerate(result_list2):
                symbol = rowb[0]
                val = getlots(symbol)
                try:
                    stab = round(associative_array[symbol] * 100, 2)
                except:
                    continue
                continued_id = i + buy_count
                table2.add_row([continued_id, symbol, rowb[1], stab, val])
            
            # Get string representations of both tables
            table_string1 = str(table)
            table_string2 = str(table2)
            
            # Determine maximum column widths for alignment
            max_width1 = max(len(row) for row in table_string1.splitlines())
            max_width2 = max(len(row) for row in table_string2.splitlines())
            
            # Print headers side by side with colors
            buy_header = f"{colors['buy']}*******CURRENT MODEL BUY RECOMMENDATIONS*******{colors['reset']}"
            sell_header = f"{colors['sell']}*******CURRENT MODEL SELL RECOMMENDATIONS*******{colors['reset']}"
            # Calculate width without color codes for alignment
            buy_header_clean = "*******CURRENT MODEL BUY RECOMMENDATIONS*******"
            sell_header_clean = "*******CURRENT MODEL SELL RECOMMENDATIONS*******"
            header_line = buy_header.ljust(max_width1 + len(buy_header) - len(buy_header_clean)) + f" {colors['border']}|{colors['reset']} " + sell_header
            slow_print(header_line)
            
            # Print tables side by side with alignment and colors
            for line1, line2 in zip(table_string1.splitlines(), table_string2.splitlines()):
                separator = f" {colors['border']}|{colors['reset']} "
                # Color the entire table lines
                colored_line1 = f"{colors['buy']}{line1}{colors['reset']}"
                colored_line2 = f"{colors['sell']}{line2}{colors['reset']}"
                combined_line = colored_line1.ljust(max_width1 + len(colors['buy']) + len(colors['reset'])) + separator + colored_line2.ljust(max_width2 + len(colors['sell']) + len(colors['reset']))
                slow_print(combined_line)
            
            # Add visual separator after tables
            print()
            
            # Update available cash
            available_cash = float(getcash(token))
            print("cash available: $", available_cash)
            
            continue
        elif action.lower() == "q":
            break
    else:
        # Selection is from sell table
        sell_index = choice - buy_count
        if sell_index >= len(result_list2):
            print("Invalid selection")
            continue

        symbol = result_list2[sell_index][0]
        current_price = getprice(symbol)

        # Find the position quantity from our positions list
        position_quantity = None
        for pos in positions:
            if pos["symbol"] == symbol:
                position_quantity = pos["quantity"]
                break

        if position_quantity is None:
            print(f"Error: Could not find position quantity for {symbol}")
            continue

        # Calculate notional value based on current price and position quantity
        notional_value = abs(math.ceil(current_price * position_quantity))
        print(
            f"construct SELL order for {symbol} - {position_quantity} shares @ ${current_price} = ${notional_value}"
        )

        # Set up order API endpoint
        url = produrlbase + "/accounts/5WY68491/orders"

        # Prepare order payload - Market order with exact quantity
        payload = json.dumps(
            {
                "order-type": "Market",
                "time-in-force": "Day",
                "legs": [
                    {
                        "instrument-type": "Equity",
                        "action": "Sell to Close",
                        "symbol": symbol,
                        "quantity": position_quantity,
                    }
                ],
            }
        )

        # Display formatted order details
        print(format_order_details(payload))
        action = input("Enter 'e' to execute, 'r' to return, 'q' to quit: ")
        if action.lower() == "r":
            # Clear screen for fresh display
            os.system("clear")
            
            # Refresh buy recommendations
            query = "select prices.symbol,overamt, \
                (coalesce((pe-average_pe),0)/9)+(RSI/100)+volat+((price-mean50)/price)+((price-mean200)/price)-(prices.divyield/2) - (div_growth_rate/3) - (fcf_ni_ratio/2) as zeta \
            from prices,MPT,sectors \
                where MPT.overamt < -7 and  prices.symbol = MPT.symbol \
                and prices.symbol = sectors.symbol \
                    order by zeta"
            results = execute_query(query, overweight_min_thresh)
            result_list = [row for i, row in enumerate(results)]
            buy_count = len(result_list)
            
            # Refresh sell recommendations
            positions = get_positions(token)
            if positions:
                owned_symbols = [pos["symbol"] for pos in positions]
                query = "select prices.symbol,overamt, \
                    (coalesce((pe-average_pe),0)/9)+(RSI/100)+volat+((price-mean50)/price)+((price-mean200)/price)-(prices.divyield/2) - (div_growth_rate/3) - (fcf_ni_ratio/2) as zeta \
                from prices,MPT,sectors \
                    where MPT.overamt > 5 \
                    and prices.symbol = MPT.symbol \
                    and prices.symbol = sectors.symbol \
                    and prices.symbol in ({}) \
                    order by zeta DESC".format(
                    ",".join(["?"] * len(owned_symbols))
                )
                results = execute_query(query, overweight_min_thresh, owned_symbols)
                result_list2 = [row for i, row in enumerate(results)]
            else:
                result_list2 = []
            
            # Reset tables
            table = create_styled_table([
                "id",
                "symbol",
                "price",
                "diffamt",
                "addshares",
                "zeta",
                "mod.stab",
            ])
            
            # Populate buy table
            for i, row in enumerate(result_list):
                symbol = row[0]
                diffamt = row[1]
                stab = round(associative_array[symbol] * 100, 2)
                cprice = round(getprice(symbol), 4)
                needed_shares = round((abs(diffamt) / cprice), 4)
                table.add_row([i, symbol, cprice, diffamt, needed_shares, int(row[2] * 1000), stab])
            
            # Reset sell table
            table2 = create_styled_table(["id", "symbol", "diffamt", "stab", "val"])
            
            # Populate sell table
            for i, rowb in enumerate(result_list2):
                symbol = rowb[0]
                val = getlots(symbol)
                try:
                    stab = round(associative_array[symbol] * 100, 2)
                except:
                    continue
                continued_id = i + buy_count
                table2.add_row([continued_id, symbol, rowb[1], stab, val])
            
            # Get string representations of both tables
            table_string1 = str(table)
            table_string2 = str(table2)
            
            # Determine maximum column widths for alignment
            max_width1 = max(len(row) for row in table_string1.splitlines())
            max_width2 = max(len(row) for row in table_string2.splitlines())
            
            # Print headers side by side with colors
            buy_header = f"{colors['buy']}*******CURRENT MODEL BUY RECOMMENDATIONS*******{colors['reset']}"
            sell_header = f"{colors['sell']}*******CURRENT MODEL SELL RECOMMENDATIONS*******{colors['reset']}"
            # Calculate width without color codes for alignment
            buy_header_clean = "*******CURRENT MODEL BUY RECOMMENDATIONS*******"
            sell_header_clean = "*******CURRENT MODEL SELL RECOMMENDATIONS*******"
            header_line = buy_header.ljust(max_width1 + len(buy_header) - len(buy_header_clean)) + f" {colors['border']}|{colors['reset']} " + sell_header
            slow_print(header_line)
            
            # Print tables side by side with alignment and colors
            for line1, line2 in zip(table_string1.splitlines(), table_string2.splitlines()):
                separator = f" {colors['border']}|{colors['reset']} "
                # Color the entire table lines
                colored_line1 = f"{colors['buy']}{line1}{colors['reset']}"
                colored_line2 = f"{colors['sell']}{line2}{colors['reset']}"
                combined_line = colored_line1.ljust(max_width1 + len(colors['buy']) + len(colors['reset'])) + separator + colored_line2.ljust(max_width2 + len(colors['sell']) + len(colors['reset']))
                slow_print(combined_line)
            
            # Add visual separator after tables
            print()
            
            # Update available cash
            available_cash = float(getcash(token))
            print("cash available: $", available_cash)
            
            continue
        elif action.lower() == "q":
            break

    # Execute order if 'e' was entered
    if action.lower() != 'e':
        continue

    # Add auth token to headers
    headers = {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "Authorization": token,
    }

    # Submit the order
    response = requests.request("POST", url, headers=headers, data=payload)
    data = json.loads(response.text)

    # Check for errors
    if "error" in data and data["error"]:
        print("\nOrder Error:")
        print("Full Error Response:")
        print(json.dumps(data, indent=2))
        if "message" in data:
            print(f"\nError Message: {data['message']}")
        if "code" in data:
            print(f"Error Code: {data['code']}")
        if "errors" in data:
            print("\nDetailed Errors:")
            for error in data["errors"]:
                print(f"- {error}")
        print("\nPlease check your order details and try again.")
        sleep(2)
        continue

    if "data" not in data or "order" not in data["data"]:
        print("\nInvalid response from API:")
        print("Full Response:")
        print(json.dumps(data, indent=2))
        print("\nPlease try again.")
        sleep(2)
        continue

    # Get order details
    status = data["data"]["order"]["status"]
    orderid = data["data"]["order"]["id"]
    symbol = data["data"]["order"]["underlying-symbol"]
    print("order status: ", orderid, status, symbol)
    
    # Wait for order to fill before checking status
    print("Waiting 10 seconds for order to fill...")
    sleep(10)

    # Check fill details
    fillstatus, fillsize, fillprice = orderstatus(orderid)
    
    # Record the transaction in database
    transaction_type = "Sell" if choice >= buy_count else "Buy"
    insert_order(symbol, fillsize, fillprice, transaction_type)

    # Save order details to file
    filename = str(orderid) + ".json"
    with open(filename, "w") as f:
        json.dump(data, f, indent=4)

    # Log order details to human-readable log file
    log_order(
        orderid,
        symbol,
        transaction_type,
        fillsize,
        fillprice,
        float(fillsize) * float(fillprice),
        fillstatus,
        data,
    )

    # Display order execution summary
    print("\n" + "="*60)
    print("ORDER EXECUTION SUMMARY")
    print("="*60)
    
    # Color coding based on status
    if fillstatus == "Filled":
        status_color = Fore.GREEN
    elif fillstatus == "Partially Filled":
        status_color = Fore.YELLOW
    else:
        status_color = Fore.RED
    
    print(f"Order ID:     {Fore.CYAN}{orderid}{Style.RESET_ALL}")
    print(f"Symbol:       {Fore.CYAN}{symbol}{Style.RESET_ALL}")
    print(f"Action:       {Fore.MAGENTA}{transaction_type.upper()}{Style.RESET_ALL}")
    print(f"Status:       {status_color}{fillstatus}{Style.RESET_ALL}")
    print(f"Quantity:     {Fore.WHITE}{fillsize:,.4f} shares{Style.RESET_ALL}")
    print(f"Fill Price:   {Fore.WHITE}${fillprice:,.2f}{Style.RESET_ALL}")
    print(f"Total Value:  {Fore.WHITE}${float(fillsize) * float(fillprice):,.2f}{Style.RESET_ALL}")
    
    print("\n" + Fore.GREEN + "✓ Transaction recorded in database" + Style.RESET_ALL)
    print(f"{Fore.GREEN}✓ Order details saved to {filename}{Style.RESET_ALL}")
    print(f"{Fore.GREEN}✓ Order logged to order_log.txt{Style.RESET_ALL}")
    
    print("\n" + "="*60)
    print("Press Enter to return to main menu...")
    input()
    print("="*60)
    
    # Refresh tables after order execution
    # Clear screen for fresh display
    os.system("clear")
    
    # Refresh buy recommendations
    query = "select prices.symbol,overamt, \
        (coalesce((pe-average_pe),0)/9)+(RSI/100)+volat+((price-mean50)/price)+((price-mean200)/price)-(prices.divyield/2) - (div_growth_rate/3) - (fcf_ni_ratio/2) as zeta \
    from prices,MPT,sectors \
        where MPT.overamt < -7 and  prices.symbol = MPT.symbol \
        and prices.symbol = sectors.symbol \
            order by zeta"
    results = execute_query(query, overweight_min_thresh)
    result_list = [row for i, row in enumerate(results)]
    buy_count = len(result_list)
    
    # Refresh sell recommendations
    positions = get_positions(token)
    if positions:
        owned_symbols = [pos["symbol"] for pos in positions]
        query = "select prices.symbol,overamt, \
            (coalesce((pe-average_pe),0)/9)+(RSI/100)+volat+((price-mean50)/price)+((price-mean200)/price)-(prices.divyield/2) - (div_growth_rate/3) - (fcf_ni_ratio/2) as zeta \
        from prices,MPT,sectors \
            where MPT.overamt > 5 \
            and prices.symbol = MPT.symbol \
            and prices.symbol = sectors.symbol \
            and prices.symbol in ({}) \
            order by zeta DESC".format(
            ",".join(["?"] * len(owned_symbols))
        )
        results = execute_query(query, overweight_min_thresh, owned_symbols)
        result_list2 = [row for i, row in enumerate(results)]
    else:
        result_list2 = []
    
    # Reset tables
    table = create_styled_table([
        "id",
        "symbol",
        "price",
        "diffamt",
        "addshares",
        "zeta",
        "mod.stab",
    ])
    
    # Populate buy table
    for i, row in enumerate(result_list):
        symbol = row[0]
        diffamt = row[1]
        stab = round(associative_array[symbol] * 100, 2)
        cprice = round(getprice(symbol), 4)
        needed_shares = round((abs(diffamt) / cprice), 4)
        table.add_row([i, symbol, cprice, diffamt, needed_shares, int(row[2] * 1000), stab])
    
    # Reset sell table
    table2 = create_styled_table(["id", "symbol", "diffamt", "stab", "val"])
    
    # Populate sell table
    for i, rowb in enumerate(result_list2):
        symbol = rowb[0]
        val = getlots(symbol)
        try:
            stab = round(associative_array[symbol] * 100, 2)
        except:
            continue
        continued_id = i + buy_count
        table2.add_row([continued_id, symbol, rowb[1], stab, val])
    
    # Get string representations of both tables
    table_string1 = str(table)
    table_string2 = str(table2)
    
    # Determine maximum column widths for alignment
    max_width1 = max(len(row) for row in table_string1.splitlines())
    max_width2 = max(len(row) for row in table_string2.splitlines())
    
    # Print headers side by side with colors
    buy_header = f"{colors['buy']}*******CURRENT MODEL BUY RECOMMENDATIONS*******{colors['reset']}"
    sell_header = f"{colors['sell']}*******CURRENT MODEL SELL RECOMMENDATIONS*******{colors['reset']}"
    # Calculate width without color codes for alignment
    buy_header_clean = "*******CURRENT MODEL BUY RECOMMENDATIONS*******"
    sell_header_clean = "*******CURRENT MODEL SELL RECOMMENDATIONS*******"
    header_line = buy_header.ljust(max_width1 + len(buy_header) - len(buy_header_clean)) + f" {colors['border']}|{colors['reset']} " + sell_header
    slow_print(header_line)
    
    # Print tables side by side with alignment and colors
    for line1, line2 in zip(table_string1.splitlines(), table_string2.splitlines()):
        separator = f" {colors['border']}|{colors['reset']} "
        # Color the entire table lines
        colored_line1 = f"{colors['buy']}{line1}{colors['reset']}"
        colored_line2 = f"{colors['sell']}{line2}{colors['reset']}"
        combined_line = colored_line1.ljust(max_width1 + len(colors['buy']) + len(colors['reset'])) + separator + colored_line2.ljust(max_width2 + len(colors['sell']) + len(colors['reset']))
        slow_print(combined_line)
    
    # Add visual separator after tables
    print()
    
    # Update available cash
    available_cash = float(getcash(token))
    print("cash available: $", available_cash)
