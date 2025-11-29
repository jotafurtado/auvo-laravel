<?php

namespace Jcf\Auvo\Http;

use Illuminate\Support\Collection;

/**
 * Encapsula a resposta padronizada da API Auvo.
 *
 * A API Auvo retorna uma estrutura padronizada para requisições GET com listagem:
 * {
 *   "result": {
 *     "entityList": [...],
 *     "pagedSearchReturnData": {
 *       "order": 0,
 *       "pageSize": 10,
 *       "page": 1,
 *       "totalItems": 2
 *     },
 *     "links": [...]
 *   }
 * }
 */
class AuvoResponse
{
    protected Collection $data;

    public function __construct(array $data)
    {
        $this->data = collect($data);
    }

    /**
     * Retorna a lista de entidades como Collection.
     */
    public function entityList(): Collection
    {
        return collect(
            collect($this->data->get('result', []))
                ->get('entityList', [])
        );
    }

    /**
     * Retorna os dados de paginação como Collection.
     */
    public function pagedSearchReturnData(): Collection
    {
        return collect(
            collect($this->data->get('result', []))
                ->get('pagedSearchReturnData', [])
        );
    }

    /**
     * Retorna os links HATEOAS como Collection.
     */
    public function links(): Collection
    {
        return collect(
            collect($this->data->get('result', []))
                ->get('links', [])
        );
    }

    /**
     * Retorna o total de itens disponíveis.
     */
    public function totalItems(): int
    {
        return (int) $this->pagedSearchReturnData()->get('totalItems', 0);
    }

    /**
     * Retorna a página atual.
     */
    public function currentPage(): int
    {
        return (int) $this->pagedSearchReturnData()->get('page', 1);
    }

    /**
     * Retorna o tamanho da página.
     */
    public function pageSize(): int
    {
        return (int) $this->pagedSearchReturnData()->get('pageSize', 0);
    }

    /**
     * Verifica se há mais páginas disponíveis.
     */
    public function hasMorePages(): bool
    {
        $totalItems = $this->totalItems();
        $currentPage = $this->currentPage();
        $pageSize = $this->pageSize();

        if ($pageSize === 0) {
            return false;
        }

        return ($currentPage * $pageSize) < $totalItems;
    }

    /**
     * Retorna os dados brutos como Collection.
     */
    public function raw(): Collection
    {
        return $this->data;
    }

    /**
     * Delega métodos não encontrados para a Collection de entityList.
     *
     * Permite usar métodos de Collection diretamente no objeto AuvoResponse.
     * Exemplo: $response->map(...), $response->filter(...), etc.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->entityList()->{$method}(...$parameters);
    }
}
