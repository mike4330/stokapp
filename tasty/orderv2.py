#!/usr/bin/python3

"""
Modern trading interface using Rich library
Migrated from order.py with improved visuals and maintainable code
"""

# Import necessary libraries
import requests
import json
import sqlite3
import os
import datetime
import argparse
import time
from time import sleep

# Rich imports for modern UI
from rich.console import Console
from rich.table import Table
from rich.panel import Panel
from rich.text import Text
from rich.align import Align
from rich import box
from rich.live import Live
from rich.layout import Layout

# Import custom modules (same as original)
from loading import load_csv_to_associative_array
from apicalls import getcash
from dbops import getlots, getprice

# Configuration
DATABASE_PATH = "../portfolio.sqlite"
STABILITY_CSV_PATH = "../stab.csv"

class TastyConfig:
    """Configuration class to manage API endpoints and environment settings"""
    
    def __init__(self, use_test_env=False, debug=False):
        self.test_url = "https://api.cert.tastyworks.com"
        self.prod_url = "https://api.tastyworks.com"
        self.use_test_env = use_test_env
        self.debug = debug
        
    @property
    def base_url(self):
        """Return the appropriate base URL based on environment"""
        return self.test_url if self.use_test_env else self.prod_url
        
    @property
    def environment_name(self):
        """Return human-readable environment name"""
        return "TEST" if self.use_test_env else "PRODUCTION"

# API endpoints (kept for backward compatibility)
testurlbase = "https://api.cert.tastyworks.com"  # Testing environment
produrlbase = "https://api.tastyworks.com"  # Production environment

def authenticate_tastyworks(config):
    """Authenticate with Tastyworks API and return token"""
    url = config.base_url + "/sessions"
    
    # Use different credentials based on environment
    if config.use_test_env:
        # Sandbox credentials
        login = "mike4330"
        password = "XG>!w6jAQWK)zR7"
    else:
        # Production credentials
        login = "mike@roetto.org"
        password = ";%:|S17<ZkPP"
    
    payload = json.dumps(
        {"login": login, "password": password, "remember-me": True}
    )
    headers = {
        "Accept": "application/json",
        "Content-Type": "application/json",
    }
    
    try:
        response = requests.request("POST", url, headers=headers, data=payload)
        st = json.loads(response.text)
        token = st["data"]["session-token"]
        return token
    except Exception as e:
        print(f"Authentication failed: {e}")
        return None

def execute_query(query, params=None):
    """Execute SQLite query and return results"""
    conn = sqlite3.connect(DATABASE_PATH)
    cur = conn.cursor()
    
    if params:
        cur.execute(query, params)
    else:
        cur.execute(query)
    
    rows = cur.fetchall()
    conn.close()
    return rows

def get_account_id(token, config):
    """Get the first available account ID"""
    url = config.base_url + "/customers/me/accounts"
    
    headers = {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "Authorization": token,
    }
    
    try:
        response = requests.request("GET", url, headers=headers)
        data = json.loads(response.text)
        
        if "data" in data and "items" in data["data"] and len(data["data"]["items"]) > 0:
            account_id = data["data"]["items"][0]["account"]["account-number"]
            return account_id
        else:
            print(f"No accounts found: {data}")
            return None
    except Exception as e:
        print(f"Error getting accounts: {str(e)}")
        return None

def get_positions(token, config):
    """Get current positions from Tastyworks API"""
    # Get the appropriate account ID
    if config.use_test_env:
        account_id = get_account_id(token, config)
        if not account_id:
            print("Could not determine account ID for test environment")
            return []
    else:
        account_id = "5WY68491"  # Production account ID
    
    url = config.base_url + f"/accounts/{account_id}/positions"
    
    headers = {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "Authorization": token,
    }
    
    try:
        response = requests.request("GET", url, headers=headers)
        data = json.loads(response.text)
        
        if "error" in data:
            print(f"API Error: {data['error']['message']}")
            return []
        
        positions = []
        for position in data["data"]["items"]:
            if position["instrument-type"] == "Equity":
                # Get P/L related data from position
                symbol = position["symbol"]
                quantity = float(position["quantity"])
                
                # Get price data for P/L calculations
                avg_open_price = float(position.get("average-open-price", 0))
                close_price = float(position.get("close-price", 0))
                realized_today = float(position.get("realized-today", 0))
                
                # Calculate P/L metrics
                if avg_open_price > 0 and close_price > 0:
                    unrealized_pnl = (close_price - avg_open_price) * quantity
                    unrealized_pnl_pct = ((close_price - avg_open_price) / avg_open_price) * 100
                    current_value = close_price * quantity
                    cost_basis = avg_open_price * quantity
                else:
                    unrealized_pnl = 0
                    unrealized_pnl_pct = 0
                    current_value = 0
                    cost_basis = 0
                
                positions.append(
                    {
                        "symbol": symbol,
                        "quantity": quantity,
                        "avg_open_price": avg_open_price,
                        "close_price": close_price,
                        "realized_today": realized_today,
                        "unrealized_pnl": unrealized_pnl,
                        "unrealized_pnl_pct": unrealized_pnl_pct,
                        "current_value": current_value,
                        "cost_basis": cost_basis,
                    }
                )
        
        return positions
    except Exception as e:
        print(f"Error getting positions: {str(e)}")
        return []

def get_buy_recommendations():
    """Get buy recommendations from API endpoint"""
    try:
        # Use the MPMv2 API endpoint for consistency
        response = requests.get('http://localhost:8000/api/model-recommendations')
        response.raise_for_status()
        data = response.json()

        # Convert API response to match expected format: [(symbol, overamt, z_score), ...]
        results = [(item['symbol'], item['overamt'], item['z_score']) for item in data]
        return results
    except Exception as e:
        print(f"Error fetching recommendations from API: {e}")
        print("Falling back to local SQL query...")

        # Fallback to local query if API is unavailable
        query = """
        select prices.symbol, overamt,
            CASE WHEN pe <= 0 THEN 0 ELSE (coalesce((pe-average_pe),0)/9) END +
            (RSI/100)+volat+((price-mean50)/price)+((price-mean200)/price)-(prices.divyield/2) - (div_growth_rate/3) - (fcf_ni_ratio/2) as zeta
        from prices, MPT, sectors
            where MPT.overamt < -7 and  prices.symbol = MPT.symbol
            and prices.symbol = sectors.symbol
                order by zeta
        """
        results = execute_query(query)
        return results

def get_sell_recommendations(token, config):
    """Get sell recommendations from database for positions we actually own"""
    # Get current positions
    positions = get_positions(token, config)
    
    if not positions:
        return [], []
    
    # Create list of symbols we own
    owned_symbols = [pos["symbol"] for pos in positions]
    
    # Query for overweight positions that we actually own
    query = """
    select prices.symbol, overamt,
        CASE WHEN pe <= 0 THEN 0 ELSE (coalesce((pe-average_pe),0)/9) END +
        (RSI/100)+volat+((price-mean50)/price)+((price-mean200)/price)-(prices.divyield/2) - (div_growth_rate/3) - (fcf_ni_ratio/2) as zeta
    from prices, MPT, sectors
        where MPT.overamt > 5
        and prices.symbol = MPT.symbol
        and prices.symbol = sectors.symbol
        and prices.symbol in ({})
        order by zeta DESC
    """.format(",".join(["?"] * len(owned_symbols)))
    
    results = execute_query(query, owned_symbols)
    return results, positions

def create_buy_recommendations_table(associative_array):
    """Create a Rich table with real buy recommendations data"""
    
    # Get real data
    buy_data = get_buy_recommendations()
    
    # Create the table with styling
    table = Table(
        title="🟢 CURRENT MODEL BUY RECOMMENDATIONS",
        show_header=True,
        header_style="bold green",
        box=box.DOUBLE_EDGE,
        title_style="bold green",
        border_style="bright_black"
    )
    
    # Add columns with specific styling (same as original order.py)
    table.add_column("ID", style="cyan on grey7", no_wrap=True, justify="center")
    table.add_column("Symbol", style="bold white on grey7", no_wrap=True)
    table.add_column("Price", style="yellow on grey7", justify="right")
    table.add_column("Diff Amt", style="white on grey7", justify="right")
    table.add_column("Add Shares", style="blue on grey7", justify="right")
    table.add_column("Zeta", style="magenta on grey7", justify="right")
    table.add_column("Mod.Stab", style="green on grey7", justify="right")
    
    # Populate with real data (limit to 6 rows)
    for i, row in enumerate(buy_data[:6]):  # Limit to 6 rows
        symbol = row[0]
        diffamt = row[1]
        zeta = row[2]
        
        try:
            # Get stability from CSV (same as original)
            stab = round(associative_array[symbol] * 100, 2) if symbol in associative_array else 0.0
            # Get current price
            cprice = round(getprice(symbol), 4)
            # Calculate needed shares
            needed_shares = round((abs(diffamt) / cprice), 4) if cprice > 0 else 0
            
            # Format values for display
            price_str = f"${cprice:.2f}"
            diffamt_str = f"${diffamt:,.0f}" if diffamt < 0 else f"${diffamt:,.0f}"
            shares_str = f"{needed_shares:.2f}"
            zeta_str = f"{int(zeta * 1000)}"
            stab_str = f"{stab:.1f}"
            
            table.add_row(
                str(i),
                symbol,
                price_str,
                diffamt_str,
                shares_str,
                zeta_str,
                stab_str
            )
            
        except Exception as e:
            # Skip problematic rows but log the error
            print(f"Error processing {symbol}: {e}")
            continue
    
    return table

def create_sell_recommendations_table(token, config, associative_array, buy_count):
    """Create a Rich table with real sell recommendations data"""
    
    # Get real data
    sell_data, positions = get_sell_recommendations(token, config)
    
    # Create the table with styling
    table = Table(
        title="🔴 CURRENT MODEL SELL RECOMMENDATIONS",
        show_header=True,
        header_style="bold red",
        box=box.DOUBLE_EDGE,
        title_style="bold red",
        border_style="bright_black"
    )
    
    # Add columns with specific styling including P/L data
    table.add_column("ID", style="cyan", no_wrap=True, justify="center")
    table.add_column("Symbol", style="bold white", no_wrap=True)
    table.add_column("Diff Amt", style="bright_red", justify="right")
    table.add_column("Mod.Stab", style="green", justify="right")
    table.add_column("Holdings", style="yellow", justify="right")
    table.add_column("P/L ($)", style="bold", justify="right")
    table.add_column("P/L (%)", style="bold", justify="right")
    table.add_column("Current Value", style="blue", justify="right")
    
    # Create position lookup for getting holdings value and P/L data
    position_lookup = {pos["symbol"]: pos for pos in positions}
    
    # Populate with real data (same logic as original)
    for i, row in enumerate(sell_data):
        symbol = row[0]
        diffamt = row[1]
        zeta = row[2]
        
        try:
            # Get stability from CSV (same as original)
            stab = round(associative_array[symbol] * 100, 2) if symbol in associative_array else 0.0
            # Get holdings value using getlots function
            val = getlots(symbol)
            
            # Get P/L data from position lookup
            position_data = position_lookup.get(symbol, {})
            unrealized_pnl = position_data.get("unrealized_pnl", 0)
            unrealized_pnl_pct = position_data.get("unrealized_pnl_pct", 0)
            current_value = position_data.get("current_value", 0)
            
            # Format values for display
            diffamt_str = f"${diffamt:,.0f}"
            stab_str = f"{stab:.1f}"
            val_str = f"${val:,.0f}" if val else "$0"
            
            # Format P/L with colors
            if unrealized_pnl > 0:
                pnl_str = f"[green]+${unrealized_pnl:,.0f}[/green]"
                pnl_pct_str = f"[green]+{unrealized_pnl_pct:.1f}%[/green]"
            elif unrealized_pnl < 0:
                pnl_str = f"[red]-${abs(unrealized_pnl):,.0f}[/red]"
                pnl_pct_str = f"[red]{unrealized_pnl_pct:.1f}%[/red]"
            else:
                pnl_str = "$0"
                pnl_pct_str = "0.0%"
            
            current_value_str = f"${current_value:,.0f}" if current_value else "$0"
            
            # Continue ID numbering from buy table (same as original)
            continued_id = i + buy_count
            
            table.add_row(
                str(continued_id),
                symbol,
                diffamt_str,
                stab_str,
                val_str,
                pnl_str,
                pnl_pct_str,
                current_value_str
            )
            
        except Exception as e:
            # Skip problematic rows but log the error
            print(f"Error processing {symbol}: {e}")
            continue
    
    return table


def get_user_input(console, prompt, valid_choices=None, allow_empty=False):
    """Get user input with better error handling and backspace support"""
    while True:
        try:
            choice = console.input(prompt).strip()
            
            if not choice and not allow_empty:
                console.print("[yellow]Please enter a choice.[/yellow]")
                continue
            
            if valid_choices and choice.lower() not in valid_choices:
                console.print(f"[red]Invalid choice. Please enter one of: {', '.join(valid_choices)}[/red]")
                continue
            
            return choice
            
        except KeyboardInterrupt:
            console.print("\n[yellow]Use Ctrl+C to interrupt or enter a valid choice.[/yellow]")
            continue
        except EOFError:
            console.print("\n[yellow]Input cancelled. Please try again.[/yellow]")
            continue

def getchoice(console):
    """Get user input for row selection with Rich styling and better input handling"""
    from rich.panel import Panel
    from rich.columns import Columns
    
    # Create a nice menu panel
    menu_content = [
        "[bold cyan]📋 TRADING MENU[/bold cyan]",
        "",
        "[green]• Enter row number[/green] - Execute individual trade",
        "[blue]• 'a'[/blue] - Autobalance portfolio",
        "[yellow]• 'r'[/yellow] - Refresh tables",
        "[red]• 'q'[/red] - Quit application",
        "",
        "[dim]Tip: Use backspace to edit your input[/dim]"
    ]
    
    console.print(Panel(
        "\n".join(menu_content),
        border_style="cyan",
        padding=(1, 2)
    ))
    
    while True:
        choice = get_user_input(console, "[bold yellow]❯ Choice: [/bold yellow]")
        
        if choice.lower() == "q":
            return None  # Indicate exit
        elif choice.lower() == "r":
            return "refresh"  # Indicate refresh
        elif choice.lower() == "a":
            return "autobalance"  # Indicate autobalance
        else:
            try:
                row_num = int(choice)
                if row_num < 0:
                    console.print("[red]❌ Please enter a non-negative number.[/red]")
                    continue
                return row_num
            except ValueError:
                console.print("[red]❌ Invalid input. Please enter a number, 'a', 'r', or 'q'.[/red]")
                continue


def format_order_details(payload, config):
    """Format order details in a human-readable way with Rich styling"""
    order = json.loads(payload)
    leg = order["legs"][0]
    
    # Create a Rich table for order details
    table = Table(
        title=f"📋 Order Preview ({'TEST' if config.use_test_env else 'PRODUCTION'})",
        show_header=True,
        header_style="bold cyan",
        box=box.ROUNDED,
        title_style="bold yellow" if config.use_test_env else "bold green"
    )
    
    table.add_column("Field", style="cyan", no_wrap=True)
    table.add_column("Value", style="white")
    
    # Add order details
    table.add_row("Order Type", order['order-type'])
    table.add_row("Time in Force", order['time-in-force'])
    
    # Add value effect and value for notional orders
    if "value-effect" in order:
        table.add_row("Effect", order['value-effect'])
        table.add_row("Value", f"${order['value']}")
    
    # Add quantity for quantity-based orders
    if "quantity" in leg:
        table.add_row("Quantity", f"{leg['quantity']} shares")
    
    table.add_row("Instrument", leg['instrument-type'])
    table.add_row("Action", leg['action'])
    table.add_row("Symbol", leg['symbol'])
    
    return table


def insert_order(symbol, size, price, xtype):
    """Insert a new transaction record into the database"""
    today = datetime.date.today()
    formatted_date = today.strftime("%Y-%m-%d")
    conn = sqlite3.connect(DATABASE_PATH)
    cur = conn.cursor()
    try:
        cur.execute(
            "INSERT INTO transactions (acct,date_new,symbol, xtype, units, price) VALUES (?,?,?, ?, ?, ?)",
            ("TT", formatted_date, symbol, xtype, size, price),
        )
        conn.commit()
        return "success"
    except Exception as e:
        print(f"Error inserting transaction: {e}")
        return "error"
    finally:
        conn.close()


def orderstatus(orderid, config, token):
    """Check the status of an order by ID"""
    # Get the appropriate account ID
    if config.use_test_env:
        account_id = get_account_id(token, config)
        if not account_id:
            return "Error", 0, 0
    else:
        account_id = "5WY68491"  # Production account
    
    url = config.base_url + f"/accounts/{account_id}/orders/{orderid}"
    
    headers = {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "Authorization": token,
    }
    
    try:
        response = requests.request("GET", url, headers=headers)
        data = json.loads(response.text)
        
        # Debug: Print the full response if debugging is enabled
        if config.debug:
            print(f"DEBUG: Order {orderid} API Response: {json.dumps(data, indent=2)}")
        
        # Check for error response
        if "error" in data:
            if config.debug:
                print(f"DEBUG: API Error for order {orderid}: {data['error']}")
            return "Error", 0, 0
        
        # Get order data
        order_data = data.get("data", {})
        if not order_data:
            if config.debug:
                print(f"DEBUG: No order data found for order {orderid}")
            return "Unknown", 0, 0
        
        # Get fill status and details
        fillstatus = order_data.get("status", "Unknown")
        fillsize = 0
        fillprice = 0
        
        # Check for fills in legs
        if "legs" in order_data and len(order_data["legs"]) > 0:
            leg = order_data["legs"][0]
            if config.debug:
                print(f"DEBUG: Leg data for order {orderid}: {json.dumps(leg, indent=2)}")
            
            if "fills" in leg and len(leg["fills"]) > 0:
                fill = leg["fills"][0]
                if config.debug:
                    print(f"DEBUG: Fill data for order {orderid}: {json.dumps(fill, indent=2)}")
                
                fillsize = float(fill.get("quantity", 0))
                fillprice = float(fill.get("fill-price", 0))
                
                # Try alternative field names if the standard ones don't work
                if fillprice == 0:
                    fillprice = float(fill.get("price", 0))
                if fillsize == 0:
                    fillsize = float(fill.get("size", 0))
        
        if config.debug:
            print(f"DEBUG: Final result for order {orderid}: Status={fillstatus}, Size={fillsize}, Price={fillprice}")
        
        return fillstatus, fillsize, fillprice
        
    except Exception as e:
        if config.debug:
            print(f"DEBUG: Exception in orderstatus for order {orderid}: {e}")
        return "Error", 0, 0


def log_order(orderid, symbol, action, quantity, price, value, status, data=None):
    """Log order details to a human-readable log file"""
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


def refresh_tables(token, config, associative_array, console):
    """Refresh and redisplay both buy and sell recommendation tables"""
    console.clear()
    
    # Show environment indicator
    env_indicator = f"[{'yellow' if config.use_test_env else 'green'}]({config.environment_name})[/]"
    console.print(Align.center(Text(f"🚀 MODERN TRADING INTERFACE {env_indicator}", style="bold blue")), "\n")
    
    # Show environment warning for test mode
    if config.use_test_env:
        console.print(Panel(
            "[bold yellow]⚠️  RUNNING IN TEST MODE ⚠️[/bold yellow]\n"
            "[yellow]You are connected to the Tastyworks sandbox environment.\n"
            "No real trades will be executed.[/yellow]",
            border_style="yellow",
            title="Test Environment"
        ))
        console.print()
    
    # Create and display buy recommendations table
    if config.debug:
        console.print("[yellow]Refreshing buy recommendations...[/yellow]")
    buy_table = create_buy_recommendations_table(associative_array)
    buy_data = get_buy_recommendations()
    buy_count = len(buy_data)
    
    console.print(buy_table)
    console.print()
    
    # Create and display sell recommendations table
    if config.debug:
        console.print("[yellow]Refreshing sell recommendations...[/yellow]")
    sell_table = create_sell_recommendations_table(token, config, associative_array, buy_count)
    console.print(sell_table)
    
    # Get and display updated cash balance
    if config.debug:
        console.print("\n[yellow]Fetching updated cash balance...[/yellow]")
    try:
        # Get the appropriate account ID
        if config.use_test_env:
            account_id = get_account_id(token, config)
            if not account_id:
                raise Exception("Could not determine account ID for test environment")
        else:
            account_id = "5WY68491"
        
        # Make direct API call to get cash balance
        url = config.base_url + f"/accounts/{account_id}/balances"
        headers = {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "Authorization": token,
        }
        response = requests.request("GET", url, headers=headers)
        data = json.loads(response.text)
        
        if "error" in data:
            raise Exception(f"API Error: {data['error']['message']}")
        
        available_cash = float(data["data"]["cash-balance"])
        console.print(Panel(
            f"[bold green]💰 Cash Available: ${available_cash:,.2f}[/bold green]",
            border_style="green",
            padding=(0, 1)
        ))
    except Exception as e:
        console.print(Panel(
            f"[bold red]❌ Could not fetch cash balance: {e}[/bold red]",
            border_style="red",
            padding=(0, 1)
        ))
        available_cash = 0
    
    return buy_data, available_cash


def parse_arguments():
    """Parse command line arguments"""
    parser = argparse.ArgumentParser(
        description="Modern Trading Interface for Tastyworks API with P/L tracking and autobalance",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python orderv2.py --test    # Use test/sandbox environment
  python orderv2.py --prod    # Use production environment (default)
  python orderv2.py --debug   # Enable debug output
  python orderv2.py           # Use production environment (default)

Interactive Commands:
  • Enter row number to execute a trade
  • 'a' to autobalance portfolio (execute buy orders until cash exhausted)
  • 'r' to refresh tables
  • 'q' to quit
        """
    )
    
    env_group = parser.add_mutually_exclusive_group()
    env_group.add_argument(
        "--test", 
        action="store_true",
        help="Use Tastyworks test/sandbox environment (api.cert.tastyworks.com)"
    )
    env_group.add_argument(
        "--prod", 
        action="store_true",
        help="Use Tastyworks production environment (api.tastyworks.com) [default]"
    )
    
    parser.add_argument(
        "--debug",
        action="store_true",
        help="Enable debug output (show fetching messages, etc.)"
    )
    
    return parser.parse_args()

def autobalance_portfolio(token, config, associative_array, console):
    """
    Autobalance portfolio by executing buy orders until available cash is exhausted.
    Orders are executed in priority order based on buy recommendations.
    """
    console.print("\n[bold magenta]🔄 AUTOBALANCE PORTFOLIO[/bold magenta]")
    
    # Get current cash balance
    try:
        if config.use_test_env:
            account_id = get_account_id(token, config)
            if not account_id:
                console.print("[red]❌ Could not determine account ID for test environment[/red]")
                return False
        else:
            account_id = "5WY68491"
        
        url = config.base_url + f"/accounts/{account_id}/balances"
        headers = {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "Authorization": token,
        }
        response = requests.request("GET", url, headers=headers)
        data = json.loads(response.text)
        
        if "error" in data:
            console.print(f"[red]❌ API Error: {data['error']['message']}[/red]")
            return False
        
        available_cash = float(data["data"]["cash-balance"])
        
    except Exception as e:
        console.print(f"[red]❌ Error getting cash balance: {e}[/red]")
        return False
    
    # Get buy recommendations
    buy_data = get_buy_recommendations()
    
    if not buy_data:
        console.print("[yellow]⚠️  No buy recommendations available[/yellow]")
        return False
    
    # Calculate proposed buys for the plan
    proposed_buys = []
    simulated_cash = available_cash
    minimum_cash_threshold = available_cash * 0.05
    
    for i, row in enumerate(buy_data):
        if simulated_cash < minimum_cash_threshold:
            break
        
        symbol = row[0]
        diffamt = row[1]
        
        try:
            current_price = getprice(symbol)
            target_value = min(abs(diffamt), simulated_cash * 0.95)
            
            if config.use_test_env:
                shares_to_buy = int(target_value / current_price)
                if shares_to_buy == 0:
                    continue
                actual_value = shares_to_buy * current_price
                proposed_buys.append({
                    'symbol': symbol,
                    'shares': shares_to_buy,
                    'price': current_price,
                    'value': actual_value,
                    'type': 'shares'
                })
            else:
                actual_value = target_value
                proposed_buys.append({
                    'symbol': symbol,
                    'shares': None,
                    'price': current_price,
                    'value': actual_value,
                    'type': 'notional'
                })
            
            simulated_cash -= actual_value
        except Exception as e:
            continue
    
    # Create plan details
    plan_details = []
    plan_details.append(f"[bold green]💰 Available Cash: ${available_cash:,.2f}[/bold green]")
    plan_details.append(f"[cyan]📊 Buy Recommendations: {len(buy_data)}[/cyan]")
    plan_details.append(f"[yellow]🎯 Proposed Orders: {len(proposed_buys)}[/yellow]")
    plan_details.append(f"[dim]💵 Minimum Cash Reserve: ${minimum_cash_threshold:.2f} (5%)[/dim]")
    plan_details.append("")
    
    if proposed_buys:
        plan_details.append("[bold cyan]Proposed Buy Orders:[/bold cyan]")
        total_proposed = 0
        for buy in proposed_buys[:5]:  # Show first 5 orders
            if buy['type'] == 'shares':
                plan_details.append(f"• {buy['symbol']}: {buy['shares']} shares @ ${buy['price']:.2f} = ${buy['value']:.2f} [dim](Market Order)[/dim]")
            else:
                plan_details.append(f"• {buy['symbol']}: ${buy['value']:.2f} worth @ ${buy['price']:.2f} [dim](Notional Market Order)[/dim]")
            total_proposed += buy['value']
        
        if len(proposed_buys) > 5:
            plan_details.append(f"• ... and {len(proposed_buys) - 5} more orders")
        
        plan_details.append(f"[dim]Total Proposed Spending: ${total_proposed:.2f}[/dim]")
    else:
        plan_details.append("[yellow]⚠️  No orders can be executed with current cash and prices[/yellow]")
    
    plan_details.append("")
    plan_details.append(f"[dim]{'[yellow]⚠️  TEST MODE - No real trades will be executed[/yellow]' if config.use_test_env else '[red]🚨 LIVE MODE - Real trades will be executed[/red]'}[/dim]")
    
    # Show autobalance plan
    console.print(Panel(
        "\n".join(plan_details),
        border_style="magenta",
        title="Autobalance Plan",
        padding=(1, 2)
    ))
    
    # Confirmation prompt
    confirm = get_user_input(console, f"\n[bold yellow]Proceed with autobalance? [y/N]: [/bold yellow]", ['y', 'n', 'yes', 'no', ''], allow_empty=True)
    if confirm.lower() not in ['y', 'yes']:
        console.print("[dim]Autobalance cancelled.[/dim]")
        return False
    
    # Execute orders using the exact same plan as calculated above
    console.print("\n[bold green]🚀 Starting autobalance execution...[/bold green]")
    
    orders_executed = 0
    total_spent = 0.0
    orders_attempted = 0
    orders_failed = 0
    
    # Track successful orders for later database recording
    successful_orders = []
    
    # Execute the exact same orders as planned - no dynamic recalculation
    for planned_buy in proposed_buys:
        symbol = planned_buy['symbol']
        target_value = planned_buy['value']
        current_price = planned_buy['price']
        orders_attempted += 1
        
        try:
            
            if config.use_test_env:
                # TEST ENVIRONMENT: Use the exact shares calculated in the plan
                shares_to_buy = planned_buy['shares']
                actual_value = planned_buy['value']
                
                # Prepare order payload - Regular Market order with quantity
                payload = json.dumps({
                    "order-type": "Market",
                    "time-in-force": "Day",
                    "legs": [{
                        "instrument-type": "Equity",
                        "action": "Buy to Open",
                        "symbol": symbol,
                        "quantity": shares_to_buy,
                    }],
                })
                
                console.print(f"[green]📈 {symbol}: {shares_to_buy} shares @ ${current_price:.2f} = ${actual_value:.2f}[/green]")
                
            else:
                # PRODUCTION ENVIRONMENT: Use the exact value calculated in the plan
                actual_value = target_value
                
                # Prepare order payload - Notional Market order with value
                payload = json.dumps({
                    "order-type": "Notional Market",
                    "value-effect": "Debit",
                    "time-in-force": "Day",
                    "value": target_value,
                    "legs": [{
                        "instrument-type": "Equity",
                        "action": "Buy to Open",
                        "symbol": symbol,
                    }],
                })
                
                console.print(f"[green]📈 {symbol}: ${target_value:.2f} worth @ ${current_price:.2f}[/green]")
            
            # Execute the order
            order_url = config.base_url + f"/accounts/{account_id}/orders"
            order_headers = {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "Authorization": token,
            }
            order_response = requests.request("POST", order_url, headers=order_headers, data=payload)
            order_data = json.loads(order_response.text)
            
            if "error" in order_data:
                console.print(f"[red]❌ Order failed for {symbol}: {order_data['error']['message']}[/red]")
                orders_failed += 1
                continue
            
            # Order successful
            orderid = order_data["data"]["order"]["id"]
            orders_executed += 1
            total_spent += actual_value
            
            console.print(f"[dim]✅ Order {orderid} submitted successfully[/dim]")
            
            # Store successful order for later database recording
            successful_orders.append({
                'orderid': orderid,
                'symbol': symbol,
                'shares': shares_to_buy if config.use_test_env else None,
                'target_value': target_value,
                'current_price': current_price,
                'actual_value': actual_value
            })
            
            # Log the order
            log_order(orderid, symbol, "Buy", 
                     shares_to_buy if config.use_test_env else f"${target_value:.2f}", 
                     current_price, actual_value, "submitted", order_data)
            
            # Brief pause between orders
            time.sleep(0.5)
            
        except Exception as e:
            console.print(f"[red]❌ Error processing {symbol}: {e}[/red]")
            orders_failed += 1
            continue
    
    # Wait for all orders to fill using retry model with 2 minute timeout
    if successful_orders:
        console.print(f"\n[yellow]⏳ Monitoring {len(successful_orders)} orders for fills (up to 2 minutes)...[/yellow]")
        
        transactions_recorded = 0
        pending_orders = successful_orders.copy()
        recorded_orders = set()
        
        # Retry model: Check every 5 seconds for up to 2 minutes
        max_attempts = 24  # 2 minutes / 5 seconds = 24 attempts
        attempt = 0
        
        while pending_orders and attempt < max_attempts:
            attempt += 1
            time.sleep(5)  # Wait 5 seconds between checks
            
            console.print(f"[dim]Checking fills... (attempt {attempt}/{max_attempts}, {len(pending_orders)} pending)[/dim]")
            
            # Check each pending order
            orders_to_remove = []
            for order_info in pending_orders:
                orderid = order_info['orderid']
                symbol = order_info['symbol']
                
                # Skip if already recorded
                if orderid in recorded_orders:
                    continue
                
                # Check fill details
                fillstatus, fillsize, fillprice = orderstatus(orderid, config, token)
                
                # Debug output to see what we're getting (only in debug mode)
                if config.debug:
                    console.print(f"[dim]DEBUG: {symbol} Order {orderid} - Status: {fillstatus}, Size: {fillsize}, Price: {fillprice}[/dim]")
                
                if fillstatus == "Filled" and fillsize > 0:
                    # Record the transaction in database (even if fillprice is 0)
                    # Use current price as fallback if fillprice is 0
                    actual_price = fillprice if fillprice > 0 else order_info['current_price']
                    insert_order(symbol, fillsize, actual_price, "Buy")
                    console.print(f"[green]✅ Transaction recorded: {symbol} {fillsize} shares @ ${actual_price:.2f}[/green]")
                    transactions_recorded += 1
                    recorded_orders.add(orderid)
                    orders_to_remove.append(order_info)
                elif fillstatus in ["Cancelled", "Rejected", "Expired"]:
                    # Order failed permanently
                    console.print(f"[red]❌ {symbol} Order {orderid}: {fillstatus} - no database record[/red]")
                    orders_to_remove.append(order_info)
                else:
                    # Still pending, continue monitoring
                    console.print(f"[dim]⏳ {symbol} Order {orderid}: {fillstatus}[/dim]")
            
            # Remove filled/failed orders from pending list
            for order_info in orders_to_remove:
                pending_orders.remove(order_info)
            
            # If all orders processed, break early
            if not pending_orders:
                console.print(f"[green]🎉 All orders processed![/green]")
                break
        
        # Report on any remaining unfilled orders
        if pending_orders:
            console.print(f"[yellow]⏰ Timeout reached. {len(pending_orders)} orders still pending:[/yellow]")
            for order_info in pending_orders:
                orderid = order_info['orderid']
                symbol = order_info['symbol']
                fillstatus, _, _ = orderstatus(orderid, config, token)
                console.print(f"[dim]⏳ {symbol} Order {orderid}: {fillstatus}[/dim]")
        
        console.print(f"[green]📊 Database transactions recorded: {transactions_recorded}/{len(successful_orders)}[/green]")
    
    # Calculate remaining cash based on actual spending, not failed orders
    remaining_cash = available_cash - total_spent
    
    # Summary
    console.print(Panel(
        f"[bold green]🎯 Autobalance Complete![/bold green]\n\n"
        f"[cyan]📊 Orders Attempted: {orders_attempted}[/cyan]\n"
        f"[cyan]✅ Orders Executed: {orders_executed}[/cyan]\n"
        f"[cyan]❌ Orders Failed: {orders_failed}[/cyan]\n"
        f"[cyan]💰 Total Spent: ${total_spent:.2f}[/cyan]\n"
        f"[cyan]💵 Remaining Cash: ${remaining_cash:.2f}[/cyan]\n"
        f"[cyan]📈 Cash Utilization: {(total_spent / available_cash * 100):.1f}%[/cyan]",
        border_style="green",
        title="Autobalance Summary",
        padding=(1, 2)
    ))
    
    get_user_input(console, "\n[dim]Press Enter to continue...[/dim]", allow_empty=True)
    return True

def main():
    """Main function - migrated from order.py with Rich styling"""
    # Parse command line arguments
    args = parse_arguments()
    
    # Create configuration based on arguments
    config = TastyConfig(use_test_env=args.test, debug=args.debug)
    
    console = Console()
    
    # Clear screen and show title with environment indicator
    console.clear()
    env_indicator = f"[{'yellow' if config.use_test_env else 'green'}]({config.environment_name})[/]"
    console.print(Align.center(Text(f"🚀 MODERN TRADING INTERFACE {env_indicator}", style="bold blue")), "\n")
    
    # Show environment warning for test mode
    if config.use_test_env:
        console.print(Panel(
            "[bold yellow]⚠️  RUNNING IN TEST MODE ⚠️[/bold yellow]\n"
            "[yellow]You are connected to the Tastyworks sandbox environment.\n"
            "No real trades will be executed.[/yellow]",
            border_style="yellow",
            title="Test Environment"
        ))
        console.print()
    
    try:
        # Load stability data (same as original)
        if config.debug:
            console.print("[yellow]Loading stability data...[/yellow]")
        associative_array = load_csv_to_associative_array(STABILITY_CSV_PATH)
        
        # Authenticate with Tastyworks
        if config.debug:
            console.print(f"[yellow]Authenticating with Tastyworks {config.environment_name} API...[/yellow]")
        token = authenticate_tastyworks(config)
        
        if not token:
            console.print("[red]❌ Authentication failed![/red]")
            return
        
        console.print(f"[green]✅ Authenticated successfully with {config.environment_name} environment[/green]")
        console.print(f"[dim]Token: {token[:20]}...[/dim]")
        console.print(f"[dim]API Endpoint: {config.base_url}[/dim]\n")
        
        # Create and display buy recommendations table
        if config.debug:
            console.print("[yellow]Fetching buy recommendations...[/yellow]")
        buy_table = create_buy_recommendations_table(associative_array)
        buy_data = get_buy_recommendations()  # Get count for sell table ID numbering
        buy_count = len(buy_data)
        
        console.print(buy_table)
        console.print()  # Add spacing between tables
        
        # Create and display sell recommendations table
        if config.debug:
            console.print("[yellow]Fetching sell recommendations...[/yellow]")
        sell_table = create_sell_recommendations_table(token, config, associative_array, buy_count)
        console.print(sell_table)
        
        # Get and display cash available
        if config.debug:
            console.print("\n[yellow]Fetching cash balance...[/yellow]")
        try:
            # Get the appropriate account ID
            if config.use_test_env:
                account_id = get_account_id(token, config)
                if not account_id:
                    raise Exception("Could not determine account ID for test environment")
            else:
                account_id = "5WY68491"  # Production account ID
            
            # Make direct API call to get cash balance
            url = config.base_url + f"/accounts/{account_id}/balances"
            headers = {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "Authorization": token,
            }
            response = requests.request("GET", url, headers=headers)
            data = json.loads(response.text)
            
            if "error" in data:
                raise Exception(f"API Error: {data['error']['message']}")
            
            available_cash = float(data["data"]["cash-balance"])
            console.print(Panel(
                f"[bold green]💰 Cash Available: ${available_cash:,.2f}[/bold green]",
                border_style="green",
                padding=(0, 1)
            ))
        except Exception as e:
            console.print(Panel(
                f"[bold red]❌ Could not fetch cash balance: {e}[/bold red]",
                border_style="red",
                padding=(0, 1)
            ))
        
        # Start interactive menu loop
        buy_data = get_buy_recommendations()
        buy_count = len(buy_data)
        
        console.print("\n[green]✅ Ready for interactive trading![/green]")
        
        # Main interaction loop
        while True:
            choice = getchoice(console)
            
            if choice is None:
                console.print("[yellow]👋 Goodbye![/yellow]")
                break  # Exit the loop if 'q' was entered
                
            elif choice == "refresh":
                # Refresh tables and continue
                buy_data, available_cash = refresh_tables(token, config, associative_array, console)
                buy_count = len(buy_data)
                continue
                
            elif choice == "autobalance":
                # Autobalance portfolio - execute buy orders until cash exhausted
                autobalance_result = autobalance_portfolio(token, config, associative_array, console)
                if autobalance_result:
                    # Refresh tables after autobalance
                    buy_data, available_cash = refresh_tables(token, config, associative_array, console)
                    buy_count = len(buy_data)
                continue
                
            # Handle order selection
            try:
                # Get fresh data for order processing
                buy_data = get_buy_recommendations()
                sell_data, positions = get_sell_recommendations(token, config)
                buy_count = len(buy_data)
                
                # Get current cash balance
                if config.use_test_env:
                    account_id = get_account_id(token, config)
                else:
                    account_id = "5WY68491"
                
                url = config.base_url + f"/accounts/{account_id}/balances"
                headers = {
                    "Accept": "application/json",
                    "Content-Type": "application/json", 
                    "Authorization": token,
                }
                response = requests.request("GET", url, headers=headers)
                data = json.loads(response.text)
                available_cash = float(data["data"]["cash-balance"])
                
                # Determine if choice is from buy or sell table
                if choice < buy_count:
                    # BUY ORDER LOGIC
                    symbol = buy_data[choice][0]
                    current_price = getprice(symbol)
                    target_value = round(min(abs(buy_data[choice][1]), available_cash * 0.95), 2)
                    
                    if config.use_test_env:
                        # TEST ENVIRONMENT: Round to whole shares
                        shares_to_buy = int(target_value / current_price)
                        actual_value = shares_to_buy * current_price
                        
                        console.print(f"\n[green]🛒 Preparing BUY order for {symbol}[/green]")
                        console.print(f"[dim]Buying {shares_to_buy} shares @ ${current_price:.2f} = ${actual_value:.2f}[/dim]")
                        console.print(f"[dim]Target was ${target_value:.2f}, adjusted to whole shares for test environment[/dim]")
                        
                        # Skip if we can't afford even 1 share
                        if shares_to_buy == 0:
                            console.print("[red]❌ Cannot afford even 1 share at current price[/red]")
                            continue
                        
                        # Prepare order payload - Regular Market order with quantity
                        payload = json.dumps({
                            "order-type": "Market",
                            "time-in-force": "Day",
                            "legs": [{
                                "instrument-type": "Equity",
                                "action": "Buy to Open",
                                "symbol": symbol,
                                "quantity": shares_to_buy,
                            }],
                        })
                    else:
                        # PRODUCTION ENVIRONMENT: Allow fractional shares
                        shares_to_buy = target_value / current_price  # Fractional shares for logging
                        actual_value = target_value

                        console.print(f"\n[green]🛒 Preparing BUY order for {symbol}[/green]")
                        console.print(f"[dim]Buying ${target_value:.2f} worth of {symbol} @ ${current_price:.2f}[/dim]")
                        console.print(f"[dim]Using fractional shares for production environment[/dim]")

                        # Prepare order payload - Notional Market order with value
                        payload = json.dumps({
                            "order-type": "Notional Market",
                            "value-effect": "Debit",
                            "time-in-force": "Day",
                            "value": target_value,
                            "legs": [{
                                "instrument-type": "Equity",
                                "action": "Buy to Open",
                                "symbol": symbol,
                            }],
                        })
                    
                    # Display order preview
                    order_table = format_order_details(payload, config)
                    console.print(order_table)
                    
                    # Get confirmation
                    action = get_user_input(console, "\n[bold cyan]Enter 'e' to execute, 'r' to refresh, 'q' to quit: [/bold cyan]", ['e', 'r', 'q'])
                    
                    if action.lower() == "e":
                        # Execute the order
                        if config.debug:
                            console.print("[yellow]🚀 Executing order...[/yellow]")
                        else:
                            console.print("[green]🚀 Executing order...[/green]")
                        
                        order_url = config.base_url + f"/accounts/{account_id}/orders"
                        order_headers = {
                            "Accept": "application/json",
                            "Content-Type": "application/json",
                            "Authorization": token,
                        }
                        
                        order_response = requests.request("POST", order_url, headers=order_headers, data=payload)
                        order_data = json.loads(order_response.text)
                        
                        if "error" in order_data:
                            error_details = []
                            error_details.append(f"[red]Message: {order_data['error'].get('message', 'Unknown error')}[/red]")
                            
                            # Check for detailed error information
                            if 'code' in order_data['error']:
                                error_details.append(f"[red]Code: {order_data['error']['code']}[/red]")
                            
                            if 'errors' in order_data['error']:
                                error_details.append(f"[red]Details:[/red]")
                                for err in order_data['error']['errors']:
                                    error_details.append(f"[red]  • {err}[/red]")
                            
                            # Show full response for debugging
                            error_details.append(f"\n[dim]Full API Response:[/dim]")
                            error_details.append(f"[dim]{json.dumps(order_data, indent=2)}[/dim]")
                            
                            console.print(Panel(
                                f"[bold red]❌ Order Failed[/bold red]\n" + "\n".join(error_details),
                                border_style="red",
                                title="Order Error"
                            ))
                        else:
                            # Order successful
                            orderid = order_data["data"]["order"]["id"]
                            status = order_data["data"]["order"]["status"]
                            
                            console.print(Panel(
                                f"[bold green]✅ Order Submitted Successfully![/bold green]\n"
                                f"[green]Order ID: {orderid}[/green]\n"
                                f"[green]Status: {status}[/green]\n"
                                f"[green]Symbol: {symbol}[/green]",
                                border_style="green",
                                title="Order Confirmation"
                            ))
                            
                            # Log the order
                            log_order(orderid, symbol, "Buy", shares_to_buy, current_price, actual_value, status, order_data)
                            
                            # Wait for order to fill and record transaction in database
                            console.print("[dim]Waiting for order to fill...[/dim]")
                            time.sleep(10)
                            
                            # Check fill details and record transaction in database
                            fillstatus, fillsize, fillprice = orderstatus(orderid, config, token)
                            
                            # Record transaction like the working order.py does
                            actual_price = fillprice if fillprice > 0 else current_price
                            insert_order(symbol, fillsize if fillsize > 0 else (target_value / current_price if config.use_test_env else 1), actual_price, "Buy")
                            console.print(f"[green]✅ Transaction recorded in database: {symbol} @ ${actual_price:.2f} (Status: {fillstatus})[/green]")
                            
                            # Save order to file
                            filename = f"{orderid}.json"
                            with open(filename, "w") as f:
                                json.dump(order_data, f, indent=4)
                            
                            console.print(f"[dim]Order details saved to {filename}[/dim]")
                        
                        get_user_input(console, "\n[dim]Press Enter to continue...[/dim]", allow_empty=True)
                        
                    elif action.lower() == "r":
                        buy_data, available_cash = refresh_tables(token, config, associative_array, console)
                        buy_count = len(buy_data)
                        continue
                    elif action.lower() == "q":
                        console.print("[yellow]👋 Goodbye![/yellow]")
                        break
                        
                else:
                    # SELL ORDER LOGIC
                    sell_index = choice - buy_count
                    if sell_index >= len(sell_data):
                        console.print("[red]❌ Invalid selection[/red]")
                        continue
                    
                    symbol = sell_data[sell_index][0]
                    current_price = getprice(symbol)
                    
                    # Find position quantity
                    position_quantity = None
                    for pos in positions:
                        if pos["symbol"] == symbol:
                            position_quantity = pos["quantity"]
                            break
                    
                    if position_quantity is None:
                        console.print(f"[red]❌ Could not find position for {symbol}[/red]")
                        continue
                    
                    console.print(f"\n[red]💰 Preparing SELL order for {symbol}[/red]")
                    console.print(f"[dim]Selling {position_quantity} shares @ ${current_price:.2f}[/dim]")
                    
                    # Prepare sell order payload
                    payload = json.dumps({
                        "order-type": "Market",
                        "time-in-force": "Day",
                        "legs": [{
                            "instrument-type": "Equity",
                            "action": "Sell to Close",
                            "symbol": symbol,
                            "quantity": position_quantity,
                        }],
                    })
                    
                    # Display order preview
                    order_table = format_order_details(payload, config)
                    console.print(order_table)
                    
                    # Get confirmation
                    action = get_user_input(console, "\n[bold cyan]Enter 'e' to execute, 'r' to refresh, 'q' to quit: [/bold cyan]", ['e', 'r', 'q'])
                    
                    if action.lower() == "e":
                        # Execute the sell order
                        if config.debug:
                            console.print("[yellow]🚀 Executing sell order...[/yellow]")
                        else:
                            console.print("[green]🚀 Executing sell order...[/green]")
                        
                        order_url = config.base_url + f"/accounts/{account_id}/orders"
                        order_headers = {
                            "Accept": "application/json",
                            "Content-Type": "application/json",
                            "Authorization": token,
                        }
                        
                        order_response = requests.request("POST", order_url, headers=order_headers, data=payload)
                        order_data = json.loads(order_response.text)
                        
                        if "error" in order_data:
                            error_details = []
                            error_details.append(f"[red]Message: {order_data['error'].get('message', 'Unknown error')}[/red]")
                            
                            # Check for detailed error information
                            if 'code' in order_data['error']:
                                error_details.append(f"[red]Code: {order_data['error']['code']}[/red]")
                            
                            if 'errors' in order_data['error']:
                                error_details.append(f"[red]Details:[/red]")
                                for err in order_data['error']['errors']:
                                    error_details.append(f"[red]  • {err}[/red]")
                            
                            # Show full response for debugging
                            error_details.append(f"\n[dim]Full API Response:[/dim]")
                            error_details.append(f"[dim]{json.dumps(order_data, indent=2)}[/dim]")
                            
                            console.print(Panel(
                                f"[bold red]❌ Order Failed[/bold red]\n" + "\n".join(error_details),
                                border_style="red",
                                title="Order Error"
                            ))
                        else:
                            # Order successful
                            orderid = order_data["data"]["order"]["id"]
                            status = order_data["data"]["order"]["status"]
                            
                            console.print(Panel(
                                f"[bold green]✅ Sell Order Submitted Successfully![/bold green]\n"
                                f"[green]Order ID: {orderid}[/green]\n"
                                f"[green]Status: {status}[/green]\n"
                                f"[green]Symbol: {symbol}[/green]\n"
                                f"[green]Quantity: {position_quantity}[/green]",
                                border_style="green",
                                title="Sell Order Confirmation"
                            ))
                            
                            # Log the order
                            log_order(orderid, symbol, "Sell", position_quantity, current_price, 
                                    float(position_quantity) * current_price, status, order_data)
                            
                            # Wait for order to fill and record transaction in database
                            console.print("[dim]Waiting for order to fill...[/dim]")
                            time.sleep(10)
                            
                            # Check fill details and record transaction in database
                            fillstatus, fillsize, fillprice = orderstatus(orderid, config, token)
                            
                            # Record transaction like the working order.py does
                            actual_price = fillprice if fillprice > 0 else current_price
                            insert_order(symbol, fillsize if fillsize > 0 else position_quantity, actual_price, "Sell")
                            console.print(f"[green]✅ Transaction recorded in database: {symbol} @ ${actual_price:.2f} (Status: {fillstatus})[/green]")
                            
                            # Save order to file
                            filename = f"{orderid}.json"
                            with open(filename, "w") as f:
                                json.dump(order_data, f, indent=4)
                            
                            console.print(f"[dim]Order details saved to {filename}[/dim]")
                        
                        get_user_input(console, "\n[dim]Press Enter to continue...[/dim]", allow_empty=True)
                        
                    elif action.lower() == "r":
                        buy_data, available_cash = refresh_tables(token, config, associative_array, console)
                        buy_count = len(buy_data)
                        continue
                    elif action.lower() == "q":
                        console.print("[yellow]👋 Goodbye![/yellow]")
                        break
                        
            except (ValueError, IndexError):
                console.print("[red]❌ Invalid selection. Please try again.[/red]")
                continue
            except Exception as e:
                console.print(Panel(
                    f"[bold red]❌ Unexpected error: {e}[/bold red]",
                    border_style="red",
                    title="Error"
                ))
                continue
        
    except FileNotFoundError as e:
        console.print(Panel(
            f"[bold red]❌ File not found: {e}[/bold red]\n"
            f"[yellow]Make sure you're running from the correct directory with access to:[/yellow]\n"
            f"• {DATABASE_PATH}\n"
            f"• {STABILITY_CSV_PATH}\n"
            f"• Custom modules (loading.py, apicalls.py, dbops.py)",
            border_style="red",
            title="Configuration Error"
        ))
    except ImportError as e:
        console.print(Panel(
            f"[bold red]❌ Missing dependency: {e}[/bold red]\n"
            f"[yellow]Install required packages:[/yellow]\n"
            f"• pip install rich\n"
            f"• Make sure custom modules are available",
            border_style="red",
            title="Import Error"
        ))
    except Exception as e:
        console.print(Panel(
            f"[bold red]❌ Unexpected error: {e}[/bold red]",
            border_style="red",
            title="Error"
        ))

if __name__ == "__main__":
    main()
