<?php

namespace App\Config;

use InvalidArgumentException;

class ProviderConfig
{
    private string $apiUrl;
    private string $xAccessToken;

    public function __construct(string $apiUrl, string $xAccessToken) {
        $this->validateApiUrl($apiUrl);
        $this->validateAccessToken($xAccessToken);
        $this->apiUrl = $apiUrl;
        $this->xAccessToken = $xAccessToken;
    }

    private function validateApiUrl(string $apiUrl): void
    {
        if (empty($apiUrl)) {
            throw new InvalidArgumentException('API URL cannot be empty');
        }

        if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid API URL format');
        }
    }

    private function validateAccessToken(string $token): void
    {
        if (empty($token)) {
            throw new InvalidArgumentException('Access token cannot be empty');
        }

        if (strlen($token) < 32) {
            throw new InvalidArgumentException('Access token must be at least 32 characters');
        }
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