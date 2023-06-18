#!/usr/bin/python3

import csv
import plotly.express as px
import pandas as pd

df = pd.read_csv("tree2.csv")

print(df)

fig = px.sunburst(df, path=["sector", "industry", "symbol"], values="value")
# fig.show()

fig.write_html("visualizations/sunburst.html")

fig = px.treemap(
    df, path=[px.Constant("all"), "sector", "industry", "symbol"], values="value"
)
fig.update_traces(root_color="lightgrey")
fig.update_layout(margin=dict(t=50, l=25, r=25, b=25))

fig.write_html("visualizations/tickertreemap.html")


fig = px.treemap(
    df, path=[px.Constant("all"), "sector", "market_cap", "symbol"], values="value"
)
fig.update_traces(root_color="lightgrey")
fig.update_layout(margin=dict(t=50, l=25, r=25, b=25))

fig.write_html("visualizations/marketcaptree.html")
