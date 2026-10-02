<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Force the test suite onto an isolated in-memory database even when the
     * Docker app container exports MySQL environment variables.
     */
    public function createApplication()
    {
        $isolatedEnvironment = [
            'APP_ENV' => 'testing',
            'APP_DEBUG' => 'true',
            'CACHE_STORE' => 'array',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_URL' => '',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
            'OPENAI_API_KEY' => '',
            'WEATHER_API_KEY' => '',
        ];

        foreach ($isolatedEnvironment as $name => $value) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $_SERVER[$name] = $value;
        }

        return parent::createApplication();
    }
}
