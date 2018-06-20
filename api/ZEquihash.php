<?php

namespace Api;
class ZEquihash extends Api
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

    public function getZAddresses()
    {
        $result = $this->client->z_listaddresses([]);
        return $result;
    }

    public function getNewZAddress()
    {
        $result = $this->client->z_getnewaddress();
        return $result;
    }

    public function getUnspent()
    {
        $result = $this->client->listunspent();
        return $result;
    }

    protected function _shieldCoinbase($from, $to)
    {
        $balance = $this->getBalance($from);
        $balance = $balance['confirmed'] / $this->magnitude() - 0.0001;
        if ($balance < 0) return false;
        $result = $this->sendToAddress($from, $to, $balance);
        return $result;
    }

    public function shieldCoinbase($to = '*', $from = '*')
    {
        $taddrs = [];
        if ($from == '*') {
            $unspent = $this->getUnspent();
            foreach ($unspent as $tx) {
                if (!in_array($tx['address'], $taddrs)) {
                    $taddrs[] = $tx['address'];
                }
            }
        } else {
            $taddrs[] = $from;
        }
        if ($to == '*') {
            $zaddrs = $this->getZAddresses();
            $to = $zaddrs[0];
        }
        $result = [];
        foreach ($taddrs as $taddr) {
            $result[$taddr] = $this->_shieldCoinbase($taddr, $to);
        }
        return $result;
    }

    public function getBalance($id = null)
    {
        if ($id === null) {
            $result = $this->client->z_gettotalbalance();
        } else {
            $result = $this->client->z_getbalance([$id]);
            $result = ['address' => $id, 'confirmed' => $this->coinsToSatoshi($result), 'immature' => 0];
            return $result;
        }
        return ['address' => $id, 'confirmed' => $this->coinsToSatoshi($result['total']), 'immature' => 0];
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
        $result = $this->client->z_sendmany([$from, $to]);
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
                if (isset($status['error'])) {
                    throw new \ErrorException($status['error']['message'], $status['error']['code']);
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
            $amount = $balance['confirmed'] / $this->magnitude() - 0.0001;
        }
        if ($amount < 0) return false;
        $result = $this->client->z_sendmany([$from, [['amount' => $amount, 'address' => $to]]]);
        $pending = true;
        while ($pending) {
            sleep(5);
            $status = $this->getOperationStatus($result);
            if ($status['status'] != "executing") {
                if ($status['status'] == 'failed') {
                    return false;
                }
                if ($status['status'] == 'success') {
                    return $result;
                }
            }
        }
        return $result;
    }

    public function getOperationStatus($id = null)
    {
        if ($id === null) {
            $result = $this->client->z_getoperationstatus();
        } else {
            $result = $this->client->z_getoperationstatus([[$id]]);
            if (isset($result[0])) $result = $result[0];
        }
        return $result;
    }
}

