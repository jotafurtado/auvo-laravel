<?php

namespace Jcf\Auvo\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Jcf\Auvo\Auth\AuthManager;
use Jcf\Auvo\Exceptions\AuvoException;
use Jcf\Auvo\Query\CustomerQuery;
use Jcf\Auvo\Query\TaskQuery;
use Jcf\Auvo\Query\TeamQuery;
use Jcf\Auvo\Query\UserQuery;

class Client
{
    protected PendingRequest $http;

    public function __construct(
        protected AuthManager $authManager,
        protected string $baseUri,
        protected int $timeout = 30,
        protected int $retry = 3,
        protected int $retryDelay = 100,
        protected bool $logRequests = false,
    ) {
        $this->refreshHttpClient();
    }

    /**
     * Atualiza o cliente HTTP com o token de autenticação válido.
     */
    protected function refreshHttpClient(): void
    {
        try {
            $accessToken = $this->authManager->getAccessToken();

            $this->http = Http::baseUrl($this->baseUri)
                ->timeout($this->timeout)
                ->retry($this->retry, $this->retryDelay)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$accessToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]);
        } catch (\Exception $e) {
            throw new AuvoException(
                "Erro ao configurar cliente HTTP: {$e->getMessage()}",
                0,
                $e,
            );
        }
    }

    /**
     * Executa uma requisição HTTP REST.
     *
     * @param  string  $method  Método HTTP (GET, POST, PATCH, DELETE)
     * @param  string  $uri  URI do endpoint
     * @param  array  $data  Dados da requisição
     * @return array<string, mixed> Resposta da API
     *
     * @throws AuvoException
     */
    protected function makeRequest(
        string $method,
        string $uri,
        array $data = [],
    ): array {
        if ($this->logRequests) {
            Log::debug('Auvo Request', [
                'method' => $method,
                'uri' => $uri,
                'data' => $data,
            ]);
        }

        // Garante que o token está válido antes de fazer a requisição
        $this->refreshHttpClient();

        $response = match (strtoupper($method)) {
            'GET' => $this->http->get($uri, $data),
            'POST' => $this->http->post($uri, $data),
            'PATCH' => $this->http->patch($uri, $data),
            'DELETE' => $this->http->delete($uri, $data),
            default => throw new AuvoException(
                "Método HTTP '{$method}' não suportado.",
            ),
        };

        // Se receber 401, tenta fazer login novamente (API Auvo não tem refresh token)
        if ($response->status() === 401) {
            try {
                $this->authManager->signIn();
                $this->refreshHttpClient();

                $response = match (strtoupper($method)) {
                    'GET' => $this->http->get($uri, $data),
                    'POST' => $this->http->post($uri, $data),
                    'PATCH' => $this->http->patch($uri, $data),
                    'DELETE' => $this->http->delete($uri, $data),
                    default => throw new AuvoException(
                        "Método HTTP '{$method}' não suportado.",
                    ),
                };
            } catch (\Exception $loginException) {
                if ($this->logRequests) {
                    Log::error('Auvo Request Failed - Auth Error', [
                        'method' => $method,
                        'uri' => $uri,
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'auth_error' => $loginException->getMessage(),
                    ]);
                }

                throw new AuvoException(
                    "Erro na autenticação: {$loginException->getMessage()}",
                    0,
                    $loginException,
                );
            }
        }

        if ($response->failed()) {
            if ($this->logRequests) {
                Log::error('Auvo Request Failed', [
                    'method' => $method,
                    'uri' => $uri,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            throw new AuvoException(
                "Erro na requisição: {$response->body()}. Status: {$response->status()}.",
            );
        }

        return $response->json() ?? [];
    }

    /**
     * Obtém o gerenciador de autenticação.
     */
    public function getAuthManager(): AuthManager
    {
        return $this->authManager;
    }

    /**
     * Retorna a instância HTTP configurada para uso em Query Builders.
     */
    public function getHttpClient(): PendingRequest
    {
        return $this->http;
    }

    /**
     * Retorna uma instância do Query Builder para Users.
     */
    public function users(): UserQuery
    {
        return new UserQuery($this->http);
    }

    /**
     * Retorna uma instância do Query Builder para Tasks.
     */
    public function tasks(): TaskQuery
    {
        return new TaskQuery($this->http);
    }

    /**
     * Retorna uma instância do Query Builder para Customers.
     */
    public function customers(): CustomerQuery
    {
        return new CustomerQuery($this->http);
    }

    /**
     * Retorna uma instância do Query Builder para Teams.
     */
    public function teams(): TeamQuery
    {
        return new TeamQuery($this->http);
    }
}
