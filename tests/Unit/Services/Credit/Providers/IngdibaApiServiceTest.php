<?php

namespace Tests\Unit\Services\Credit\Providers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use App\Services\Credit\Providers\IngdibaApiService;
use App\Services\HttpRequestService;
use App\Config\ProviderConfig;
use JsonException;
use Exception;

/**
 * @covers \App\Services\Credit\Providers\IngdibaApiService
 */
class IngdibaApiServiceTest extends TestCase
{
    private IngdibaApiService $service;
    private MockObject $httpService;
    private MockObject $logger;
    private MockObject $providerConfig;

    protected function setUp(): void
    {
        $this->httpService = $this->createMock(HttpRequestService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->providerConfig = $this->createMock(ProviderConfig::class);
        $this->service = new IngdibaApiService($this->httpService, $this->logger, $this->providerConfig);
    }

    public function testGetRatesReturnsFormattedResponseWhenValid(): void
    {
        $amount = 5000.0;
        $apiUrl = 'https://api.ingdiba.test';
        $accessToken = 'test-token';
        $apiResponse = '{"zinsen": "5.99", "duration": "12"}';

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
            'rate' => '5.99%',
            'duration' => '12 months',
            'provider' => 'ingdiba'
        ];

        $result = $this->service->getRates($amount);
        $this->assertEquals($expected, $result);
    }

    public function testGetRatesReturnsEmptyArrayOnHttpError(): void
    {
        $amount = 5000.0;

        $this->providerConfig->method('getApiUrl')->willReturn('https://api.ingdiba.test');
        $this->httpService->method('get')
            ->willThrowException(new Exception('Connection failed'));

        $this->logger->expects($this->atLeast(1))
            ->method('error')
            ->with('Error fetching data from Ingdiba API: Connection failed');

        $result = $this->service->getRates($amount);
        $this->assertEquals([], $result);
    }

    public function testGetRatesReturnsEmptyArrayOnInvalidJson(): void
    {
        $amount = 5000.0;
        $invalidJson = '{invalid-json}';

        $this->providerConfig->method('getApiUrl')->willReturn('https://api.ingdiba.test');
        $this->httpService->method('get')->willReturn($invalidJson);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('JSON parsing error: Syntax error');

        $result = $this->service->getRates($amount);
        $this->assertEquals([], $result);
    }

    public function testGetRatesReturnsEmptyArrayOnMissingFields(): void
    {
        $amount = 5000.0;
        $invalidResponse = '{"some_field": "value"}';

        $this->providerConfig->method('getApiUrl')->willReturn('https://api.ingdiba.test');
        $this->httpService->method('get')->willReturn($invalidResponse);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid response format'));

        $result = $this->service->getRates($amount);
        $this->assertEquals([], $result);
    }

    public function testGetRatesHandlesSingleMonthDuration(): void
    {
        $amount = 5000.0;
        $apiResponse = '{"zinsen": "5.99", "duration": "1"}';

        $this->providerConfig->method('getApiUrl')->willReturn('https://api.ingdiba.test');
        $this->httpService->method('get')->willReturn($apiResponse);

        $expected = [
            'rate' => '5.99%',
            'duration' => '1 month',
            'provider' => 'ingdiba'
        ];

        $result = $this->service->getRates($amount);
        $this->assertEquals($expected, $result);
    }
}
