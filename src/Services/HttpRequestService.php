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

    public function get($url, $headers = []): string
    {
        $response = $this->client->get($url, ['headers' => $headers]);
        return $response->getBody()->getContents();
    }

    public function post($url, $data, $headers = []): string
    {
        $response = $this->client->post($url, ['json' => $data, 'headers' => $headers]);
        return $response->getBody()->getContents();
    }
}