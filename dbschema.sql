BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "transactions" (
	"id"	INTEGER UNIQUE,
	"date_new"	TEXT,
	"symbol"	TEXT,
	"xtype"	TEXT,
	"acct"	TEXT,
	"price"	REAL,
	"units"	REAL,
	"units_remaining"	REAL,
	"gain"	REAL,
	"tradetype"	TEXT,
	"fee"	REAL,
	"term"	TEXT,
	"disposition"	TEXT,
	"note"	TEXT,
	"datetime"	TEXT,
	PRIMARY KEY("id" AUTOINCREMENT)
);
CREATE TABLE IF NOT EXISTS "prices" (
	"symbol"	TEXT UNIQUE,
	"asset_class"	TEXT,
	"price"	REAL CHECK("price" > 0),
	"lastupdate"	INTEGER,
	"alloc_target"	REAL,
	"compidx"	REAL,
	"compidx2"	REAL,
	"stdev"	REAL,
	"laststatupdate"	INTEGER,
	"volat"	REAL,
	"class"	TEXT,
	"hlr"	REAL,
	"mean50"	REAL,
	"mean200"	REAL,
	"divyield"	REAL
);
CREATE TABLE IF NOT EXISTS "aux_attributes" (
	"symbol"	TEXT,
	"sector"	TEXT,
	"industry"	TEXT,
	"market_cap_cat"	TEXT,
	"market_cap"	REAL,
	"dividend_date"	TEXT
);
CREATE TABLE IF NOT EXISTS "security_values" (
	"symbol"	TEXT,
	"timestamp"	TEXT,
	"high"	TEXT,
	"close"	TEXT,
	"volume"	TEXT,
	"shares"	REAL,
	"cost_basis"	REAL,
	"return"	REAL,
	"cum_divs"	REAL,
	"cbps"	REAL,
	"cum_real_gl"	REAL
);
CREATE TABLE IF NOT EXISTS "historical" (
	"date"	TEXT,
	"value"	TEXT,
	"cost"	TEXT,
	"dret"	REAL,
	"return"	TEXT,
	"WMA8"	TEXT,
	"WMA24"	REAL,
	"WMA28"	REAL,
	"WMA36"	REAL,
	"WMA41"	NUMERIC,
	"WMA48"	REAL,
	"WMA55"	REAL,
	"4wsdev"	TEXT,
	"8wsdev"	TEXT,
	"16wsdev"	TEXT,
	"32wsdev"	TEXT,
	"WMA64"	REAL
);
COMMIT;
