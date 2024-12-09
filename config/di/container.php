<?php

use App\Config\Environment;
use App\Config\ProvidersConfig;
use App\Factories\Credit\CreditProviderFactory;
use App\Services\Credit\CreditProviderService;
use App\Services\HttpRequestService;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use function DI\autowire;
use function DI\create;
use function DI\get;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    \Dotenv\Dotenv::class => function() {
        return Dotenv::createImmutable(__DIR__ . '/../../');
    },
    Environment::class => create(Environment::class)
        ->constructor(get(\Dotenv\Dotenv::class)),
    ProvidersConfig::class => autowire(),
    // Bind LoggerInterface to concrete Logger implementation
    LoggerInterface::class => function() {
        $logger = new Logger('error_logger');
        $logger->pushHandler(new StreamHandler('error.log', Logger::ERROR));
        return $logger;
    },
    HttpRequestService::class => create(),
    CreditProviderFactory::class => autowire()
        ->constructorParameter('providersConfig', get(ProvidersConfig::class)),
    CreditProviderService::class => autowire()
]);

return $containerBuilder->build();
