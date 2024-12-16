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
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use function DI\autowire;
use function DI\create;
use function DI\get;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    \Dotenv\Dotenv::class => function() {
        return Dotenv::createImmutable(__DIR__ . '/../../');
    },
    Environment::class => create(Environment::class)
        ->constructor(get(Dotenv::class)),
    ProvidersConfig::class => autowire(),
    // Bind LoggerInterface to concrete Logger implementation
    LoggerInterface::class => function(ContainerInterface $container) {
        $environment = $container->get(Environment::class);
        $logger = new Logger('app_logger');

        if ($environment->get('APP_ENV') === 'production') {
            $logger->pushHandler(new RotatingFileHandler('logs/error.log', 30, LogLevel::ERROR));
            $logger->pushHandler(new RotatingFileHandler('logs/app.log', 30, LogLevel::INFO));
        } else {
            $logger->pushHandler(new StreamHandler('php://stdout', LogLevel::DEBUG));
        }

        return $logger;
    },
    HttpRequestService::class => create(),
    CreditProviderFactory::class => autowire()
        ->constructorParameter('providersConfig', get(ProvidersConfig::class)),
    CreditProviderService::class => autowire()
]);

return $containerBuilder->build();
