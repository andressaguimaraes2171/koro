<?php

namespace App\Services\Credit;
interface CreditProviderInterface
{
    public function getRates(float $amount): array;
}