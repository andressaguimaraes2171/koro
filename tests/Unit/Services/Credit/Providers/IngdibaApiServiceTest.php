<?php

namespace Tests\Unit\Services\Credit\Providers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use App\Services\Credit\Providers\IngdibaApiService;
use App\Services\HttpRequestService;
use App\Config\ProviderConfig;
use Psr\Http\Message\ResponseInterface;
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
        $expected = [
            'rate' => '5.99%',
            'duration' => '12 months',
            'provider' => 'ingdiba'
        ];
        $apiResponse = '{"zinsen": "5.99", "duration": "12"}';

        $responseMock = $this->createMock(ResponseInterface::class);
        $bodyMock = $this->createMock(StreamInterface::class);

        $bodyMock->expects($this->once())
            ->method('getContents')
            ->willReturn($apiResponse);

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($bodyMock);

        $this->httpService->expects($this->once())
            ->method('send')
            ->willReturn($responseMock);

        $result = $this->service->getRates($amount);
        $this->assertEquals($expected, $result);
    }
    public function testGetRatesReturnsEmptyArrayOnHttpError(): void
    {
        $amount = 5000.0;
        $expected = [];
        $this->httpService->method('send')
            ->willThrowException(new Exception('Connection failed'));

        $this->logger->expects($this->atLeast(1))
            ->method('error')
            ->with($this->stringContains('Failed to retrieve ingdiba API response for URL'));

        $result = $this->service->getRates($amount);
        $this->assertEquals($expected, $result);
    }

    public function testGetRatesReturnsEmptyArrayOnInvalidJson(): void
    {
        $amount = 5000.0;
        $expected = [];

        $responseMock = $this->createMock(ResponseInterface::class);
        $bodyMock = $this->createMock(StreamInterface::class);

        $invalidJson = '{"invalidJson"}';
        $bodyMock->expects($this->once())
            ->method('getContents')
            ->willReturn($invalidJson);

        $responseMock->method('getBody')->willReturn($bodyMock);

        $this->httpService->expects($this->once())
            ->method('send')
            ->willReturn($responseMock);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to parse ingdiba API'));

        $result = $this->service->getRates($amount);
        $this->assertEquals($expected, $result);
    }

    public function testGetRatesReturnsEmptyArrayOnMissingFields(): void
    {
        $amount = 5000.0;
        $expected = [];

        $responseMock = $this->createMock(ResponseInterface::class);
        $bodyMock = $this->createMock(StreamInterface::class);

        $invalidResponse = '{"someField": "value"}';
        $bodyMock->expects($this->once())
            ->method('getContents')
            ->willReturn($invalidResponse);

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($bodyMock);

        $this->httpService->expects($this->once())
            ->method('send')
            ->willReturn($responseMock);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Response is missing a valid'));

        $result = $this->service->getRates($amount);
        $this->assertEquals($expected, $result);
    }

    public function testGetRatesHandlesSingleMonthDuration(): void
    {
        $amount = 5000.0;
        $expected = [
            'rate' => '5.99%',
            'duration' => '1 month',
            'provider' => 'ingdiba'
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $bodyMock = $this->createMock(StreamInterface::class);

        $apiResponse = '{"zinsen": "5.99", "duration": "1"}';

        $bodyMock->expects($this->once())
            ->method('getContents')
            ->willReturn($apiResponse);

        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($bodyMock);

        $this->httpService->expects($this->once())
            ->method('send')
            ->willReturn($responseMock);

        $result = $this->service->getRates($amount);
        $this->assertEquals($expected, $result);
    }
}
