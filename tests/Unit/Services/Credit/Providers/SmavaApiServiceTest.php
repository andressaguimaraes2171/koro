<?php

namespace Tests\Unit\Services\Credit\Providers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use App\Services\Credit\Providers\SmavaApiService;
use App\Services\HttpRequestService;
use App\Config\ProviderConfig;
use Psr\Http\Message\ResponseInterface;
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

    public function testGetRatesReturnsFormattedResponseWhenValid(): void
    {
        $amount = 5000.0;
        $expected = [
            'rate' => '5.99%',
            'duration' => '12 months',
            'provider' => 'smava'
        ];
        $apiResponse = '{"Terms": {"Duration": 1}, "Interest": "5,99%"}';

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
            ->with($this->stringContains('Failed to retrieve smava API response for URL'));

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
            ->with($this->stringContains('Failed to parse smava API'));

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

    public function testConvertRateAndDuration(): void
    {
        $amount = 5000.0;
        $expected = [
            'rate' => '5.99%',
            'duration' => '12 months',
            'provider' => 'smava'
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $bodyMock = $this->createMock(StreamInterface::class);

        $apiResponse = '{"Terms": {"Duration": 1}, "Interest": "5,99%"}';

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