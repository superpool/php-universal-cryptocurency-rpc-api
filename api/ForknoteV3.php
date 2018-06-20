<?php

namespace Api;
class ForknoteV3 extends Api
{
    protected $url = 'json_rpc';

    public function __construct($ip = '127.0.0.1', $port, $protocol = 'http', $user = null, $password = null)
    {
        parent::__construct($ip = '127.0.0.1', $port, $protocol = 'http', $user, $password);
        $this->client->setForceObject(true);
    }

    public function magnitude()
    {
        return 1000000000000;
    }

    public function getAddresses()
    {
        $result = $this->client->get_addresses([]);
        $result = $result['addresses'];
        return $result;
    }

    public function getBalance($id = null)
    {
        if ($id === null) {
            $result = $this->client->get_balance([]);
        } else {
            $result = $this->client->get_balance(['address' => $id]);
            $result = ['address' => $id, 'confirmed' => $result['spendable'], 'immature' => 0];
            return $result;
        }
        return ['address' => $id, 'confirmed' => $result['spendable'], 'immature' => 0];
    }

    public function sendToAdresses($from, $to, $paymentId = false)
    {
        $txids = [];

        $tempTo = $to;
        $to = [];

        foreach ($tempTo as $wallet => $amount) {
            $to[] = [
                'address' => $wallet,
                'amount' => $this->coinsToSatoshi($amount),
                'ours' => true,
                'outputs' => []
            ];
        }

        $transaction = [
            'change_address' => $from,
            'any_spend_address' => true,
            'transaction' => [
                'transfers' => $to
            ],
        ];

        if ($paymentId !== false) {
            $transaction['payment_id'] = $paymentId;
        }
        $this->client->setForceObject(false);

        $resultTransaction = $this->client->create_transaction($transaction);
        $result = $this->client->send_transaction(['binary_transaction' => $resultTransaction['binary_transaction']]);

        $this->client->setForceObject(true);

        if ($result['send_result'] != 'broadcast') {
            return false;
        }

        foreach ($tempTo as $wallet => $amount) {
            $txids[] = [
                'wallet' => $wallet,
                'amount' => $amount,
                'txid' => $resultTransaction['transaction']['hash']
            ];
        }
        return $txids;
    }

    public function sendToAddress($from, $to, $amount = false, $paymentId = false)
    {
        if ($amount === false) {
            $balance = $this->getBalance($from);
            $amount = $balance['confirmed'] - $this->coinsToSatoshi(0.0001);
        }
        if ($amount < 0) return false;

        $transaction = [
            'change_address' => $from,
            'any_spend_address' => true,
            'transaction' => [
                'transfers' => [
                    ['amount' => $amount, 'address' => $to]
                ]
            ]
        ];

        if ($paymentId !== false) {
            $transaction['payment_id'] = $paymentId;
        }
        $this->client->setForceObject(false);
        $resultTransaction = $this->client->create_transaction($transaction);
        $result = $this->client->send_transaction(['binary_transaction' => $resultTransaction['binary_transaction']]);
        if ($result['send_result'] != 'broadcast') {
            return false;
        }
        $this->client->setForceObject(true);
        return $resultTransaction['transaction']['hash'];
    }

    public function getUnspent()
    {
        $result = $this->client->get_unspents();
        return $result;
    }

    public function getOperationStatus($id = null)
    {
        if ($id === null) {
            $result = $this->client->gettransactions();
        } else {
            $result = $this->client->gettransaction([$id]);
            if (isset($result[0])) $result = $result[0];
        }
        return $result;
    }
}

