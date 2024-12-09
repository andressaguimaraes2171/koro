<?php

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use App\Config\ProviderConfig;

/**
 * @covers \App\Config\ProviderConfig
 */
class ProviderConfigTest extends TestCase
{
    public function testConstructorWithValidInputs()
    {
        $apiUrl = 'https://api.example.com';
        $token = str_repeat('a', 32);

        $config = new ProviderConfig($apiUrl, $token);

        $this->assertEquals($apiUrl, $config->getApiUrl());
        $this->assertEquals($token, $config->getXAccessToken());
    }

    public function testConstructorWithEmptyApiUrl()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('API URL cannot be empty');

        new ProviderConfig('', str_repeat('a', 32));
    }

    public function testConstructorWithInvalidApiUrl()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid API URL format');

        new ProviderConfig('not-a-url', str_repeat('a', 32));
    }

    public function testConstructorWithEmptyAccessToken()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token cannot be empty');

        new ProviderConfig('https://api.example.com', '');
    }

    public function testConstructorWithShortAccessToken()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token must be at least 32 characters');

        new ProviderConfig('https://api.example.com', 'short_token');
    }

    public function testGetApiUrlReturnsCorrectValue()
    {
        $apiUrl = 'https://api.example.com';
        $config = new ProviderConfig($apiUrl, str_repeat('a', 32));

        $this->assertEquals($apiUrl, $config->getApiUrl());
    }

    public function testGetXAccessTokenReturnsCorrectValue()
    {
        $token = str_repeat('a', 32);
        $config = new ProviderConfig('https://api.example.com', $token);

        $this->assertEquals($token, $config->getXAccessToken());
    }
}
