<?php

namespace App\Services\Credit;
interface CreditProviderInterface
{
    public function retrieveApiResponse(int $amount);

    public function formatProviderResponse(string $response): array;
}