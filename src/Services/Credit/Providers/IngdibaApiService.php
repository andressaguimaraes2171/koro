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
    private function retrieveApiResponse(int $amount)
    {
        $url = $this->providerApiSettings->getApiUrl() . '&amount=' . $amount;
        return $this->httpService->get($url, [
            'X-Access-key' => $this->providerApiSettings->getXAccessToken(),
        ]);
    }

    private function formatProviderResponse(string $response): array
    {
        try {
            $decodedResponse = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            $validatedResponse = $this->validateResponse($decodedResponse);
            $offer['rate'] = $validatedResponse['zinsen'].'%';

            $duration = (int) $validatedResponse['duration'];
            $offer['duration'] = $duration > 1 ?  $duration . ' months' : $duration . ' month';
            $offer['provider'] = $this->providerName;
            return $offer;

        } catch (JsonException $e) {
            $this->logger->error('JSON parsing error: ' . $e->getMessage(), [
                'response' => $response,
                'exception' => $e
            ]);

            return [];

        } catch (Exception $e) {
            $this->logger->error('Unexpected error in response formatting: ' . $e->getMessage(), [
                'response' => $response,
                'exception' => $e
            ]);

            return [];
        }
    }

    private function validateResponse(?array $response): array
    {
        try {
            if (!array_key_exists('zinsen', $response) || !array_key_exists('duration', $response)) {
                throw new Exception('Invalid response format: missing required fields');
            }

            return $response;

        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), [
                'response' => $response,
                'exception' => $e
            ]);

            return [];

        }
    }

    public function getRates(float $amount): array
    {
        $response = $this->retrieveApiResponse($amount);
        return $this->formatProviderResponse($response);
    }

}