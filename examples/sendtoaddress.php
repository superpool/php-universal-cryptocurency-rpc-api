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