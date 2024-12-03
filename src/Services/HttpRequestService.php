<?php

namespace App\Services;

use GuzzleHttp\Client;
class HttpRequestService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function get($url, $headers = [])
    {
        $response = $this->client->get($url, ['headers' => $headers]);
        return $response->getBody()->getContents();
    }

    public function post($url, $data, $headers = [])
    {
        $response = $this->client->post($url, ['json' => $data, 'headers' => $headers]);
        return $response->getBody()->getContents();
    }

    public function setHeaders($headers)
    {
        $this->client->setDefaultOption('headers', $headers);
    }
}