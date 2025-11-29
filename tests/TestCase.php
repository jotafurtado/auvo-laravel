<?php

namespace Jcf\Auvo\Tests;

use Jcf\Auvo\Providers\AuvoServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            AuvoServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('auvo.api_key', 'test-api-key');
        $app['config']->set('auvo.api_token', 'test-api-token');
        $app['config']->set('auvo.base_uri', 'https://api.test.com/v2');
    }
}
