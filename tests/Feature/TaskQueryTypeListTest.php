<?php

namespace Jcf\Auvo\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Jcf\Auvo\Facades\Auvo;
use Jcf\Auvo\Tests\TestCase;

class TaskQueryTypeListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->forgetInstance('auvo');
        $this->app->forgetInstance('auvo.auth');

        Http::preventStrayRequests();
    }

    public function test_type_list_sets_param_filter_type_list(): void
    {
        Http::fake($this->fakeLoginResponses());

        $query = Auvo::tasks()->typeList('197449,197448,192587,197452');

        $reflection = new \ReflectionClass($query);
        $filtersProperty = $reflection->getProperty('filters');
        $filtersProperty->setAccessible(true);
        $filters = $filtersProperty->getValue($query);

        $this->assertSame('197449,197448,192587,197452', $filters['typeList']);
    }

    public function test_type_list_is_sent_in_get_request(): void
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
            ->selectFields('taskID,customerId,taskType')
            ->getAll();

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/tasks')) {
                return false;
            }

            $paramFilter = json_decode($request->data()['paramFilter'] ?? '{}', true);

            return ($paramFilter['typeList'] ?? null) === '197452';
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
