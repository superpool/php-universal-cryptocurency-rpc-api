<?php

namespace Api;
class NeoScrypt extends Api
{
    public function magnitude()
    {
        return 100000000;
    }

    public function getAddresses()
    {
        $result = $this->client->getaddressesbyaccount([""]);
        return $result;
    }

    public function getBalance($id = null)
    {
        if ($id === null) {
            $result = $this->client->getwalletinfo([]);
        } else {
            $result = $this->client->getbalance([$id]);
            $result = ['address' => $id, 'confirmed' => $this->coinsToSatoshi($result), 'immature' => 0];
            return $result;
        }
        return ['address' => $id, 'confirmed' => $this->coinsToSatoshi($result['balance']), 'immature' => 0];
    }

    public function getUnspent()
    {
        $result = $this->client->listunspent();
        return $result;
    }

    public function sendToAdresses($from, $to, $paymentId = false)
    {
        $txids = [];
        $result = $this->client->sendmany(["", $to]);
        if ($result !== false) {
            foreach ($to as $wallet => $amount) {
                $txids[] = [
                    'wallet' => $wallet,
                    'amount' => $amount,
                    'txid' => $result
                ];
            }
        }
        return $txids;
    }

    public function sendToAddress($from, $to, $amount = false, $paymentId = false)
    {
        if ($amount === false) {
            $balance = $this->getBalance();
            $amount = $balance['confirmed'] - $this->coinsToSatoshi(0.0001);
        }
        if ($amount < 0) return false;
        $result = $this->client->sendmany(["", [$to => $amount]]);
        if ($result != false) {
            $result = $this->getOperationStatus($result);
        }
        return $result;
    }

    public function getOperationStatus($id = null)
    {
        if ($id === null) {
            $result = $this->client->getoperationstatus();
        } else {
            $result = $this->client->getoperationstatus([[$id]]);
            if (isset($result[0])) $result = $result[0];
        }
        return $result;
    }
}

