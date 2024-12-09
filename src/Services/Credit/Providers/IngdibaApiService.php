<?php

namespace App\Services\Credit\Providers;

use App\Config\ProviderConfig;
use App\Services\Credit\CreditProviderInterface;
use App\Services\HttpRequestService;
use Exception;
use JsonException;
use Psr\Log\LoggerInterface;

class IngdibaApiService implements CreditProviderInterface
{
    private HttpRequestService $httpService;
    private LoggerInterface $logger;

    private ProviderConfig $providerApiSettings;

    private string $providerName = 'ingdiba';

    public function __construct(HttpRequestService $httpService, LoggerInterface $logger, ProviderConfig $providerApiSettings)
    {
        $this->httpService = $httpService;
        $this->logger = $logger;
        $this->providerApiSettings = $providerApiSettings;
    }
    private function retrieveApiResponse(int $amount): string
    {
        $url = $this->providerApiSettings->getApiUrl() . '&amount=' . $amount;
        try {
            return $this->httpService->get($url, [
                'X-Access-key' => $this->providerApiSettings->getXAccessToken(),
            ]);
        } catch (Exception $e) {
            $this->logger->error('Error fetching data from Ingdiba API: ' . $e->getMessage(), [
                'url' => $url,
            ]);
        }
        return '';
    }
    private function apiResponseToArray(string $response): array
    {
        try {
            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->error('JSON parsing error: ' . $e->getMessage(), [
                'response' => $response,
                'exception' => $e
            ]);
            return [];
        }
    }
    private function formatProviderResponse(array $response): array
    {

        $offer['rate'] = $response['zinsen'].'%';
        $duration = (int) $response['duration'];
        $offer['duration'] = $duration > 1 ?  $duration . ' months' : $duration . ' month';
        $offer['provider'] = $this->providerName;
        return $offer;

    }

    private function isValidResponse(?array $response): bool
    {
        try {
            if (!array_key_exists('zinsen', $response) || !array_key_exists('duration', $response)) {
                throw new Exception('Invalid response format: missing required fields');
            }

        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), [
                'response' => $response,
                'exception' => $e
            ]);

            return false;
        }

        return true;
    }

    public function getRates(float $amount): array
    {
        $apiResponse = $this->retrieveApiResponse($amount);
        if (!empty($apiResponse)) {
            $response = $this->apiResponseToArray($apiResponse);
            if (!empty($response)) {
                if ($this->isValidResponse($response) === true) {
                    return $this->formatProviderResponse($response);
                }
            }
        }

        return [];
    }
}