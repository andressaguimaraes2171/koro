<?php

namespace Tests\Unit\Services\Credit\Providers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use App\Services\Credit\Providers\SmavaApiService;
use App\Services\HttpRequestService;
use App\Config\ProviderConfig;
use JsonException;
use Exception;
/**
 * @covers \App\Services\Credit\Providers\SmavaApiService
 */
class SmavaApiServiceTest extends TestCase
{
    private SmavaApiService $service;
    private MockObject $httpService;
    private MockObject $logger;
    private MockObject $providerConfig;

    protected function setUp(): void
    {
        $this->httpService = $this->createMock(HttpRequestService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->providerConfig = $this->createMock(ProviderConfig::class);
        $this->service = new SmavaApiService($this->httpService, $this->logger, $this->providerConfig);
    }

    public function testGetRatesReturnsFormattedResponseForValidData(): void
    {
        $amount = 5000;
        $apiUrl = 'https://api.smava.de/rates';
        $accessToken = 'test-token';
        $apiResponse = json_encode([
            'Interest' => '5,5',
            'Terms' => ['Duration' => '2']
        ]);

        $this->providerConfig->expects($this->once())
            ->method('getApiUrl')
            ->willReturn($apiUrl);

        $this->providerConfig->expects($this->once())
            ->method('getXAccessToken')
            ->willReturn($accessToken);

        $this->httpService->expects($this->once())
            ->method('get')
            ->with($apiUrl . '&amount=' . $amount, ['X-Access-key' => $accessToken])
            ->willReturn($apiResponse);

        $expected = [
            'rate' => '5.5',
            'duration' => '24 months',
            'provider' => 'smava'
        ];

        $result = $this->service->getRates($amount);
        $this->assertEquals($expected, $result);
    }

    public function testGetRatesReturnsEmptyArrayOnHttpError(): void
    {
        $amount = 5000.0;

        $this->providerConfig->method('getApiUrl')->willReturn('https://api.smava.test');
        $this->httpService->method('get')
            ->willThrowException(new Exception('Connection failed'));

        $this->logger->expects($this->atLeast(1))
            ->method('error')
            ->with($this->stringContains('Failed to retrieve smava API response for URL'));

        $result = $this->service->getRates($amount);
        $this->assertEquals([], $result);
    }

    public function testGetRatesReturnsEmptyArrayOnInvalidJson(): void
    {
        $amount = 5000.0;
        $invalidJson = '{invalid-json}';

        $this->providerConfig->method('getApiUrl')->willReturn('https://api.smava.test');
        $this->httpService->method('get')->willReturn($invalidJson);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to parse smava API'));

        $result = $this->service->getRates($amount);
        $this->assertEquals([], $result);
    }

    public function testGetRatesReturnsEmptyArrayOnMissingFields(): void
    {
        $amount = 5000.0;
        $invalidResponse = '{"some_field": "value"}';

        $this->providerConfig->method('getApiUrl')->willReturn('https://api.smava.test');
        $this->httpService->method('get')->willReturn($invalidResponse);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Response is missing a valid'));

        $result = $this->service->getRates($amount);
        $this->assertEquals([], $result);
    }

    public function testGetRatesHandlesSingleMonthDuration(): void
    {
        $amount = 5000;
        $apiResponse = json_encode([
            'Interest' => '5,5',
            'Terms' => ['Duration' => '2 years'] // 1 month
        ]);

        $this->providerConfig->method('getApiUrl')
            ->willReturn('https://api.smava.de/rates');

        $this->httpService->method('get')
            ->willReturn($apiResponse);

        $expected = [
            'rate' => '5.5',
            'duration' => '24 months',
            'provider' => 'smava'
        ];

        $result = $this->service->getRates($amount);
        $this->assertEquals($expected, $result);
    }
}
