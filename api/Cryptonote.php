<?php

namespace Api;
class Cryptonote extends Api
{
    protected $url = 'json_rpc';

    public function magnitude()
    {
        return 1000000000000;
    }

    public function getAddresses()
    {
        $result = $this->client->getaddress();
        return [$result['address']];
    }

    public function getBalance()
    {
        $result = $this->client->getbalance();
        return ['address' => null, 'confirmed' => $result['unlocked_balance'], 'immature' => $result['unlocked_balance'] - $result['balance']];
    }

    public function sendToAdresses($from, $to, $paymentId = false)
    {
        $txids = [];

        $tempTo = $to;
        $to = [];

        foreach ($tempTo as $wallet => $amount) {
            $to[] = [
                'address' => $wallet,
                'amount' => $this->coinsToSatoshi($amount)
            ];
        }

        $transaction = [
            'mixin' => 6,
            'destinations' => $to,
        ];

        if ($paymentId !== false) {
            $transaction['payment_id'] = $paymentId;
        }

        $result = $this->client->transfer($transaction);
        if ($result !== false) {
            foreach ($tempTo as $wallet => $amount) {
                $txids[] = [
                    'wallet' => $wallet,
                    'amount' => $amount,
                    'txid' => $result['tx_hash']
                ];
            }
        }
        return $txids;
    }

    public function sendToAddress($from, $to, $amount = false, $paymentId = false)
    {
        //transfer
        if ($amount === false) {
            $balance = $this->getBalance($from);
            $amount = $balance['confirmed'];
        }
        if ($amount < 0) return false;
        $amount = $this->coinsToSatoshi($amount);
        $transaction = [
            'mixin' => 6,
            'destinations' => ['amount' => $amount, 'address' => $to],
        ];
        if ($paymentId !== false) {
            $transaction['payment_id'] = $paymentId;
        }
        $result = $this->client->transfer($transaction);
        return $result;
    }
    public function getUnspent()
    {
        return [[
            'address' => $this->getAddresses()[0],
            'amount' => $this->getBalance()['confirmed']
        ]];
    }
    public function getOperationStatus($id = null)
    {
        //get_transfer_by_txid txid
        if ($id === null) {
            $result = $this->client->get_transfers();
        } else {
            $result = $this->client->get_transfer_by_txid(['txid' => $id]);
            if (isset($result[0])) $result = $result[0];
        }
        return $result;
    }
}
