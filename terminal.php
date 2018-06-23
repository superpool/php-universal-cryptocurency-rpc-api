<?php
require_once('config.php');
function requestInvoice($app, $request, $redis, $apiWallets)
{
    $value = $app->in('Write command: ');
    switch ($value) {
        case 'coins':
            $app->out(' Availiable coins: ' . implode(", ", array_keys($apiWallets)));
            break;
        case 'fck':
        case 'fuck':
            $app->out(' No no no no no! Consuella does not welcome these expressions!.');
            exit;
            break;
        case 'unspent':
            $coins = [];
            $availiableCoins = [];
            foreach ($apiWallets as $coin => $api) {
                if (method_exists($api, 'getUnspent')) {
                    $coins[] = $coin;
                    $availiableCoins[$coin] = $api;
                }
            }
            $app->out(' Availiable coins: ' . implode(", ", $coins));
            $value = $app->in('  Write Coin name: ');
            if (!isset($availiableCoins[$value])) {
                $app->out('  Not found coin: ' . $value);
            } else {
                $api = $apiWallets[$value];
                $unspents = $api->getUnspent();
                foreach ($unspents as $unspent) {
                    $app->out('   ' . json_encode($unspent, JSON_PRETTY_PRINT));
                }
            }
            break;
        case 'addresses':
            $app->out(' Availiable coins: ' . implode(", ", array_keys($apiWallets)));
            $value = $app->in('  Write Coin name: ');
            if (!isset($apiWallets[$value])) {
                $app->out('  Not found coin: ' . $value);
            } else {
                $api = $apiWallets[$value];
                $addresses = [];
                $publicAddresses = $api->getAddresses();
                foreach ($publicAddresses as $addr) {
                    $addresses[] = $addr;
                }
                if (method_exists($api, 'getZAddresses')) {
                    $privateAddresses = $api->getZAddresses();
                    foreach ($privateAddresses as $addr) {
                        $addresses[] = $addr;
                    }
                }
                foreach ($addresses as $addr) {
                    $app->out('  ' . $addr);
                }
            }
            break;
        case 'opid':
            $coins = [];
            $availiableCoins = [];
            foreach ($apiWallets as $coin => $api) {
                if (method_exists($api, 'getZAddresses')) {
                    $coins[] = $coin;
                    $availiableCoins[$coin] = $api;
                }
            }
            $app->out(' Availiable coins: ' . implode(", ", $coins));
            $value = $app->in('  Write Coin name: ');
            if (!isset($availiableCoins[$value])) {
                $app->out('  Not found coin: ' . $value);
            } else {
                $api = $apiWallets[$value];
                $value = $app->in('  Write opid or put empty string for last operation: ');
                $app->out("");
                if (strlen($value) == 0) {
                    $app->out('   ' . json_encode($api->getOperationStatus(), JSON_PRETTY_PRINT));
                } else {
                    $app->out('   ' . json_encode($api->getOperationStatus($value), JSON_PRETTY_PRINT));
                }
            }
            break;
        case 'txid':
            $app->out(' Availiable coins: ' . implode(", ", array_keys($apiWallets)));
            $value = $app->in('  Write Coin name: ');
            if (!isset($apiWallets[$value])) {
                $app->out('  Not found coin: ' . $value);
            } else {
                $api = $apiWallets[$value];
                $value = $app->in('  Write txid: ');
                $app->out("");
                if (strlen($value) == 0) {
                    $app->out('   ' . json_encode($api->getOperationStatus(), JSON_PRETTY_PRINT));
                } else {
                    $app->out('   ' . json_encode($api->getOperationStatus($value), JSON_PRETTY_PRINT));
                }
            }
            break;
        case 'balance':
            foreach ($apiWallets as $name => $api) {
                // echo "==================\r\n";
                $app->out("== " . ucfirst($name));

                $result = $api->getBalance();
                $app->out("    Balance: " . $result['confirmed']);
                $app->out("    Magnitude: " . $api->magnitude());
                $app->out("    Readable: " . $api->satoshiToCoins($result['confirmed']));

                $result = $api->getAddresses();
                $app->out("    Addresses");
                foreach ($result as $addr) {
                    $balance = $api->getBalance($addr);
                    $balance = $api->satoshiToCoins($balance['confirmed']);
                    $app->out("     " . $addr . " " . $balance);
                }
                if (method_exists($api, 'getZAddresses')) {
                    $result = $api->getZAddresses();

                    if (count($result) == 0) {
                        $address = $api->getNewZAddress();
                    }
                    $result = $api->getZAddresses();
                    foreach ($result as $addr) {
                        $balance = $api->getBalance($addr);
                        $balance = $api->satoshiToCoins($balance['confirmed']);
                        $app->out("     " . $addr . " " . $balance);

                    }
                }
                $app->out("");
            }
            break;
        case 'shield':
            foreach ($apiWallets as $name => $api) {
                if (method_exists($api, 'shieldCoinbase')) {
                    $app->out("== " . ucfirst($name));
                    $result = $api->getBalance();
                    $app->out("    Balance: " . number_format($result['confirmed'] / $api->magnitude(), 10, '.', ''));

                    $zaddr = $api->getZAddresses()[0];
                    $app->out("    Try shielding to " . $zaddr);
                    $opids = $api->shieldCoinbase($zaddr);
                    if (count($opids) == 0) {
                        $app->out("     Insufficient balance");
                    } else {
                        //$app->out('   [TEST MODE IS ON]');

                        foreach ($opids as $taddr => $opid) {
                            if ($opid == false) {
                                $app->out("     " . $taddr . " Insufficient balance");
                                continue;
                            }
                            $app->out("     " . $taddr . ": " . json_encode($opid));
                            $data = [
                                $name,
                                $taddr,
                                $zaddr,
                                $api->getBalance($taddr)['confirmed'] / $api->magnitude(),
                                json_encode($opid),
                                time()
                            ];
                            $redis->sAdd('wallet:shielding', implode(":", $data));
                        }
                    }
                    $app->out("");
                }
            }
            break;
        case 'transfer':
            $app->out(' Availiable coins: ' . implode(", ", array_keys($apiWallets)));
            $coin = $app->in('  Write Coin name: ');
            if (!isset($apiWallets[$coin])) {
                $app->out('  Not found coin: ' . $coin);
            } else {
                $api = $apiWallets[$coin];
                $balance = $api->getBalance()['confirmed'];
                $app->out('   Balance: ' . ($balance / $api->magnitude()));
                $app->out('   Raw Balance: ' . $balance);
                $value = $app->in('  Write amount: ');
                $app->out("");
                $paymentId = $app->in('  Write paymentId: ');
                $publicAddresses = $api->getAddresses();
                foreach ($publicAddresses as $addr) {
                    $addresses[] = $addr;
                }
                if (method_exists($api, 'getZAddresses')) {
                    $privateAddresses = $api->getZAddresses();
                    foreach ($privateAddresses as $addr) {
                        $addresses[] = $addr;
                    }
                }
                $app->out("    Addresses");
                foreach ($addresses as $addr) {
                    $balance = $api->getBalance($addr);
                    $balance = $api->satoshiToCoins($balance['confirmed']);
                    $app->out("     " . $addr . " " . $balance);
                }
                $app->out("");
                $from = $app->in('  Write from address: ');
                $to = $app->in('  Write to address: ');
                $result = $api->sendToAddress($from, $to, $value, $paymentId);
                $app->out('   ' . json_encode($result, JSON_PRETTY_PRINT));
                $redis->sAdd('wallet:transfers', $coin . ':' . $from . ':' . $to . ':' . $value . ':test:' . time());

            }
            break;
        case 'exit':
            $app->out("");
            $app->out(' Bye bye!');
            exit;
            break;
        case 'help':
            $app->out(' Under construction but i can whisper to you availiable commands...');
            $app->out('  Support commands `coins`, `shield`, `opid` ,`txid`, `addresses`, `unspent`, `transfer`, `balance`, `help`, `exit`');
            break;
        default:
            $app->out(' Oh oh oh you are too much stupid! Why you not read messages below?');
            $app->out('  Support commands `coins`, `shield`, `opid` ,`txid`, `addresses`, `unspent`, `transfer`, `balance`, `help`, `exit`');
            break;
    }
    $app->out("");
    return requestInvoice($app, $request, $redis, $apiWallets);
}
$log = [];
$console->execute(function ($app) use ($request, $redis, $wallets,$log) {
    $app->out("");
    $app->out('Welcome to payment system v0.9.3.9 RC');
    $app->out('  Now i serve all you requests. My name is RoboBob!');
    $app->out("");
    $app->out('[TESTMODE OFF]');
    $app->out("");
    $app->out(' For security i logging all you actions and you ssh credentials sorry :).');
    $app->out("");
    $app->out('  SSH Connection: ' . $_SERVER['USER'] . '@' . $_SERVER["SSH_CONNECTION"]);
    $app->out("");
    $app->out(' When version changed to v1.0 i can speak with you same siri ;) ');
    $app->out(' And when version changed to v2.0 i\'ll be owner of this world! :grin: ');
    $app->out(' ');
    $app->out(' Support commands `coins`, `shield`, `opid`, `txid`, `addresses`, `unspent`, `transfer`, `balance`, `help`, `exit`, `fuck`.');
    $app->out(' ');
    $app->out("");
    return requestInvoice($app, $request, $redis, $wallets);
});
?>
