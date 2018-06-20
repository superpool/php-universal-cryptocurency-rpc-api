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