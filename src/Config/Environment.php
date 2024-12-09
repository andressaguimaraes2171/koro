<?php

namespace App\Config;

use Dotenv\Dotenv;
class Environment
{
    private array $config;

    public function __construct(Dotenv $dotenv)
    {
        $dotenv->load();
        $this->config = $_ENV;
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->config;
    }
}
