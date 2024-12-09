<?php

namespace Tests\Unit\Factories\Credit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Exception;
use App\Services\HttpRequestService;
use App\Config\ProviderConfig;
use App\Config\ProvidersConfig;
use App\Factories\Credit\CreditProviderFactory;
use App\Services\Credit\Providers\SmavaApiService;
use App\Services\Credit\Providers\IngdibaApiService;

/**
 * @covers \App\Factories\Credit\CreditProviderFactory
 */
class CreditProviderFactoryTest extends TestCase
{
    private CreditProviderFactory $factory;
    private MockObject $httpService;
    private MockObject $logger;
    private MockObject $providersConfig;

    protected function setUp(): void
    {
        $this->httpService = $this->createMock(HttpRequestService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->providersConfig = $this->createMock(ProvidersConfig::class);
        $this->factory = new CreditProviderFactory($this->httpService, $this->logger, $this->providersConfig);
    }

    public function testCreateProvidersWithValidProviders(): void
    {

        $config = [
            'ingdiba' => $this->createMock(ProviderConfig::class),
            'smava' => $this->createMock(ProviderConfig::class),
        ];

        $this->providersConfig->expects($this->once())
            ->method('all')
            ->willReturn($config);

        $providers = $this->factory->createProviders();

        $this->assertCount(2, $providers);
        //var_dump($providers);
        $this->assertInstanceOf(IngdibaApiService::class, $providers[0]);
        $this->assertInstanceOf(SmavaApiService::class, $providers[1]);
    }

    public function testCreateProvidersWithSingleProvider(): void
    {
        $config = [
            'ingdiba' => $this->createMock(ProviderConfig::class),
        ];

        $this->providersConfig->expects($this->once())
            ->method('all')
            ->willReturn($config);

        $providers = $this->factory->createProviders();

        $this->assertCount(1, $providers);
        $this->assertInstanceOf(IngdibaApiService::class, $providers[0]);
    }

    public function testCreateProvidersWithEmptyConfig(): void
    {
        $this->providersConfig->expects($this->once())
            ->method('all')
            ->willReturn([]);

        $providers = $this->factory->createProviders();

        $this->assertEmpty($providers);
    }

    public function testCreateProvidersWithUnknownProvider(): void
    {
        $config = [
            'random' => ['some' => 'config']
        ];

        $this->providersConfig->expects($this->once())
            ->method('all')
            ->willReturn($config);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown provider: random');

        $this->factory->createProviders();
    }
}
