<?php

namespace Api;
class Api
{
    protected $url = '';
    public $client;
    protected $ip;
    protected $port;

    public function __construct($ip = '127.0.0.1', $port, $protocol = 'http', $user = null, $password = null)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;

        $this->client = new Client($this->user, $this->password, $this->ip, $this->port, $this->url);
    }

    public function coinsToSatoshi($amount, $toFloat = true)
    {
        $amount = bcmul($amount,$this->magnitude());
        return ($toFloat == true ? (double)$amount : $amount);
    }
    public function satoshiToCoins($amount){
        return (double)bcdiv($amount,$this->magnitude(),8);
    }

    public function hexdec($hex) {
        if($hex === false){
            return 0;
        }
        if(strlen($hex) == 1) {
            return hexdec($hex);
        } else {
            $remain = substr($hex, 0, -1);
            $last = substr($hex, -1);
            return bcadd(bcmul(16, $this->hexdec($remain)), hexdec($last));
        }
    }

    public function dechex($dec) {
        if($dec === false){
            return 0;
        }
        $last = bcmod($dec, 16);
        $remain = bcdiv(bcsub($dec, $last), 16);

        if($remain == 0) {
            return dechex($last);
        } else {
            return $this->dechex($remain).dechex($last);
        }
    }
}