<?php

use DI\ContainerBuilder;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Services\HttpRequestService;
use App\Services\Credit\CreditProviderFactory;
use App\Services\Credit\CreditProviderService;
use Psr\Log\LoggerInterface;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    'providersApiSettings' => [
        'ingdiba' =>[
            'apiUrl' => "https://api.jsonbin.io/v3/b/65a6e50e266cfc3fde79aa14?meta=false",
            'xAccessToken' => "$2a$10\$NH1p52EaThQFAUbsMloZ.ObhsAsdBC77RJROzFiJ7OUc52oBIn5DS"
        ],
        'smava' =>[
            'apiUrl' => "https://api.jsonbin.io/v3/b/65a6e71e1f5677401f1ebd2c?meta=false",
            'xAccessToken' => "$2a$10\$NH1p52EaThQFAUbsMloZ.ObhsAsdBC77RJROzFiJ7OUc52oBIn5DS",
        ],
    ],
    'providers' => 'ingdiba,smava',
    // Bind LoggerInterface to concrete Logger implementation
    LoggerInterface::class => function() {
        $logger = new Logger('error_logger');
        $logger->pushHandler(new StreamHandler('error.log', Logger::ERROR));
        return $logger;
    },
    HttpRequestService::class => \DI\create(),
    CreditProviderFactory::class => \DI\autowire()
        ->constructorParameter('providers', DI\get('providers'))
        ->constructorParameter('apiSettings', DI\get('providersApiSettings')),
    CreditProviderService::class => \DI\autowire()
]);



return $containerBuilder->build();
