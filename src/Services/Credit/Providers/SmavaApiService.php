<?php

namespace App\Services\Credit\Providers;

use App\Config\ProviderConfig;
use App\Services\Credit\CreditProviderInterface;
use App\Services\HttpRequestService;
use App\Errors\Credit\Provider\ApiRequestException;
use App\Errors\Credit\Provider\ApiResponseFormatException;
use App\Errors\Credit\Provider\InvalidApiResponseException;
use Exception;
use JsonException;
use Psr\Log\LoggerInterface;

class SmavaApiService implements CreditProviderInterface
{
    private HttpRequestService $httpService;
    private LoggerInterface $logger;

    private ProviderConfig $providerApiSettings;

    private string $providerName = 'smava';

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
            throw new ApiRequestException("Failed to retrieve smava API response for URL: {$url}", 0, $e);
        }
    }

    private function apiResponseToArray(string $response): array
    {
        try {
            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ApiResponseFormatException("Failed to parse smava API response to array: {$apiResponse}", 0, $e);
        }
    }

    private function formatProviderResponse(array $response): array
    {

        $duration = ((int)($response['Terms']['Duration'])) * 12;

        $offer['rate'] = str_replace(',', '.', $response['Interest']);
        $offer['duration'] = $duration > 1 ? $duration . ' months' : $duration . ' month';
        $offer['provider'] = $this->providerName;

        return $offer;

    }

    private function isValidResponse(?array $response): bool
    {
        if (!isset($response['Interest']) || !isset($response['Terms']['Duration'])) {
            throw new InvalidApiResponseException("Response is missing a valid [Interest] or [Terms][Duration] key", 0);
        }

        return true;
    }

    public function getRates(float $amount): array
    {
        try {
            $apiResponse = $this->retrieveApiResponse($amount);
            $response = $this->apiResponseToArray($apiResponse);

            if ($this->isValidResponse($response)) {
                return $this->formatProviderResponse($response);
            }
        } catch (ApiRequestException | ApiResponseFormatException | InvalidApiResponseException | Exception $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => get_class($e),
                'stack' => $e->getTraceAsString(),
            ]);
        }

        return [];
    }
}