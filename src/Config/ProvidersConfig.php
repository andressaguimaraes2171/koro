<?php

namespace App\Config;

class ProvidersConfig
{
    private Environment $environment;
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }
    private array $providers = [];

    public function addProvider(string $name, ProviderConfig $config): void
    {
        $this->providers[$name] = $config;
    }

    public function getProvider(string $name): ProviderConfig
    {
        return $this->providers[$name];
    }

    public function getEnabledProviders(): array
    {
        return array_keys($this->providers);
    }

    public function all() : array
    {
        if (empty($this->providers)) {
            $this->loadProvidersFromEnvironment();
        }
        return $this->providers;
    }

    private function loadProvidersFromEnvironment(): void
    {
        $activeProviders = $this->environment->get('ACTIVE_PROVIDERS');
        $providers = explode(',', $activeProviders);
        foreach ($providers as $providerName) {
            $this->addProvider(
                $providerName,
                new ProviderConfig(
                    $this->environment->get('API_URL_' . strtoupper($providerName)),
                    $this->environment->get('X_ACCESS_KEY_' . strtoupper($providerName))
                )
            );
        }
    }
}