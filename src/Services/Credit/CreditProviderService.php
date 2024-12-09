<?php

namespace App\Services\Credit;
use App\Factories\Credit\CreditProviderFactory;
use Exception;
use Psr\Log\LoggerInterface;

class CreditProviderService
{
    private CreditProviderFactory $creditProviderFactory;
    private LoggerInterface $logger;
    public function __construct(CreditProviderFactory $creditProviderFactory, LoggerInterface $logger)
    {
        $this->creditProviderFactory = $creditProviderFactory;
        $this->logger = $logger;
    }

    public function retrieveCreditRates(float $amount): array
    {
        $providers = [];
        $response = [];
        try {
            $providers = $this->creditProviderFactory->createProviders();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e
            ]);
        }

        foreach ($providers as $provider) {
            $response[] = $provider->getRates($amount);
        }

        try {
            $this->validateResponse($response);
        }  catch (Exception $e) {
            $this->logger->error($e->getMessage(), [
                'response' => $response,
                'exception' => $e
            ]);
        }

        return $response;
    }

    private function validateResponse(array $response): void
    {
        foreach ($response as $key => $offer) {
            if (empty($offer['rate']) || empty($offer['duration']) || empty($offer['provider'])) {
                unset($response[$key]);
                throw new Exception('The formatting of the api service is not correct: '.$offer['provider']);
            }
        }
    }
}