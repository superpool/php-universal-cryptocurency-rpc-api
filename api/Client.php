<?php

namespace Api;

class Client
{
    // Configuration options
    public $status;
    public $error;
    public $raw_response;
    public $response;
    protected $username;
    protected $password;
    protected $proto;

    // Information and debugging
    protected $host;
    protected $port;
    protected $url;
    protected $CACertificate;
    protected $id = 0;
    protected $forceObject = false;

    /**
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int $port
     * @param string $proto
     * @param string $url
     */
    public function __construct($username, $password, $host = 'localhost', $port = 8332, $url = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
        $this->url = $url;

        // Set some defaults
        $this->proto = 'http';
        $this->CACertificate = null;
    }

    /**
     * @param string|null $certificate
     */
    public function setSSL($certificate = null)
    {
        $this->proto = 'https'; // force HTTPS
        $this->CACertificate = $certificate;
    }

    public function setForceObject($value = true)
    {
        $this->forceObject = $value;
    }

    public function __call($method, $params)
    {
        $this->status = null;
        $this->error = null;
        $this->raw_response = null;
        $this->response = null;

        $params = array_values($params);

        // The ID should be unique for each call
        $this->id++;

        // Build the request, it's ok that params might have any empty array
        $request = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'id' => $this->id
        ];
        if ($params !== null && !empty($params)) {
            $request['params'] = array_pop($params);
        }
        // Build the cURL session
        //echo "{$this->proto}://{$this->host}:{$this->port}/{$this->url}\n";
        // echo json_encode($request,JSON_PRETTY_PRINT) . "\n";
        $curl = curl_init("{$this->proto}://{$this->host}:{$this->port}/{$this->url}");
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_HTTPHEADER => ['Content-type: application/json'],
            CURLOPT_POST => true,
            CURLOPT_HEADER => false,
            CURLOPT_POSTFIELDS => $this->forceObject ? json_encode($request, JSON_FORCE_OBJECT) : json_encode($request)
        ];
        if ($this->username !== null) {
            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $options[CURLOPT_USERPWD] = $this->username . ':' . $this->password;
        }

        if (ini_get('open_basedir')) {
            unset($options[CURLOPT_FOLLOWLOCATION]);
        }

        if ($this->proto == 'https') {
            if (!empty($this->CACertificate)) {
                $options[CURLOPT_CAINFO] = $this->CACertificate;
                $options[CURLOPT_CAPATH] = DIRNAME($this->CACertificate);
            } else {
                $options[CURLOPT_SSL_VERIFYPEER] = false;
            }
        }

        curl_setopt_array($curl, $options);
        $this->raw_response = curl_exec($curl);

        $this->response = json_decode($this->raw_response, true);

        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $curl_error = curl_error($curl);
        curl_close($curl);

        if (!empty($curl_error)) {
            $this->error = $curl_error;
        }

        if (isset($this->response['error'])) {
            $this->error = $this->response['error'];
        } else if ($this->status != 200) {
            switch ($this->status) {
                case 400:
                    $this->error = 'HTTP_BAD_REQUEST';
                    break;
                case 401:
                    $this->error = 'HTTP_UNAUTHORIZED';
                    break;
                case 403:
                    $this->error = 'HTTP_FORBIDDEN';
                    break;
                case 404:
                    $this->error = 'HTTP_NOT_FOUND';
                    break;
            }
        }
        if ($this->error !== null) {
            var_dump($this->error);
            throw new \ErrorException($this->error['message'], $this->error['code']);
            return ['error' => $this->error];
        }

        return $this->response['result'];
    }
}