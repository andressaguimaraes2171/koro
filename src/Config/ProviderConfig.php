<?php

namespace App\Config;

class ProviderConfig
{
    private string $apiUrl;
    private string $xAccessToken;

    public function __construct(string $apiUrl, string $xAccessToken) {
        $this->apiUrl = $apiUrl;
        $this->xAccessToken = $xAccessToken;
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function getXAccessToken(): string
    {
        return $this->xAccessToken;
    }
}