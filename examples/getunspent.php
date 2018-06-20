<?php
require_once('../config.php');
foreach ($wallets as $name => $api) {
    echo "== " . $name . "\n";
    $unspents = $api->getUnspent();
    foreach ($unspents as $unspent) {
        echo json_encode($unspent, JSON_PRETTY_PRINT) . "\r\n";
    }
}