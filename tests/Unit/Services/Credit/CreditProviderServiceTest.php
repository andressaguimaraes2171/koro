<?php

namespace Tests\Unit\Services\Credit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Exception;
use App\Services\Credit\CreditProviderService;
use App\Factories\Credit\CreditProviderFactory;
use App\Services\Credit\CreditProviderInterface;

/**
 * @covers \App\Services\Credit\CreditProviderService
 */
class CreditProviderServiceTest extends TestCase
{
    private CreditProviderService $service;
    private MockObject $creditProviderFactory;
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->creditProviderFactory = $this->createMock(CreditProviderFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new CreditProviderService($this->creditProviderFactory, $this->logger);
    }

    public function testRetrieveCreditRatesSuccess(): void
    {
        $amount = 1000.0;
        $mockProvider1 = $this->createMock(CreditProviderInterface::class);
        $mockProvider2 = $this->createMock(CreditProviderInterface::class);

        $provider1Response = [
            'rate' => 5.5,
            'duration' => 12,
            'provider' => 'Provider1'
        ];
        $provider2Response = [
            'rate' => 6.0,
            'duration' => 24,
            'provider' => 'Provider2'
        ];

        $mockProvider1->expects($this->once())
            ->method('getRates')
            ->with($amount)
            ->willReturn($provider1Response);

        $mockProvider2->expects($this->once())
            ->method('getRates')
            ->with($amount)
            ->willReturn($provider2Response);

        $this->creditProviderFactory->expects($this->once())
            ->method('createProviders')
            ->willReturn([$mockProvider1, $mockProvider2]);

        $result = $this->service->retrieveCreditRates($amount);

        $this->assertCount(2, $result);
        $this->assertEquals($provider1Response, $result[0]);
        $this->assertEquals($provider2Response, $result[1]);
    }

    public function testRetrieveCreditRatesWithFactoryException(): void
    {
        $amount = 1000.0;
        $exception = new Exception('Factory error');

        $this->creditProviderFactory->expects($this->once())
            ->method('createProviders')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($exception->getMessage(), ['exception' => $exception]);

        $result = $this->service->retrieveCreditRates($amount);

        $this->assertEmpty($result);
    }

    public function testRetrieveCreditRatesWithInvalidResponse(): void
    {
        $amount = 1000.0;
        $mockProvider = $this->createMock(CreditProviderInterface::class);

        $invalidResponse = [
            'rate' => 5.5,
            'provider' => 'InvalidProvider'
            // Missing duration
        ];

        $mockProvider->expects($this->once())
            ->method('getRates')
            ->willReturn($invalidResponse);

        $this->creditProviderFactory->expects($this->once())
            ->method('createProviders')
            ->willReturn([$mockProvider]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('The formatting of the api service is not correct'), $this->anything());

        $result = $this->service->retrieveCreditRates($amount);
        $this->assertCount(1, $result);
    }

    public function testRetrieveCreditRatesWithEmptyProviders(): void
    {
        $amount = 1000.0;

        $this->creditProviderFactory->expects($this->once())
            ->method('createProviders')
            ->willReturn([]);

        $result = $this->service->retrieveCreditRates($amount);

        $this->assertEmpty($result);
    }
}
