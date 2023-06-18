.mode csv
.output tree2.csv
.headers on

select security_values.symbol,MPT.sector,market_cap,industry,close*shares as value from security_values,MPT
where security_values.symbol = MPT.symbol
and security_values.timestamp = 
	(select max(timestamp) from security_values where security_values.symbol = MPT.symbol)
group by security_values.symbol
