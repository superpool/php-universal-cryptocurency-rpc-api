<?php
// PATH FOR INCLUDE
$path = 'YOUR PATH TO PHP-WALLET';

require_once($path . '/vendor/request/Request.php');
require_once($path . '/vendor/console/Console.php');

// LOAD RPC CLIENT API's
require_once($path . '/api/Client.php');

require_once($path . '/api/Api.php');

require_once($path . '/api/Cryptonote.php');
require_once($path . '/api/Forknote.php');
require_once($path . '/api/ForknoteV3.php');
require_once($path . '/api/Equihash.php');
require_once($path . '/api/ZEquihash.php');
require_once($path . '/api/X11Gost.php');
require_once($path . '/api/Bitcoin.php');
require_once($path . '/api/Ethash.php');
require_once($path . '/api/NeoScrypt.php');

// DEFINE GLOBAL ARRAY OF API WALLETS
$wallets = [];

// DEFINE COINS MORE OPTIONS AND DEFINITIONS IN /coins/COINNAME.php
$walletConfigs = [
    'monero' => [
        'protocol' => 'http',
        'ip' => 'WALLET IP (localhost)',
        'port' => 'WALLET PORT',
        'user' => null,
        'password' => null,
    ],
    'bytecoin' => [
        'protocol' => 'http',
        'ip' => 'WALLET IP (localhost)',
        'port' => 'WALLET PORT',
        'user' => null,
        'password' => null,
    ],
    'musicoin' => [
        'protocol' => 'http',
        'ip' => '127.0.0.1',
        'port' => '21301',
        'user' => null,
        'password' => null,
    ],
    'bitcoingold' => [
        'protocol' => 'http',
        'ip' => 'DAEMON IP (localhost)',
        'port' => 'DAEMON PORT',
        'user' => 'RPC USER',
        'password' => 'RPC PASSWORD',
    ],
    'sibcoin' => [
        'protocol' => 'http',
        'ip' => 'DAEMON IP (localhost)',
        'port' => 'DAEMON PORT',
        'user' => 'RPC USER',
        'password' => 'RPC PASSWORD',
    ],

    'zclassic' => [
        'protocol' => 'http',
        'ip' => 'DAEMON IP (localhost)',
        'port' => 'DAEMON PORT',
        'user' => 'RPC USER',
        'password' => 'RPC PASSWORD',
    ],
    'zcash' => [
        'protocol' => 'http',
        'ip' => 'DAEMON IP (localhost)',
        'port' => 'DAEMON PORT',
        'user' => 'RPC USER',
        'password' => 'RPC PASSWORD',
    ],
    'digibyte' => [
        'protocol' => 'http',
        'ip' => 'DAEMON IP (localhost)',
        'port' => 'DAEMON PORT',
        'user' => 'RPC USER',
        'password' => 'RPC PASSWORD',
    ]
];
// INIT WALLET RPC CLIENTS
foreach ($walletConfigs as $name => $wallet) {
    require_once($path . '/coins/' . $name . '.php');
    $className = '\Coins\\' . $name;
    $wallets[$name] = new $className($wallet['ip'], $wallet['port'], $wallet['protocol'], $wallet['user'], $wallet['password']);
}
$console = new Console_Client();
$request = new Request();