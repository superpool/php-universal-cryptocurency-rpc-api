<?php

namespace Api;
class Forknote extends Api
{
    protected $url = 'json_rpc';

    public function magnitude()
    {
        return 1000000000000;
    }

    public function getAddresses()
    {
        $result = $this->client->getAddresses();
        return $result['addresses'];
    }

    public function getBalance()
    {
        $result = $this->client->getBalance();
        return ['address' => null, 'confirmed' => $result['availableBalance'], 'immature' => $result['lockedAmount']];
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
            'addresses' => [$from],
            'fee' => $this->coinsToSatoshi(0.01),
            'anonymity' => 4,
            'transfers' => $to
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
        //sendTransaction
        if ($amount === false) {
            $balance = $this->getBalance($from);
            $amount = $balance['confirmed'];
        }
        if ($amount < 0) return false;
        $amount = $this->coinsToSatoshi($amount);

        $transaction = [
            'addresses' => [$from],
            'fee' => $this->coinsToSatoshi(0.01),
            'anonymity' => 4,
            'transfers' => [
                ['amount' => $amount, 'address' => $to]
            ]
        ];

        if ($paymentId !== false) {
            $transaction['payment_id'] = $paymentId;
        }

        $result = $this->client->sendTransaction($transaction);
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
        //getTransaction transactionHash
        if ($id === null) {
            $result = $this->client->getTransactions();
        } else {
            $result = $this->client->getTransaction(['transactionHash' => $id]);
            if (isset($result[0])) $result = $result[0];
        }
        return $result;
    }
}

