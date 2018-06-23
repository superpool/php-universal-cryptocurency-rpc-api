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
###
- coinsToSatoshi
- satoshiToCoins
###
- getAddresses
- getBalance
- sendToAdresses
- sendToAddress
- getUnspent
- getOperationStatus
###
-- ZeroCash only
###
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

Example's (executing from folder examples)

### Get balance. getbalance.php
```php
<?php
require_once('../config.php');
foreach ($wallets as $name => $api) {
    echo "== " . $name . "\n";
    $result = $api->getBalance();
    echo "    Balance: " . $result['confirmed'] . "\r\n";
    echo "    Magnitude: " . $api->magnitude() . "\r\n";
    echo "    Readable: " . number_format($result['confirmed'] / $api->magnitude(), 10, '.', '') . "\r\n";
}
```

### Shield coinbase. shieldcoinbase.php
```php
<?php
require_once('../config.php');
foreach ($wallets as $name => $api) {
    if (method_exists($api, 'shieldCoinbase')) {
        echo "== " . $name . "\n";
        $zaddr = $api->getZAddresses()[0];
        echo "    Try shielding to " . $zaddr . "\r\n";
        $opids = $api->shieldCoinbase($zaddr);
        if(count($opids) == 0){
            echo "     Insufficient balance\r\n";
        } else {
            foreach ($opids as $taddr => $opid) {
                if ($opid == false) {
                    echo "     " . $taddr . " Insufficient balance" . "\r\n";
                    continue;
                }
                echo "     " . $taddr . ": " . $opid . "\r\n";
            }
        }
        echo "\r\n";
    }
}
```

### Get unspent output's. getunspent.php
```php
<?php
require_once('../config.php');
foreach ($wallets as $name => $api) {
    echo "== " . $name . "\n";
    $unspents = $api->getUnspent();
    foreach ($unspents as $unspent) {
        echo json_encode($unspent, JSON_PRETTY_PRINT) . "\r\n";
    }
}
```
### Send to address. sendtoaddress.php
```php
<?php
require_once('../config.php');

$reciverAddress = 't1*************************';
$reciveAmount = 0.1;

$api = $wallets['zcash'];

$addrs = $api->getAddresses();

$balance = $api->getBalance($addrs[0]);
$balance = ($balance['confirmed'] / $api->magnitude());
echo "     " . $addr . " " . $balance . "\n";
if ($balance > $reciveAmount) { 
    $opid = $api->sendToAddress($addrs[0], $reciverAddress, $reciveAmount);
    echo "      Transaction operation id: " . $opid . "\r\n"; 
} else echo "      Insufficient balance\r\n";
```

### Send to addresses. sendtoaddresses.php
```php
<?php
require_once('../config.php');
/*
 * Sample of batch transactions
 */
$paymentsData = [
    'zcash' => [
        'from' => 'Your ZCash address',
        'to' => [
            ['address' => 'ZCash address', 'amount' => 0.14],
            ['address' => 'ZCash address', 'amount' => 0.099],
            ['address' => 'ZCash address', 'amount' => 0.781],
        ]
    ],
    'monero' => [
        'from' => 'Your Monero address',
        'to' => [
            ['address' => 'Monero wallet address', 'amount' => 0.96],
            ['address' => 'Monero wallet address', 'amount' => 1.16],
        ]
    ],
    'digibyte' => [
        'from' => '*',
        'to' => [
            ['address' => 'Digibyte wallet address', 'amount' => 15.7],
        ]
    ]
];
/*
 * Normalize transactions
 */
$preparedPayments = [];
foreach ($paymentsData as $coin => $payments){
    if (!isset($preparedPayments[$coin])) {
        $preparedPayments[$coin] = [];
    }
    foreach ($payments['to'] as $payment) {
        if (!isset($preparedPayments[$coin][$payment['address']])) {
            $preparedPayments[$coin][$payment['address']] = 0;
        }
        $preparedPayments[$coin][$payment['address']] += $payment['amount'];
    }
}
/*
 * Execute transaction
 */
foreach ($preparedPayments as $coin => $payments) {
    $api = $wallets[$coin];

    echo "== " . $coin . "\n";
    echo "   Pay from: " . $paymentsData[$coin]['from'] . "\r\n";
    echo "   Count: " . count($payments) . "\r\n";
    try {
        $txids = $api->sendToAdresses($paymentsData[$coin]['from'], $payments);
    } catch (\Exception $exception) {
        $txids = false;
        $error = $exception;
    }
    echo "   Transactions: \n";
    if ($txids !== false) {
        foreach ($txids as $tx) {
            echo "     " . $tx['wallet'] . ";" . $tx['amount'] . ";" . $tx['txid'] . "\r\n";
        }
    } else {
        echo "   (!) Failed\r\n";
        echo "      Code: " . $error->getCode() . "\r\n";
        echo "      Message: " . $error->getMessage() . "\r\n";

    }
    echo "\r\n";
}
```

Terminal

