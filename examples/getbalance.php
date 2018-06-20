<?php
require_once('../config.php');
foreach ($wallets as $name => $api) {
    echo "== " . $name . "\n";
    $result = $api->getBalance();
    echo "    Balance: " . $result['confirmed'] . "\r\n";
    echo "    Magnitude: " . $api->magnitude() . "\r\n";
    echo "    Readable: " . number_format($result['confirmed'] / $api->magnitude(), 10, '.', '') . "\r\n";
}