<?php

namespace Api;
class Ethash extends Api
{
    public function magnitude()
    {
        return 1000000000000000000;
    }

    public function getAddresses()
    {
        $result = $this->client->eth_accounts();
        return $result;
    }

    public function getBalance($id = null)
    {
        if ($id === null) {
            $id = $this->getAddresses()[0];
        }
        $result = $this->client->eth_getBalance([$id, 'latest']);
        $result = $this->hexdec($result);
        return ['address' => $id, 'confirmed' => $result, 'immature' => 0];
    }

    public function sendToAdresses($from, $to, $paymentId = false)
    {
        $txids = [];
        foreach ($to as $wallet => $amount) {
            $result = $this->sendToAddress($from, $wallet, $amount);
            $txids[] = [
                'wallet' => $wallet,
                'amount' => $amount,
                'txid' => $result
            ];
        }
        return $txids;
    }

    public function sendToAddress($from, $to, $amount = false, $paymentId = false)
    {
        if ($amount === false) {
            $balance = $this->getBalance($from);
            $amount = $balance['confirmed'] - (0.0001 * $this->magnitude());
        }
        if ($amount < 0) return false;
        $this->client->personal_unlockAccount([$from, 'Bloodlast01', 60]);

        $amount = $this->coinsToSatoshi($amount, false);
        $result = $this->client->eth_sendTransaction([[
            'from' => $from,
            'to' => $to,
            'value' => '0x' . $this->dechex($amount)
        ]]);
        return $result;
    }

    public function getOperationStatus($id = null)
    {
        if ($id === null) {
            $result = $this->client->eth_getTransactionByHash([0]);
        } else {
            $result = $this->client->eth_getTransactionByHash([$id]);
        }
        return $result;
    }
    /*
     * eth_getTransactionByHash
     * eth_sendTransaction
     * params: [{
  "from": "0xb60e8dd61c5d32be8058bb8eb970870f07233155",
  "to": "0xd46e8dd67c5d32be8058bb8eb970870f07244567",
  "gas": "0x76c0", // 30400
  "gasPrice": "0x9184e72a000", // 10000000000000
  "value": "0x9184e72a", // 2441406250
  "data": "0xd46e8dd67c5d32be8d46e8dd67c5d32be8058bb8eb970870f072445675058bb8eb970870f072445675"
}]
     */
}

