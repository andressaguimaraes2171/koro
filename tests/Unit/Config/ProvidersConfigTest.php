<?php

namespace Tests\Unit\Config;

use App\Config\Environment;
use App\Config\ProviderConfig;
use App\Config\ProvidersConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Config\ProvidersConfig
 */
class ProvidersConfigTest extends TestCase
{
    private Environment $environment;
    private ProvidersConfig $providersConfig;

    private const ACCESS_TOKEN_LENGTH = 32;

    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);
        $this->providersConfig = new ProvidersConfig($this->environment);
    }
    public function testAccessTokenLengthValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token must be at least 32 characters');

        new ProviderConfig('http://api.test', 'short-key');
    }

    public function testValidAccessToken(): void
    {
        $validToken = str_repeat('a', 32);
        $config = new ProviderConfig('http://api.test', $validToken);

        $this->assertEquals($validToken, $config->getXAccessToken());
    }

    public function testAddProviderSuccessfully(): void
    {
        $validToken1 = str_repeat('a', self::ACCESS_TOKEN_LENGTH);
        $config = new ProviderConfig('http://api.test', $validToken1);
        $this->providersConfig->addProvider('test', $config);

        $this->assertEquals($config, $this->providersConfig->getProvider('test'));
    }

    public function testAddDuplicateProviderThrowsException(): void
    {
        $validToken1 = str_repeat('a', self::ACCESS_TOKEN_LENGTH);
        $config = new ProviderConfig('http://api.test', $validToken1);
        $this->providersConfig->addProvider('test', $config);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Provider 'test' is already configured");

        $this->providersConfig->addProvider('test', $config);
    }

    public function testGetEnabledProvidersReturnsCorrectList(): void
    {
        $validToken1 = str_repeat('a', self::ACCESS_TOKEN_LENGTH);
        $validToken2 = str_repeat('b', self::ACCESS_TOKEN_LENGTH);
        $config1 = new ProviderConfig('http://api1.test', $validToken1);
        $config2 = new ProviderConfig('http://api2.test', $validToken2);

        $this->providersConfig->addProvider('provider1', $config1);
        $this->providersConfig->addProvider('provider2', $config2);

        $this->assertEquals(['provider1', 'provider2'], $this->providersConfig->getEnabledProviders());
    }

    public function testLoadProvidersFromEnvironment(): void
    {
        $validToken1 = str_repeat('a', self::ACCESS_TOKEN_LENGTH);
        $validToken2 = str_repeat('b', self::ACCESS_TOKEN_LENGTH);
        $this->environment->expects($this->atLeast(4))
            ->method('get')
            ->willReturnMap([
                ['ACTIVE_PROVIDERS', null, 'provider1,provider2'],
                ['API_URL_PROVIDER1', null, 'http://api1.test'],
                ['X_ACCESS_KEY_PROVIDER1', null, $validToken1],
                ['API_URL_PROVIDER2', null, 'http://api2.test'],
                ['X_ACCESS_KEY_PROVIDER2', null, $validToken2]
            ]);

        $providers = $this->providersConfig->all();

        $this->assertCount(2, $providers);
        $this->assertInstanceOf(ProviderConfig::class, $providers['provider1']);
        $this->assertInstanceOf(ProviderConfig::class, $providers['provider2']);
        $this->assertEquals('http://api1.test', $providers['provider1']->getApiUrl());
        $this->assertEquals($validToken1, $providers['provider1']->getXAccessToken());
        $this->assertEquals('http://api2.test', $providers['provider2']->getApiUrl());
        $this->assertEquals($validToken2, $providers['provider2']->getXAccessToken());
    }

    public function testAllReturnsEmptyArrayWhenNoActiveProviders(): void
    {
        $this->environment->method('get')
            ->willReturnMap([
                ['ACTIVE_PROVIDERS', null, '']
            ]);

        $providers = $this->providersConfig->all();
        $this->assertEmpty($providers);
    }
}
