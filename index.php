<?php
require __DIR__ . '/vendor/autoload.php';

use App\Services\Credit\CreditProviderService;
use App\Config\Environment;

$container = require __DIR__ . '/config/di/container.php';
$environment = $container->get(Environment::class);

$isProduction = $environment->get('APP_ENV') === 'production';
ini_set('display_errors', $isProduction ? '0' : '1');
ini_set('display_startup_errors', $isProduction ? '0' : '1');
error_reporting($isProduction ? 0 : E_ALL);

$creditService = $container->get(CreditProviderService::class);

if (@$_GET['submit']) {
    $offers = $creditService->retrieveCreditRates($_GET['amount']);
}

include(__DIR__ .'/view.phtml');