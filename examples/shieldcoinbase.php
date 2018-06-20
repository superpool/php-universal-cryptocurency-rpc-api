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