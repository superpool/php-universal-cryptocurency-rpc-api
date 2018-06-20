<?php

namespace Coins;

class sumokoin extends \Api\Cryptonote
{
    public function magnitude()
    {
        return 1000000000;
    }

    public function getBalance(){
        $result = $this->client->getbalance();
        //return ['address' => null, 'confirmed' => $result['unlocked_balance'], 'immature' => $result['balance'] - $result['unlocked_balance']];
        return ['address' => null, 'confirmed' => $result['balance'], 'immature' => $result['balance'] - $result['unlocked_balance']];
    }
}