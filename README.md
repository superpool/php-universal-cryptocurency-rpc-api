# php-universal-cryptocurency-rpc-api
PHP library provide rpc-calls for many cryptocurencies. Support ethash, bitcoin-core (v0.13 - 0.16), equihash, cryptonote based coins.

Supported rpc's API
- Bitcoin-core (v0.13 - 0.16)
- Equihash (zerocash)
- Equihash (bitcoin-core)
- Cryptonote
- Forknote v2
- Forknote v3
- Ethash

Supported by API command's
- magnitude

- coinsToSatoshi
- satoshiToCoins

- getAddresses
- getBalance
- sendToAdresses
- sendToAddress
- getUnspent
- getOperationStatus

-- ZeroCash only

- getZAddresses 
- getNewZAddress
- shieldCoinbase

Supported by php console interactive application command's
- coins
- shield
- opid
- txid
- addresses
- unspent
- transfer
- balance 
