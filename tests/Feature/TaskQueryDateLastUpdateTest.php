<?php

namespace Jcf\Auvo\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Jcf\Auvo\Facades\Auvo;
use Jcf\Auvo\Tests\TestCase;

class TaskQueryDateLastUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->forgetInstance('auvo');
        $this->app->forgetInstance('auvo.auth');

        Http::preventStrayRequests();
    }

    public function test_date_last_update_sets_param_filter_with_time_component(): void
    {
        Http::fake($this->fakeLoginResponses());

        $query = Auvo::tasks()->dateLastUpdate('2026-06-29 01:30:00');

        $reflection = new \ReflectionClass($query);
        $filtersProperty = $reflection->getProperty('filters');
        $filtersProperty->setAccessible(true);
        $filters = $filtersProperty->getValue($query);

        $this->assertSame('2026-06-29 01:30:00T00:00:00', $filters['dateLastUpdate']);
    }

    public function test_date_last_update_preserves_existing_time_component(): void
    {
        Http::fake($this->fakeLoginResponses());

        $query = Auvo::tasks()->dateLastUpdate('2026-06-29T01:30:00');

        $reflection = new \ReflectionClass($query);
        $filtersProperty = $reflection->getProperty('filters');
        $filtersProperty->setAccessible(true);
        $filters = $filtersProperty->getValue($query);

        $this->assertSame('2026-06-29T01:30:00', $filters['dateLastUpdate']);
    }

    public function test_date_last_update_is_sent_in_get_request(): void
    {
        Http::fake(array_merge($this->fakeLoginResponses(), [
            'api.test.com/v2/tasks*' => Http::response([
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

        Auvo::tasks()
            ->typeList('197452')
            ->dateLastUpdate('2026-06-29')
            ->getAll();

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/tasks')) {
                return false;
            }

            if (str_contains($request->url(), 'GetDeletedTasks')) {
                return false;
            }

            $paramFilter = json_decode($request->data()['paramFilter'] ?? '{}', true);

            return ($paramFilter['dateLastUpdate'] ?? null) === '2026-06-29T00:00:00';
        });
    }

    public function test_deleted_targets_get_deleted_tasks_endpoint(): void
    {
        Http::fake(array_merge($this->fakeLoginResponses(), [
            'api.test.com/v2/tasks/GetDeletedTasks*' => Http::response([
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

        Auvo::tasks()
            ->deleted()
            ->typeList('197452')
            ->getAll();

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/tasks/GetDeletedTasks');
        });
    }

    public function test_deleted_is_idempotent_on_repeated_calls(): void
    {
        Http::fake($this->fakeLoginResponses());

        $query = Auvo::tasks()->deleted()->deleted();

        $reflection = new \ReflectionClass($query);
        $endpointProperty = $reflection->getProperty('endpoint');
        $endpointProperty->setAccessible(true);

        $this->assertSame('/tasks/GetDeletedTasks', $endpointProperty->getValue($query));
    }

    public function test_deleted_query_preserves_period_and_type_list_filters(): void
    {
        Http::fake(array_merge($this->fakeLoginResponses(), [
            'api.test.com/v2/tasks/GetDeletedTasks*' => Http::response([
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

        Auvo::tasks()
            ->deleted()
            ->period('2025-01-01', '2026-06-29')
            ->typeList('197449,197448')
            ->getAll();

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/tasks/GetDeletedTasks')) {
                return false;
            }

            $paramFilter = json_decode($request->data()['paramFilter'] ?? '{}', true);

            return ($paramFilter['startDate'] ?? null) === '2025-01-01T00:00:00'
                && ($paramFilter['endDate'] ?? null) === '2026-06-29T23:59:59'
                && ($paramFilter['typeList'] ?? null) === '197449,197448';
        });
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
                    'created' => now()->format('Y-m-d H:i:s'),
                    'expiration' => now()->addMinutes(30)->format('Y-m-d H:i:s'),
                    'message' => 'OK',
                ],
            ], 200),
        ];
    }
}
