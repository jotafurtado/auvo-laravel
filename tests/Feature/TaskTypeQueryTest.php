<?php

namespace Jcf\Auvo\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Jcf\Auvo\Exceptions\AuthenticationException;
use Jcf\Auvo\Exceptions\RateLimitException;
use Jcf\Auvo\Facades\Auvo;
use Jcf\Auvo\Query\TaskTypeQuery;
use Jcf\Auvo\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class TaskTypeQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->forgetInstance('auvo');
        $this->app->forgetInstance('auvo.auth');

        Http::preventStrayRequests();
    }

    private function fakeLogin(): void
    {
        Http::fake([
            'api.test.com/v2/login/' => Http::response([
                'result' => [
                    'authenticated' => true,
                    'accessToken' => 'test-access-token',
                    'created' => Carbon::now()->format('Y-m-d H:i:s'),
                    'expiration' => Carbon::now()->addMinutes(30)->format('Y-m-d H:i:s'),
                    'message' => 'OK',
                ],
            ], 200),
        ]);
    }

    /**
     * Feature: 015-auvo-task-types-resource, Property 1: Agregação completa de páginas em getAll()
     */
    #[DataProvider('getAllAggregationProvider')]
    public function test_property_get_all_aggregates_all_pages(int $totalItems): void
    {
        Http::preventStrayRequests();

        $this->app->forgetInstance('auvo');
        $this->app->forgetInstance('auvo.auth');

        $allItems = [];

        for ($index = 0; $index < $totalItems; $index++) {
            $allItems[] = [
                'id' => $index + 1,
                'description' => 'Task Type '.($index + 1),
            ];
        }

        $pages = $totalItems === 0 ? [[]] : array_chunk($allItems, 100);
        $pagesByNumber = [];

        foreach ($pages as $pageIndex => $pageItems) {
            $pagesByNumber[$pageIndex + 1] = $pageItems;
        }

        $fakeResponses = $this->fakeLoginResponses();

        if ($totalItems === 0) {
            $fakeResponses['api.test.com/v2/tasktypes*'] = Http::response([
                'result' => [
                    'entityList' => [],
                    'pagedSearchReturnData' => [
                        'page' => 1,
                        'pageSize' => 100,
                        'totalItems' => 0,
                    ],
                ],
            ], 200);
        } else {
            foreach ($pagesByNumber as $page => $pageItems) {
                $fakeResponses["api.test.com/v2/tasktypes*page={$page}*"] = Http::response([
                    'result' => [
                        'entityList' => $pageItems,
                        'pagedSearchReturnData' => [
                            'page' => $page,
                            'pageSize' => 100,
                            'totalItems' => $totalItems,
                        ],
                    ],
                ], 200);
            }
        }

        Http::fake($fakeResponses);

        $result = Auvo::taskTypes()->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount($totalItems, $result);
        $this->assertSame(
            collect($allItems)->pluck('id')->all(),
            $result->pluck('id')->all(),
        );
    }

    /**
     * @return array<string, array{int}>
     */
    public static function getAllAggregationProvider(): array
    {
        $cases = [];

        for ($iteration = 0; $iteration < 100; $iteration++) {
            $cases["iteration_{$iteration}"] = [random_int(0, 250)];
        }

        return $cases;
    }

    public function test_task_type_query_uses_tasktypes_endpoint_with_pagination(): void
    {
        Http::fake(array_merge($this->fakeLoginResponses(), [
            'api.test.com/v2/tasktypes*' => Http::response([
                'result' => [
                    'entityList' => [
                        ['id' => 10, 'description' => 'Visita Técnica'],
                    ],
                    'pagedSearchReturnData' => [
                        'page' => 1,
                        'pageSize' => 100,
                        'totalItems' => 1,
                    ],
                ],
            ], 200),
        ]));

        $result = Auvo::taskTypes()->getAll();

        $this->assertCount(1, $result);
        $this->assertSame('Visita Técnica', $result->first()['description']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/tasktypes')
                && ($request->data()['page'] ?? null) === 1
                && ($request->data()['pageSize'] ?? null) === 100;
        });
    }

    public function test_get_all_continues_when_api_returns_zero_total_items_with_full_page(): void
    {
        $pageOneItems = [];

        for ($index = 1; $index <= 100; $index++) {
            $pageOneItems[] = [
                'id' => $index,
                'description' => 'Task Type '.$index,
            ];
        }

        Http::fake(array_merge($this->fakeLoginResponses(), [
            'api.test.com/v2/tasktypes*page=1*' => Http::response([
                'result' => [
                    'entityList' => $pageOneItems,
                    'pagedSearchReturnData' => [
                        'page' => 1,
                        'pageSize' => 100,
                        'totalItems' => 0,
                    ],
                    'links' => [
                        ['href' => 'https://api.test.com/v2/tasktypes?page=1', 'rel' => 'self', 'method' => 'GET'],
                    ],
                ],
            ], 200),
            'api.test.com/v2/tasktypes*page=2*' => Http::response([
                'result' => [
                    'entityList' => [
                        ['id' => 101, 'description' => 'Task Type 101'],
                    ],
                    'pagedSearchReturnData' => [
                        'page' => 2,
                        'pageSize' => 100,
                        'totalItems' => 0,
                    ],
                    'links' => [
                        ['href' => 'https://api.test.com/v2/tasktypes?page=2', 'rel' => 'self', 'method' => 'GET'],
                    ],
                ],
            ], 200),
        ]));

        $result = Auvo::taskTypes()->getAll();

        $this->assertCount(101, $result);
        $this->assertSame(101, $result->last()['id']);
    }

    public function test_get_all_returns_empty_collection_when_api_returns_no_items(): void
    {
        Http::fake(array_merge($this->fakeLoginResponses(), [
            'api.test.com/v2/tasktypes*' => Http::response([
                'result' => [
                    'entityList' => [],
                    'pagedSearchReturnData' => [
                        'page' => 1,
                        'pageSize' => 100,
                        'totalItems' => 0,
                    ],
                ],
            ], 200),
        ]));

        $result = Auvo::taskTypes()->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_authentication_error_throws_authentication_exception(): void
    {
        Http::fake(array_merge($this->fakeLoginResponses(), [
            'api.test.com/v2/tasktypes*' => Http::response('Unauthorized', 401),
        ]));

        $this->expectException(AuthenticationException::class);

        Auvo::taskTypes()->getAll();
    }

    public function test_rate_limit_error_throws_rate_limit_exception(): void
    {
        Http::fake(array_merge($this->fakeLoginResponses(), [
            'api.test.com/v2/tasktypes*' => Http::response('Rate limit temporarily exceeded', 403),
        ]));

        $this->expectException(RateLimitException::class);

        Auvo::taskTypes()->getAll();
    }

    public function test_client_task_types_returns_task_type_query(): void
    {
        Http::fake($this->fakeLoginResponses());

        $taskTypesQuery = Auvo::taskTypes();

        $this->assertInstanceOf(TaskTypeQuery::class, $taskTypesQuery);
    }

    public function test_task_type_query_supports_id_and_description_filters(): void
    {
        Http::fake($this->fakeLoginResponses());

        $query = Auvo::taskTypes()->id(42)->description('Manutenção');

        $reflection = new \ReflectionClass($query);
        $filtersProperty = $reflection->getProperty('filters');
        $filtersProperty->setAccessible(true);
        $filters = $filtersProperty->getValue($query);

        $this->assertSame(42, $filters['id']);
        $this->assertSame('Manutenção', $filters['description']);
    }

    public function test_task_type_query_page_and_page_size_use_params(): void
    {
        Http::fake($this->fakeLoginResponses());

        $query = Auvo::taskTypes()->page(3)->pageSize(25);

        $reflection = new \ReflectionClass($query);
        $paramsProperty = $reflection->getProperty('params');
        $paramsProperty->setAccessible(true);
        $params = $paramsProperty->getValue($query);

        $this->assertSame(3, $params['page']);
        $this->assertSame(25, $params['pageSize']);
    }

    /**
     * @return array<string, mixed>
     */
    private function fakeLoginResponses(): array
    {
        return [
            'api.test.com/v2/login/' => Http::response([
                'result' => [
                    'authenticated' => true,
                    'accessToken' => 'test-access-token',
                    'created' => Carbon::now()->format('Y-m-d H:i:s'),
                    'expiration' => Carbon::now()->addMinutes(30)->format('Y-m-d H:i:s'),
                    'message' => 'OK',
                ],
            ], 200),
        ];
    }
}
