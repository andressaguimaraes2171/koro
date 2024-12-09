<?php

namespace App\Factories\Credit;

use App\Config\ProvidersConfig;
use App\Services\Credit\Providers\IngdibaApiService;
use App\Services\Credit\Providers\SmavaApiService;
use App\Services\HttpRequestService;
use Exception;
use Psr\Log\LoggerInterface;

class CreditProviderFactory
{
    const PROVIDER_INGDIBA = 'ingdiba';
    const PROVIDER_SMAVA = 'smava';
    private HttpRequestService $httpService;
    private LoggerInterface $logger;
    private ProvidersConfig $providersConfig;

    private array $providerMapping = [
        self::PROVIDER_INGDIBA => IngdibaApiService::class,
        self::PROVIDER_SMAVA => SmavaApiService::class,
        // Add more providers as needed
    ];
    public function __construct(HttpRequestService $httpService, LoggerInterface $logger, ProvidersConfig $providersConfig)
    {
        $this->httpService = $httpService;
        $this->logger = $logger;
        $this->providersConfig = $providersConfig;
    }


    public function createProviders(): array
    {
        $providers = [];
        foreach ($this->providersConfig->all() as $providerName => $provider) {
            if (isset($this->providerMapping[$providerName])) {
                $providers[] = new $this->providerMapping[$providerName]($this->httpService, $this->logger, $provider);

            } else {
                throw new Exception("Unknown provider: $providerName");
            }
        }

        return $providers;
    }
}