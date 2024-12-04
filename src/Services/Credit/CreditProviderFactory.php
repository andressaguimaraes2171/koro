<?php

namespace App\Services\Credit;

use App\Services\Credit\Providers\IngdibaApiService;
use App\Services\Credit\Providers\SmavaApiService;
use App\Services\HttpRequestService;
use Psr\Log\LoggerInterface;
class CreditProviderFactory
{
    private HttpRequestService $httpService;
    private LoggerInterface $logger;
    private array $providers = [];

    private array $apiSettings = [];
    private $providerMapping = [
        'ingdiba' => IngdibaApiService::class,
        'smava' => SmavaApiService::class,
        // Add more providers as needed
    ];
    public function __construct(HttpRequestService $httpService, LoggerInterface $logger, string $providers, array $apiSettings)
    {
        $this->httpService = $httpService;
        $this->logger = $logger;
        $this->providers = explode(',', $providers);
        $this->apiSettings = $apiSettings;
    }


    public function createProviders()
    {
        $providers = [];
        foreach ($this->providers as $provider) {
            if (isset($this->providerMapping[$provider])) {
                $providers[] = new $this->providerMapping[$provider]($this->httpService, $this->logger, $this->apiSettings[$provider]);
            } else {
                throw new \Exception("Unknown provider: $provider");
            }
        }
        return $providers;
    }
}