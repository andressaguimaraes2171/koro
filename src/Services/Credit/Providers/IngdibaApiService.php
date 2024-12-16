<?php

namespace App\Services\Credit\Providers;

use App\Config\ProviderConfig;
use App\Services\Credit\CreditProviderInterface;
use App\Services\HttpRequestService;
use App\Errors\Credit\Provider\ApiRequestException;
use App\Errors\Credit\Provider\ApiResponseFormatException;
use App\Errors\Credit\Provider\InvalidApiResponseException;
use Exception;
use GuzzleHttp\Psr7\Request;
use JsonException;
use Psr\Http\Message\ResponseInterface;
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
    private function retrieveApiResponse(int $amount): ResponseInterface
    {
        $url = $this->providerApiSettings->getApiUrl() . '&amount=' . $amount;
        $headers = [
            'X-Access-key' => $this->providerApiSettings->getXAccessToken(),
        ];
        $request = new Request('GET', $url, $headers);
        try {
            return $this->httpService->send($request);
        } catch (Exception $e) {
            throw new ApiRequestException("Failed to retrieve ingdiba API response for URL: {$url}", 0, $e);
        }
    }
    private function apiResponseToArray(ResponseInterface $response): array
    {
        try {
            return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ApiResponseFormatException("Failed to parse ingdiba API response to array: {$response->getBody()}", 0, $e);
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
        if (!isset($response['zinsen']) || !isset($response['duration'])) {
            throw new InvalidApiResponseException("Response is missing a valid zinsen or duration key", 0);
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