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
     * Itera página a página, invocando o callback com as entidades de cada página.
     *
     * @param  callable(Collection, int): void  $callback
     * @return int Total de entidades processadas
     *
     * @throws AuvoException
     */
    public function eachPage(callable $callback): int
    {
        $totalProcessed = 0;
        $page = 1;

        while (true) {
            $pageResult = $this->fetchPage($page);

            if ($pageResult === null) {
                break;
            }

            ['entities' => $entities, 'response' => $response] = $pageResult;

            $callback($entities, $page);
            $totalProcessed += $entities->count();

            if (! $this->shouldFetchNextPage($response, $entities->count())) {
                break;
            }

            $page++;
        }

        return $totalProcessed;
    }

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

        $this->eachPage(function (Collection $entities) use (&$allResults): void {
            $allResults = $allResults->merge($entities);
        });

        return $allResults;
    }

    /**
     * @return array{entities: Collection, response: AuvoResponse}|null
     */
    private function fetchPage(int $page): ?array
    {
        $queryBuilder = clone $this->queryBuilder;

        $response = $queryBuilder->page($page)->pageSize(self::PAGE_SIZE)->get();

        if (! $response instanceof AuvoResponse) {
            return null;
        }

        $entities = $response->entityList();

        if ($entities->isEmpty()) {
            return null;
        }

        return ['entities' => $entities, 'response' => $response];
    }

    /**
     * Determina se deve buscar a próxima página.
     *
     * Alguns endpoints da Auvo (ex.: /tasktypes) retornam totalItems=0 mesmo
     * com resultados paginados; nesses casos, continua enquanto a página atual
     * estiver cheia ou houver link HATEOAS nextPage.
     */
    protected function shouldFetchNextPage(AuvoResponse $response, int $entityCount): bool
    {
        if ($response->hasMorePages()) {
            return true;
        }

        if ($response->hasNextPageLink()) {
            return true;
        }

        // Fallback: alguns endpoints (ex.: /tasktypes) retornam totalItems=0.
        if ($response->totalItems() === 0 && $entityCount >= self::PAGE_SIZE) {
            return true;
        }

        return false;
    }
}
