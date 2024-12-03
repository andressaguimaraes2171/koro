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
    public function __construct(HttpRequestService $httpService, LoggerInterface $logger)
    {
        $this->httpService = $httpService;
        $this->logger = $logger;
    }
    private $providerMapping = [
        'ingdiba' => IngdibaApiService::class,
        'smava' => SmavaApiService::class,
        // Add more providers as needed
    ];

    public function createProviders()
    {
        $providers = [];
        foreach (explode(',', $_ENV['ACTIVE_PROVIDERS']) as $provider) {
            if (isset($this->providerMapping[$provider])) {
                $providers[] = new $this->providerMapping[$provider]($this->httpService, $this->logger);
            } else {
                throw new \Exception("Unknown provider: $provider");
            }
        }
        return $providers;
    }
}