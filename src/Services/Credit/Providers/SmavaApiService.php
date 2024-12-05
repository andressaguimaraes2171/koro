<?php

namespace App\Services\Credit\Providers;

use App\Services\Credit\CreditProviderInterface;
use App\Services\HttpRequestService;
use Psr\Log\LoggerInterface;
use Exception;
use JsonException;

class SmavaApiService implements CreditProviderInterface
{
    private HttpRequestService $httpService;
    private LoggerInterface $logger;
    private string $apiUrl;
    private string $xAccessToken;
    private string $providerName = 'smava';
    public function __construct(HttpRequestService $httpService, LoggerInterface $logger, array $apiSettings)
    {
        $this->httpService = $httpService;
        $this->logger = $logger;
        $this->apiUrl = $apiSettings['apiUrl'];
        $this->xAccessToken = $apiSettings['xAccessToken'];
    }

    private function retrieveApiResponse(int $amount)
    {
        $url = $this->apiUrl . '&amount=' . $amount;
        return $this->httpService->get($url, [
            'X-Access-key' => $this->xAccessToken,
        ]);
    }

    private function formatProviderResponse(string $response): array
    {
        try {
            $decodedResponse = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            $validatedResponse = $this->validateResponse($decodedResponse);

            $duration = ((int)($validatedResponse['Terms']['Duration'])) * 12;

            $offer['rate'] = str_replace(',', '.', $validatedResponse['Interest']);
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
            if (!array_key_exists('Interest', $response) || !array_key_exists('Duration', $response['Terms'])) {
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

    public function getRates(int $amount): array
    {
        $response = $this->retrieveApiResponse($amount);
        return $this->formatProviderResponse($response);
    }
}