<?php

namespace Jcf\Auvo\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Jcf\Auvo\Facades\Auvo;
use Jcf\Auvo\Http\AuvoResponse;
use Jcf\Auvo\Tests\TestCase;

class QueryBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->forgetInstance('auvo');
        $this->app->forgetInstance('auvo.auth');

        // Fake de autenticação para todos os testes
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

    public function test_user_query_builder_can_filter_by_type(): void
    {
        $query = Auvo::users()->userType(3);

        $reflection = new \ReflectionClass($query);
        $property = $reflection->getProperty('filters');
        $property->setAccessible(true);
        $filters = $property->getValue($query);

        $this->assertEquals(3, $filters['userType']);
    }

    public function test_user_query_builder_can_filter_available_users(): void
    {
        $query = Auvo::users()->availableForTasks();

        $reflection = new \ReflectionClass($query);
        $property = $reflection->getProperty('filters');
        $property->setAccessible(true);
        $filters = $property->getValue($query);

        $this->assertFalse($filters['unavailableForTasks']);
    }

    public function test_user_query_builder_can_chain_filters(): void
    {
        $query = Auvo::users()
            ->userType(3)
            ->availableForTasks()
            ->email('admin@example.com');

        $reflection = new \ReflectionClass($query);
        $property = $reflection->getProperty('filters');
        $property->setAccessible(true);
        $filters = $property->getValue($query);

        $this->assertEquals(3, $filters['userType']);
        $this->assertFalse($filters['unavailableForTasks']);
        $this->assertEquals('admin@example.com', $filters['email']);
    }

    public function test_task_query_builder_can_filter_by_period(): void
    {
        $query = Auvo::tasks()->period('2024-01-01', '2024-01-31');

        $reflection = new \ReflectionClass($query);
        $property = $reflection->getProperty('filters');
        $property->setAccessible(true);
        $filters = $property->getValue($query);

        $this->assertEquals('2024-01-01T00:00:00', $filters['startDate']);
        $this->assertEquals('2024-01-31T23:59:59', $filters['endDate']);
    }

    public function test_task_query_builder_can_filter_scheduled_tasks(): void
    {
        $query = Auvo::tasks()->scheduled();

        $reflection = new \ReflectionClass($query);
        $property = $reflection->getProperty('filters');
        $property->setAccessible(true);
        $filters = $property->getValue($query);

        $this->assertEquals('scheduled', $filters['status']);
    }

    public function test_task_query_builder_can_chain_filters(): void
    {
        $query = Auvo::tasks()
            ->period('2024-01-01', '2024-01-31')
            ->userId(123)
            ->scheduled();

        $reflection = new \ReflectionClass($query);
        $property = $reflection->getProperty('filters');
        $property->setAccessible(true);
        $filters = $property->getValue($query);

        $this->assertEquals('2024-01-01T00:00:00', $filters['startDate']);
        $this->assertEquals('2024-01-31T23:59:59', $filters['endDate']);
        $this->assertEquals(123, $filters['userId']);
        $this->assertEquals('scheduled', $filters['status']);
    }

    public function test_task_query_builder_can_set_pagination(): void
    {
        $query = Auvo::tasks()
            ->page(2)
            ->pageSize(50);

        $reflection = new \ReflectionClass($query);
        $property = $reflection->getProperty('params');
        $property->setAccessible(true);
        $params = $property->getValue($query);

        $this->assertEquals(2, $params['page']);
        $this->assertEquals(50, $params['pageSize']);
    }

    public function test_task_query_builder_can_select_fields(): void
    {
        $query = Auvo::tasks()
            ->selectFields('taskID,customerId,finished');

        $reflection = new \ReflectionClass($query);
        $property = $reflection->getProperty('params');
        $property->setAccessible(true);
        $params = $property->getValue($query);

        $this->assertEquals('taskID,customerId,finished', $params['selectfields']);
    }

    public function test_task_query_builder_can_combine_all_filters(): void
    {
        $query = Auvo::tasks()
            ->period('2024-01-01', '2024-12-31')
            ->type(197448)
            ->page(1)
            ->pageSize(100)
            ->selectFields('taskID,customerId,customerDescription,finished');

        $reflectionFilters = new \ReflectionClass($query);
        $propertyFilters = $reflectionFilters->getProperty('filters');
        $propertyFilters->setAccessible(true);
        $filters = $propertyFilters->getValue($query);

        $reflectionParams = new \ReflectionClass($query);
        $propertyParams = $reflectionParams->getProperty('params');
        $propertyParams->setAccessible(true);
        $params = $propertyParams->getValue($query);

        $this->assertEquals('2024-01-01T00:00:00', $filters['startDate']);
        $this->assertEquals('2024-12-31T23:59:59', $filters['endDate']);
        $this->assertEquals(197448, $filters['type']);
        $this->assertEquals(1, $params['page']);
        $this->assertEquals(100, $params['pageSize']);
        $this->assertEquals('taskID,customerId,customerDescription,finished', $params['selectfields']);
    }

    public function test_customer_query_builder_can_filter_active(): void
    {
        $query = Auvo::customers()->active();

        $reflection = new \ReflectionClass($query);
        $property = $reflection->getProperty('filters');
        $property->setAccessible(true);
        $filters = $property->getValue($query);

        $this->assertTrue($filters['active']);
    }

    public function test_customer_query_builder_can_filter_by_document(): void
    {
        $query = Auvo::customers()->document('12345678901');

        $reflection = new \ReflectionClass($query);
        $property = $reflection->getProperty('filters');
        $property->setAccessible(true);
        $filters = $property->getValue($query);

        $this->assertEquals('12345678901', $filters['document']);
    }

    public function test_team_query_builder_can_filter_active(): void
    {
        $query = Auvo::teams()->active();

        $reflection = new \ReflectionClass($query);
        $property = $reflection->getProperty('filters');
        $property->setAccessible(true);
        $filters = $property->getValue($query);

        $this->assertTrue($filters['active']);
    }

    public function test_team_query_builder_can_filter_by_manager(): void
    {
        $query = Auvo::teams()->managerId(456);

        $reflection = new \ReflectionClass($query);
        $property = $reflection->getProperty('filters');
        $property->setAccessible(true);
        $filters = $property->getValue($query);

        $this->assertEquals(456, $filters['managerId']);
    }

    public function test_get_returns_auvo_response_for_list_endpoints(): void
    {
        Http::fake([
            'api.test.com/v2/tasks*' => Http::response([
                'result' => [
                    'entityList' => [
                        ['taskID' => 1, 'customerId' => 100],
                        ['taskID' => 2, 'customerId' => 200],
                    ],
                    'pagedSearchReturnData' => [
                        'page' => 1,
                        'pageSize' => 10,
                        'totalItems' => 2,
                    ],
                    'links' => [],
                ],
            ], 200),
        ]);

        $response = Auvo::tasks()->get();

        $this->assertInstanceOf(AuvoResponse::class, $response);
    }

    public function test_auvo_response_entity_list(): void
    {
        Http::fake([
            'api.test.com/v2/tasks*' => Http::response([
                'result' => [
                    'entityList' => [
                        ['taskID' => 1, 'customerId' => 100],
                        ['taskID' => 2, 'customerId' => 200],
                    ],
                    'pagedSearchReturnData' => [
                        'page' => 1,
                        'pageSize' => 10,
                        'totalItems' => 2,
                    ],
                    'links' => [],
                ],
            ], 200),
        ]);

        $response = Auvo::tasks()->get();
        $entities = $response->entityList();

        $this->assertInstanceOf(Collection::class, $entities);
        $this->assertCount(2, $entities);
        $this->assertEquals(1, $entities[0]['taskID']);
        $this->assertEquals(2, $entities[1]['taskID']);
    }

    public function test_auvo_response_pagination_methods(): void
    {
        Http::fake([
            'api.test.com/v2/tasks*' => Http::response([
                'result' => [
                    'entityList' => [
                        ['taskID' => 1],
                    ],
                    'pagedSearchReturnData' => [
                        'page' => 2,
                        'pageSize' => 10,
                        'totalItems' => 25,
                    ],
                    'links' => [],
                ],
            ], 200),
        ]);

        $response = Auvo::tasks()->get();

        $this->assertEquals(25, $response->totalItems());
        $this->assertEquals(2, $response->currentPage());
        $this->assertEquals(10, $response->pageSize());
    }

    public function test_auvo_response_has_more_pages(): void
    {
        Http::fake([
            'api.test.com/v2/tasks*' => Http::response([
                'result' => [
                    'entityList' => [
                        ['taskID' => 1],
                    ],
                    'pagedSearchReturnData' => [
                        'page' => 1,
                        'pageSize' => 10,
                        'totalItems' => 25,
                    ],
                    'links' => [],
                ],
            ], 200),
        ]);

        $response = Auvo::tasks()->get();

        $this->assertTrue($response->hasMorePages());
    }

    public function test_auvo_response_no_more_pages(): void
    {
        Http::fake([
            'api.test.com/v2/tasks*' => Http::response([
                'result' => [
                    'entityList' => [
                        ['taskID' => 1],
                    ],
                    'pagedSearchReturnData' => [
                        'page' => 3,
                        'pageSize' => 10,
                        'totalItems' => 25,
                    ],
                    'links' => [],
                ],
            ], 200),
        ]);

        $response = Auvo::tasks()->get();

        $this->assertFalse($response->hasMorePages());
    }

    public function test_auvo_response_magic_methods(): void
    {
        Http::fake([
            'api.test.com/v2/tasks*' => Http::response([
                'result' => [
                    'entityList' => [
                        ['taskID' => 1, 'finished' => false],
                        ['taskID' => 2, 'finished' => true],
                        ['taskID' => 3, 'finished' => false],
                    ],
                    'pagedSearchReturnData' => [
                        'page' => 1,
                        'pageSize' => 10,
                        'totalItems' => 3,
                    ],
                    'links' => [],
                ],
            ], 200),
        ]);

        $response = Auvo::tasks()->get();

        // Testar métodos de Collection delegados
        $this->assertCount(3, $response);
        $this->assertEquals(1, $response->first()['taskID']);

        $filtered = $response->filter(fn ($task) => $task['finished'] === true);
        $this->assertCount(1, $filtered);
    }

    public function test_get_all_returns_collection(): void
    {
        Http::fake([
            'api.test.com/v2/tasks*page=1*' => Http::response([
                'result' => [
                    'entityList' => [
                        ['id' => 1, 'name' => 'Task 1'],
                        ['id' => 2, 'name' => 'Task 2'],
                    ],
                    'pagedSearchReturnData' => [
                        'page' => 1,
                        'pageSize' => 100,
                        'totalItems' => 3,
                    ],
                ],
            ], 200),
            'api.test.com/v2/tasks*page=2*' => Http::response([
                'result' => [
                    'entityList' => [
                        ['id' => 3, 'name' => 'Task 3'],
                    ],
                    'pagedSearchReturnData' => [
                        'page' => 2,
                        'pageSize' => 100,
                        'totalItems' => 3,
                    ],
                ],
            ], 200),
        ]);

        $allTasks = Auvo::tasks()->getAll();

        $this->assertInstanceOf(Collection::class, $allTasks);
        $this->assertCount(3, $allTasks);
        $this->assertEquals('Task 1', $allTasks[0]['name']);
        $this->assertEquals('Task 2', $allTasks[1]['name']);
        $this->assertEquals('Task 3', $allTasks[2]['name']);
    }

    public function test_get_all_with_filters(): void
    {
        Http::fake([
            'api.test.com/v2/tasks*' => Http::response([
                'result' => [
                    'entityList' => [
                        ['id' => 1, 'type' => 197448],
                        ['id' => 2, 'type' => 197448],
                    ],
                    'pagedSearchReturnData' => [
                        'page' => 1,
                        'pageSize' => 100,
                        'totalItems' => 2,
                    ],
                ],
            ], 200),
        ]);

        $allTasks = Auvo::tasks()
            ->type(197448)
            ->period('2024-01-01', '2024-12-31')
            ->getAll();

        $this->assertInstanceOf(Collection::class, $allTasks);
        $this->assertCount(2, $allTasks);
        $this->assertEquals(197448, $allTasks[0]['type']);
    }

    public function test_all_pages_returns_query_object(): void
    {
        $allPagesQuery = Auvo::tasks()->allPages();

        $this->assertInstanceOf(\Jcf\Auvo\Query\AllPagesQuery::class, $allPagesQuery);
    }

    public function test_get_all_handles_empty_results(): void
    {
        Http::fake([
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
        ]);

        $allTasks = Auvo::tasks()->getAll();

        $this->assertInstanceOf(Collection::class, $allTasks);
        $this->assertEmpty($allTasks);
    }

    public function test_first_returns_collection(): void
    {
        Http::fake([
            'api.test.com/v2/tasks/123' => Http::response([
                'result' => [
                    'taskID' => 123,
                    'customerId' => 456,
                    'finished' => false,
                ],
            ], 200),
        ]);

        $task = Auvo::tasks()->first(123);

        $this->assertInstanceOf(Collection::class, $task);
        $this->assertEquals(123, $task->get('taskID'));
        $this->assertEquals(456, $task->get('customerId'));
    }
}
