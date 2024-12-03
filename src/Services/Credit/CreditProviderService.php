<?php

namespace App\Services\Credit;
use App\Services\HttpRequestService;
use Psr\Log\LoggerInterface;

class CreditProviderService
{
    private CreditProviderFactory $creditProviderFactory;
    public function __construct(CreditProviderFactory $creditProviderFactory)
    {

        $this->creditProviderFactory = $creditProviderFactory;
    }

    public function retrieveCreditRates(int $amount)
    {
        $providers = $this->creditProviderFactory->createProviders();
        $response = [];

        foreach ($providers as $provider) {
            $response[] = $provider->getRates($amount);
        }

        return $response;
    }
}