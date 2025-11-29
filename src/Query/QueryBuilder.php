<?php

namespace Jcf\Auvo\Query;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Jcf\Auvo\Exceptions\AuthenticationException;
use Jcf\Auvo\Exceptions\AuvoException;
use Jcf\Auvo\Exceptions\NotFoundException;
use Jcf\Auvo\Exceptions\RateLimitException;
use Jcf\Auvo\Exceptions\ValidationException;
use Jcf\Auvo\Http\AuvoResponse;

abstract class QueryBuilder
{
    protected array $filters = []; // Filtros que vão no paramFilter (startDate, endDate, type, etc)

    protected array $params = []; // Parâmetros diretos (page, pageSize, selectfields, etc)

    protected array $data = [];

    protected ?string $resourceId = null;

    protected bool $logRequests = false;

    public function __construct(
        protected PendingRequest $http,
        protected string $endpoint,
    ) {}

    /**
     * Adiciona um filtro (vai no paramFilter JSON).
     *
     * @param  string  $key  Chave do filtro
     * @param  mixed  $value  Valor do filtro
     */
    public function where(string $key, mixed $value): static
    {
        $this->filters[$key] = $value;

        return $this;
    }

    /**
     * Adiciona um parâmetro direto de query string.
     *
     * @param  string  $key  Chave do parâmetro
     * @param  mixed  $value  Valor do parâmetro
     */
    public function param(string $key, mixed $value): static
    {
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * Define o ID do recurso específico.
     *
     * @param  string|int  $id  ID do recurso
     */
    public function find(string|int $id): static
    {
        $this->resourceId = (string) $id;

        return $this;
    }

    /**
     * Define múltiplos parâmetros de uma vez.
     *
     * @param  array<string, mixed>  $params  Parâmetros a serem definidos
     */
    public function setParams(array $params): static
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    /**
     * Define os dados para requisições POST/PUT.
     *
     * @param  array<string, mixed>  $data  Dados a serem definidos
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Executa uma requisição GET.
     *
     * @return AuvoResponse|array<string, mixed> AuvoResponse se a resposta contém entityList, array caso contrário
     *
     * @throws AuvoException
     */
    public function get(): AuvoResponse|array
    {
        $uri = $this->buildUri();

        // Monta os parâmetros da requisição
        $requestParams = $this->params;

        // Se houver filtros, adiciona como paramFilter JSON
        if (! empty($this->filters)) {
            $requestParams['paramFilter'] = json_encode($this->filters);
        }

        try {
            $response = $this->http->get($uri, $requestParams);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return $this->handleHttpException($e);
        }

        $data = $this->handleResponse($response);

        // Se a resposta contém entityList, retorna AuvoResponse
        if (isset($data['result']['entityList'])) {
            return new AuvoResponse($data);
        }

        // Caso contrário, retorna array (para respostas de item único)
        return $data;
    }

    /**
     * Executa uma requisição POST.
     *
     * @param  array<string, mixed>  $data  Dados para a requisição
     * @return array<string, mixed> Resposta da API
     *
     * @throws AuvoException
     */
    public function create(array $data = []): array
    {
        $uri = $this->buildUri();
        $payload = ! empty($data) ? $data : $this->data;

        try {
            $response = $this->http->post($uri, $payload);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return $this->handleHttpException($e);
        }

        return $this->handleResponse($response);
    }

    /**
     * Executa uma requisição PATCH.
     *
     * @param  array<string, mixed>  $data  Dados para a requisição
     * @return array<string, mixed> Resposta da API
     *
     * @throws AuvoException
     */
    public function update(array $data = []): array
    {
        if (! $this->resourceId) {
            throw new AuvoException(
                'É necessário informar o ID do recurso para atualização.',
            );
        }

        $uri = $this->buildUri();
        $payload = ! empty($data) ? $data : $this->data;

        try {
            $response = $this->http->patch($uri, $payload);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return $this->handleHttpException($e);
        }

        return $this->handleResponse($response);
    }

    /**
     * Executa uma requisição DELETE.
     *
     * @param  array<string, mixed>  $data  Dados para a requisição
     * @return array<string, mixed> Resposta da API
     *
     * @throws AuvoException
     */
    public function delete(array $data = []): array
    {
        if (! $this->resourceId) {
            throw new AuvoException(
                'É necessário informar o ID do recurso para exclusão.',
            );
        }

        $uri = $this->buildUri();
        $payload = ! empty($data) ? $data : $this->data;

        try {
            $response = $this->http->delete($uri, $payload);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return $this->handleHttpException($e);
        }

        return $this->handleResponse($response);
    }

    /**
     * Constrói a URI da requisição.
     *
     * @return string URI completa
     */
    protected function buildUri(): string
    {
        $uri = $this->endpoint;

        if ($this->resourceId) {
            $uri .= '/'.$this->resourceId;
        }

        return $uri;
    }

    /**
     * Trata exceções HTTP do Laravel e converte para exceções específicas.
     *
     * @param  \Illuminate\Http\Client\RequestException  $e  Exceção HTTP do Laravel
     *
     * @throws AuthenticationException|NotFoundException|ValidationException|RateLimitException|AuvoException
     */
    protected function handleHttpException(
        \Illuminate\Http\Client\RequestException $e,
    ): never {
        $response = $e->response;
        $status = $response ? $response->status() : 0;
        $body = $response ? $response->body() : $e->getMessage();
        $uri = $this->buildUri();

        if ($this->logRequests) {
            Log::error('Auvo Query Request Failed', [
                'endpoint' => $uri,
                'status' => $status,
                'body' => $body,
                'exception' => $e->getMessage(),
            ]);
        }

        // Verifica se é rate limit (403 com mensagem específica)
        if ($status === 403 && str_contains($body, 'Rate limit')) {
            throw new RateLimitException(
                "Rate limit excedido na API Auvo. Limite: 400 requisições por minuto. Status: {$status}. Resposta: {$body}",
                $status,
            );
        }

        match ($status) {
            401, 403 => throw new AuthenticationException(
                "Erro de autenticação na API Auvo. Status: {$status}. Resposta: {$body}",
                $status,
            ),
            404 => throw new NotFoundException(
                "Recurso não encontrado na API Auvo. Status: {$status}. Resposta: {$body}",
                $status,
            ),
            400, 422 => throw new ValidationException(
                "Erro de validação na API Auvo. Status: {$status}. Resposta: {$body}",
                $status,
            ),
            default => throw new AuvoException(
                "Erro na requisição: {$body}. Status: {$status}.",
                $status,
            ),
        };
    }

    /**
     * Trata a resposta da requisição.
     *
     * @param  \Illuminate\Http\Client\Response  $response  Resposta HTTP
     * @return array<string, mixed> Dados da resposta
     *
     * @throws AuvoException|AuthenticationException|NotFoundException|ValidationException|RateLimitException
     */
    protected function handleResponse($response): array
    {
        if ($response->failed()) {
            $status = $response->status();
            $body = $response->body();
            $uri = $this->buildUri();

            if ($this->logRequests) {
                Log::error('Auvo Query Request Failed', [
                    'endpoint' => $uri,
                    'status' => $status,
                    'body' => $body,
                ]);
            }

            // Verifica se é rate limit (403 com mensagem específica)
            if ($status === 403 && str_contains($body, 'Rate limit')) {
                throw new RateLimitException(
                    "Rate limit excedido na API Auvo. Limite: 400 requisições por minuto. Status: {$status}. Resposta: {$body}",
                    $status,
                );
            }

            match ($status) {
                401, 403 => throw new AuthenticationException(
                    "Erro de autenticação na API Auvo. Status: {$status}. Resposta: {$body}",
                    $status,
                ),
                404 => throw new NotFoundException(
                    "Recurso não encontrado na API Auvo. Status: {$status}. Resposta: {$body}",
                    $status,
                ),
                400, 422 => throw new ValidationException(
                    "Erro de validação na API Auvo. Status: {$status}. Resposta: {$body}",
                    $status,
                ),
                default => throw new AuvoException(
                    "Erro na requisição: {$body}. Status: {$status}.",
                    $status,
                ),
            };
        }

        return $response->json() ?? [];
    }

    /**
     * Busca uma única entidade pelo ID e retorna como Collection.
     *
     * @param  string|int  $id  ID da entidade
     * @return Collection Entidade encontrada
     *
     * @throws AuvoException
     */
    public function first(string|int $id): Collection
    {
        $this->find($id);
        $response = $this->get();

        // Se é um array, extrai do 'result' se necessário
        if (is_array($response)) {
            return collect($response['result'] ?? $response);
        }

        // Se é AuvoResponse, usa entityList e pega o primeiro item
        return $response->entityList()->first()
            ? collect($response->entityList()->first())
            : collect();
    }

    /**
     * Retorna um objeto para buscar todas as páginas.
     */
    public function allPages(): AllPagesQuery
    {
        return new AllPagesQuery($this);
    }

    /**
     * Busca todas as páginas automaticamente e retorna uma Collection única.
     *
     * @return Collection Todos os resultados combinados
     *
     * @throws AuvoException
     */
    public function getAll(): Collection
    {
        return $this->allPages()->get();
    }
}
