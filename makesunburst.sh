#!/bin/bash

cd /var/www/html/portfolio/

sqlite3 ./portfolio.sqlite < tree.sql > tree2.csv
./sunburst.py
