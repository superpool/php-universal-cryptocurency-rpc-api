<?php

namespace Api;
class Equihash extends Api
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

    public function sendToAdresses($from, $to, $paymentId = false)
    {
        $txids = [];
        $tempTo = $to;
        $to = [];
        foreach ($tempTo as $wallet => $amount) {
            $to[] = [
                'address' => $wallet,
                'amount' => $amount
            ];
        }
        $result = $this->client->sendmany([$from, $to]);
        if ($result !== false) {
            foreach ($tempTo as $wallet => $amount) {
                $txids[] = [
                    'wallet' => $wallet,
                    'amount' => $amount,
                    'opid' => $result
                ];
            }
        }
        $pending = true;
        while ($pending) {
            sleep(5);
            $status = $this->getOperationStatus($result);
            if ($status['status'] != "executing") {
                if ($status['status'] == 'failed') {
                    return false;
                }
                if ($status['status'] == 'success') {
                    $txid = $status['result']['txid'];
                    foreach ($txids as $id => $tx) {
                        $txids[$id]['txid'] = $txid;
                    }
                    $pending = false;
                }
            }
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
        $result = $this->client->sendmany([$from, [['amount' => $this->coinsToSatoshi($amount), 'address' => $to]]]);
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

