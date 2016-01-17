Welcome to BotJack!

BotJack is a RESTful API based bitcoin blackjack game built and designed for bots. Playing this game requires a curl wrapper and will not work in the web browser. Take the token field and pass as a Authorization header. Game play is completely stateless and the token automatically expires after 24 hours.
Instructions:

/create_player/[name]
send bitcoin to the address returned and save the token
NOTE: It may take up to 10 minutes for 2 confirmations to transfer bitcoin to the wallet.
/balance - gets the current wallet balance, min of .0001 required to play
/deal - shuffle and deal a new game
/hit or /stand
repeat and then /cashout/[wallet_address] to send the bitcoin to your wallet
Game Information:

Dealer stops at 17
No insurance, split, double down
ACE counts as 1 or 11
Current Bet is fixed at .01 BTC
Single Deck, a new shuffle each hand
Example from command line:

curl https://botjack.co/create_player/dan

{"wallet":"1PTX9DTsLtjfgH4wvgWkmnHz7Camuf3iKp","player":"dan","access_token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjYxYzIxMGI1Y2RlYWFlMjkzNGYzYmQwOTEyYWU2Nzk0MDc3M2RlN2RiODkzNTcwMjY2NjMwOGE1NTk3NGNlMzQiLCJwbGF5ZXIiOiJkYW4iLCJ3YWxsZXQiOiIxUFRYOURUc0x0amZnSDR3dmdXa21uSHo3Q2FtdWYzaUtwIiwiZXhwIjoxNDUzMTI3OTE0LCJpc3MiOiJib3RqYWNrLmNvIn0.OaDbxZX8YRN-bE6acBchj-6NoNu9LUgnEpRciYE9lAg"}

curl -H "Authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjYxYzIxMGI1Y2RlYWFlMjkzNGYzYmQwOTEyYWU2Nzk0MDc3M2RlN2RiODkzNTcwMjY2NjMwOGE1NTk3NGNlMzQiLCJwbGF5ZXIiOiJkYW4iLCJ3YWxsZXQiOiIxUFRYOURUc0x0amZnSDR3dmdXa21uSHo3Q2FtdWYzaUtwIiwiZXhwIjoxNDUzMTI3OTE0LCJpc3MiOiJib3RqYWNrLmNvIn0.OaDbxZX8YRN-bE6acBchj-6NoNu9LUgnEpRciYE9lAg" https://botjack.co/balance

{"wallet":"1PTX9DTsLtjfgH4wvgWkmnHz7Camuf3iKp","player":"dan","balance":0.1,"access_token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjYxYzIxMGI1Y2RlYWFlMjkzNGYzYmQwOTEyYWU2Nzk0MDc3M2RlN2RiODkzNTcwMjY2NjMwOGE1NTk3NGNlMzQiLCJwbGF5ZXIiOiJkYW4iLCJ3YWxsZXQiOiIxUFRYOURUc0x0amZnSDR3dmdXa21uSHo3Q2FtdWYzaUtwIiwiZXhwIjoxNDUzMTI3OTE0LCJpc3MiOiJib3RqYWNrLmNvIn0.OaDbxZX8YRN-bE6acBchj-6NoNu9LUgnEpRciYE9lAg"}

curl -H "Authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjYxYzIxMGI1Y2RlYWFlMjkzNGYzYmQwOTEyYWU2Nzk0MDc3M2RlN2RiODkzNTcwMjY2NjMwOGE1NTk3NGNlMzQiLCJwbGF5ZXIiOiJkYW4iLCJ3YWxsZXQiOiIxUFRYOURUc0x0amZnSDR3dmdXa21uSHo3Q2FtdWYzaUtwIiwiZXhwIjoxNDUzMTI3OTE0LCJpc3MiOiJib3RqYWNrLmNvIn0.OaDbxZX8YRN-bE6acBchj-6NoNu9LUgnEpRciYE9lAg" https://botjack.co/deal

{"wallet":"1PTX9DTsLtjfgH4wvgWkmnHz7Camuf3iKp","player":"dan","balance":0.0949,"dealer_hand":["XX","9D"],"player_hand":["6D","9S"],"access_token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjYxYzIxMGI1Y2RlYWFlMjkzNGYzYmQwOTEyYWU2Nzk0MDc3M2RlN2RiODkzNTcwMjY2NjMwOGE1NTk3NGNlMzQiLCJwbGF5ZXIiOiJkYW4iLCJ3YWxsZXQiOiIxUFRYOURUc0x0amZnSDR3dmdXa21uSHo3Q2FtdWYzaUtwIiwiZXhwIjoxNDUzMTI3OTE0LCJpc3MiOiJib3RqYWNrLmNvIn0.OaDbxZX8YRN-bE6acBchj-6NoNu9LUgnEpRciYE9lAg"}

curl -H "Authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjYxYzIxMGI1Y2RlYWFlMjkzNGYzYmQwOTEyYWU2Nzk0MDc3M2RlN2RiODkzNTcwMjY2NjMwOGE1NTk3NGNlMzQiLCJwbGF5ZXIiOiJkYW4iLCJ3YWxsZXQiOiIxUFRYOURUc0x0amZnSDR3dmdXa21uSHo3Q2FtdWYzaUtwIiwiZXhwIjoxNDUzMTI3OTE0LCJpc3MiOiJib3RqYWNrLmNvIn0.OaDbxZX8YRN-bE6acBchj-6NoNu9LUgnEpRciYE9lAg" https://botjack.co/hit 

{"wallet":"1PTX9DTsLtjfgH4wvgWkmnHz7Camuf3iKp","player":"dan","balance":0.0949,"dealer_hand":["XX","9D"],"player_hand":["6D","9S","2S"],"access_token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjYxYzIxMGI1Y2RlYWFlMjkzNGYzYmQwOTEyYWU2Nzk0MDc3M2RlN2RiODkzNTcwMjY2NjMwOGE1NTk3NGNlMzQiLCJwbGF5ZXIiOiJkYW4iLCJ3YWxsZXQiOiIxUFRYOURUc0x0amZnSDR3dmdXa21uSHo3Q2FtdWYzaUtwIiwiZXhwIjoxNDUzMTI3OTE0LCJpc3MiOiJib3RqYWNrLmNvIn0.OaDbxZX8YRN-bE6acBchj-6NoNu9LUgnEpRciYE9lAg"}

curl -H "Authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NLCJwbGF5ZXIiOiJkYW4iLCJ3YWxsZXQiOiIxUFRYOURUc0x0amZnSDR3dmdXa21uSHo3Q2FtdWYzaUtwIiwiZXhwIjoxNDUzMTI3OTE0LCJpc3MiOiJib3RqYWNrLmNvIn0.OaDbxZX8YRN-bE6acBchj-6NoNu9LUgnEpRciYE9lAg" https://botjack.co/stand 

{"wallet":"1PTX9DTsLtjfgH4wvgWkmnHz7Camuf3iKp","player":"dan","balance":0.0949,"dealer_hand":["3D","9D","8C"],"player_hand":["6D","9S","2S"],"access_token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjYxYzIxMGI1Y2RlYWFlMjkzNGYzYmQwOTEyYWU2Nzk0MDc3M2RlN2RiODkzNTcwMjY2NjMwOGE1NTk3NGNlMzQiLCJwbGF5ZXIiOiJkYW4iLCJ3YWxsZXQiOiIxUFRYOURUc0x0amZnSDR3dmdXa21uSHo3Q2FtdWYzaUtwIiwiZXhwIjoxNDUzMTI3OTE0LCJpc3MiOiJib3RqYWNrLmNvIn0.OaDbxZX8YRN-bE6acBchj-6NoNu9LUgnEpRciYE9lAg"}

curl -H "Authorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjYxYzIxMGI1Y2RlYWFlMjkzNGYzYmQwOTEyYWU2Nzk0MDc3M2RlN2RiODkzNTcwMjY2NjMwOGE1NTk3NGNlMzQiLCJwbGF5ZXIiOiJkYW4iLCJ3YWxsZXQiOiIxUFRYOURUc0x0amZnSDR3dmdXa21uSHo3Q2FtdWYzaUtwIiwiZXhwIjoxNDUzMTI3OTE0LCJpc3MiOiJib3RqYWNrLmNvIn0.OaDbxZX8YRN-bE6acBchj-6NoNu9LUgnEpRciYE9lAg" https://botjack.co/cashout/1JN8NZsoU6NCFNMpEgN9M8o2TfGM9Dwfti

{"wallet":"1PTX9DTsLtjfgH4wvgWkmnHz7Camuf3iKp","player":"dan","balance":0.0945,"access_token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjYxYzIxMGI1Y2RlYWFlMjkzNGYzYmQwOTEyYWU2Nzk0MDc3M2RlN2RiODkzNTcwMjY2NjMwOGE1NTk3NGNlMzQiLCJwbGF5ZXIiOiJkYW4iLCJ3YWxsZXQiOiIxUFRYOURUc0x0amZnSDR3dmdXa21uSHo3Q2FtdWYzaUtwIiwiZXhwIjoxNDUzMTI3OTE0LCJpc3MiOiJib3RqYWNrLmNvIn0.OaDbxZX8YRN-bE6acBchj-6NoNu9LUgnEpRciYE9lAg"}


Please contact Daniel Molloy for any tech support issues. dmolloy@fvault.net 
