<?php

namespace Jcf\Auvo\Providers;

use Illuminate\Support\ServiceProvider;
use Jcf\Auvo\Auth\AuthManager;
use Jcf\Auvo\Http\Client;

class AuvoServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/auvo.php',
            'auvo'
        );

        $this->app->singleton('auvo.auth', function ($app) {
            $config = $app['config']['auvo'];

            if (empty($config['api_key']) || empty($config['api_token'])) {
                throw new \Jcf\Auvo\Exceptions\AuvoException(
                    'As credenciais de autenticação (api_key e api_token) são obrigatórias.',
                );
            }

            return new AuthManager(
                baseUri: $config['base_uri'],
                apiKey: $config['api_key'],
                apiToken: $config['api_token'],
            );
        });

        $this->app->singleton('auvo', function ($app) {
            $config = $app['config']['auvo'];
            $authManager = $app->make('auvo.auth');

            return new Client(
                authManager: $authManager,
                baseUri: $config['base_uri'],
                timeout: $config['timeout'] ?? 30,
                retry: $config['retry'] ?? 3,
                retryDelay: $config['retry_delay'] ?? 100,
                logRequests: $config['log_requests'] ?? false,
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/auvo.php' => config_path('auvo.php'),
        ], 'config');
    }
}
