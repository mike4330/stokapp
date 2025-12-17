import sqlite3


def getlots(symbol):
    """Executes the given SQLite query and returns the results as a list of dictionaries."""
    # print ("symbol is",symbol)
    conn = sqlite3.connect(
        "/var/www/html/portfolio/portfolio.sqlite"
    )  # Replace with your database file path
    cur = conn.cursor()

    query = "SELECT price FROM prices WHERE symbol = ?"
    cur.execute(query, (symbol,))
    price = cur.fetchone()[0]  # Assuming the `price` column is in the first position

    query = "select sum(price*units) as val from transactions \
        WHERE xtype = 'Buy' and symbol = ? \
            and disposition IS NULL group by symbol having val > 0 "

    cur.execute(query, (symbol,))
    rows = cur.fetchall()
    conn.close()

    if rows and rows[0][0] is not None:
        value = round(rows[0][0], 2)
    else:
        value = 0  # Or any other default value you prefer

    return value

def getprice(symbol):
    conn = sqlite3.connect(
        "/var/www/html/portfolio/portfolio.sqlite"
    )  # Replace with your database file path
    cur = conn.cursor()
    query = "SELECT price FROM prices WHERE symbol = ?"
    cur.execute(query, (symbol,))
    price = cur.fetchone()[0]  # Assuming the `price` column is in the first position
    return price
    