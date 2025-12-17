import requests
import json

testurlbase = "https://api.cert.tastyworks.com"
produrlbase = "https://api.tastyworks.com"



def getcash(token):
    url = testurlbase + "/accounts/5WZ07144/balances"
    #url = produrlbase + "/accounts/5WY68491/balances"
    payload = {}
    headers = {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "Authorization": token,
    }
    response = requests.request("GET", url, headers=headers, data=payload)
    data = json.loads(response.text)
    cash = data["data"]["cash-balance"]
    return cash