<?php

namespace App\Services\Credit;
interface CreditProviderInterface
{
    public function getRates(int $amount): array;
}