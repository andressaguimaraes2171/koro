<?php
require __DIR__ . '/vendor/autoload.php';

use App\Services\Credit\CreditProviderService;
$container = require __DIR__ . '/config/di/container.php';

$creditService = $container->get(CreditProviderService::class);

if (@$_GET['submit']) {
    $offers = $creditService->retrieveCreditRates($_GET['amount']);
}

include(__DIR__ .'/view.phtml');