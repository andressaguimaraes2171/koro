<?php

namespace App\Services\Credit\Providers;

use App\Services\Credit\CreditProviderInterface;
use App\Services\HttpRequestService;
use Psr\Log\LoggerInterface;

class IngdibaApiService implements CreditProviderInterface
{
    private HttpRequestService $httpService;
    private LoggerInterface $logger;
    private string $apiUrl;
    private string $xAccessToken;
    private string $providerName = 'ingdiba';
    public function __construct(HttpRequestService $httpService, LoggerInterface $logger)
    {
        $this->httpService = $httpService;
        $this->logger = $logger;
        $this->apiUrl = $_ENV['API_URL_INGDIBA'];
        $this->xAccessToken = $_ENV['X_ACCESS_KEY_INGDIBA'];
    }
    public function retrieveApiResponse(int $amount)
    {
        $url = $this->apiUrl . '&amount=' . $amount;
        $response = $this->httpService->get($url, [
            'X-Access-key' => $this->xAccessToken,
        ]);
        return $response;
    }

    public function formatProviderResponse(string $response): array
    {
        try {
            $decodedResponse = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            return $this->validateResponse($decodedResponse);

        } catch (\JsonException $e) {
            $this->logger->error('JSON parsing error: ' . $e->getMessage(), [
                'response' => $response,
                'exception' => $e
            ]);
            return [];

        } catch (\Exception $e) {
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
            $offer = [];
            if (!array_key_exists('zinsen', $response) || !array_key_exists('duration', $response)) {
                throw new \Exception('Invalid response format: missing required fields');
            }

            $offer['rate'] = $response['zinsen'];
            //convert months to years
            $offer['duration'] = $response['duration'] / 12;
            $offer['provider'] = $this->providerName;
            return $offer;

        } catch (\Exception $e) {
            $this->logger->error('Invalid response format: ' . $e->getMessage(), [
                'response' => $response,
                'exception' => $e
            ]);
            return [];

        }
    }

    public function getRates(int $amount): array
    {
        $response = $this->retrieveApiResponse($amount);
        return $this->formatProviderResponse($response);
    }
}