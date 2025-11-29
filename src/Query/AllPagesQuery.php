<?php

namespace Jcf\Auvo\Query;

use Illuminate\Support\Collection;
use Jcf\Auvo\Exceptions\AuvoException;
use Jcf\Auvo\Http\AuvoResponse;

class AllPagesQuery
{
    protected const PAGE_SIZE = 100;

    public function __construct(
        protected QueryBuilder $queryBuilder,
    ) {}

    /**
     * Busca todas as páginas automaticamente e retorna uma Collection única.
     *
     * @return Collection Todos os resultados combinados
     *
     * @throws AuvoException
     */
    public function get(): Collection
    {
        $allResults = collect();
        $page = 1;

        while (true) {
            // Cria uma cópia do QueryBuilder para não modificar o estado original
            $queryBuilder = clone $this->queryBuilder;

            // Busca a página atual
            $response = $queryBuilder->page($page)->pageSize(self::PAGE_SIZE)->get();

            // Se não é AuvoResponse, retorna vazio
            if (! $response instanceof AuvoResponse) {
                break;
            }

            // Extrai as entidades da página atual
            $entities = $response->entityList();

            // Se não há entidades, termina a iteração
            if ($entities->isEmpty()) {
                break;
            }

            // Adiciona as entidades ao resultado total
            $allResults = $allResults->merge($entities);

            // Verifica se há mais páginas
            if (! $response->hasMorePages()) {
                break;
            }

            $page++;
        }

        return $allResults;
    }
}
