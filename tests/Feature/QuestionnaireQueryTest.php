<?php

namespace Jcf\Auvo\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Jcf\Auvo\Facades\Auvo;
use Jcf\Auvo\Query\QuestionnaireQuery;
use Jcf\Auvo\Tests\TestCase;

class QuestionnaireQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->forgetInstance('auvo');
        $this->app->forgetInstance('auvo.auth');

        Http::preventStrayRequests();
    }

    public function test_client_questionnaires_returns_questionnaire_query(): void
    {
        Http::fake($this->fakeLoginResponses());

        $query = Auvo::questionnaires();

        $this->assertInstanceOf(QuestionnaireQuery::class, $query);
    }

    public function test_questionnaire_query_uses_questionnaires_endpoint_with_pagination(): void
    {
        Http::fake(array_merge($this->fakeLoginResponses(), [
            'api.test.com/v2/questionnaires*' => Http::response([
                'result' => [
                    'entityList' => [
                        [
                            'id' => 91617,
                            'description' => 'M&V - TELEVISOR',
                            'header' => '',
                            'footer' => '',
                            'creationDate' => '2022-11-09T14:26:29',
                            'questions' => [
                                [
                                    'id' => 982348,
                                    'answerType' => 2,
                                    'description' => 'UC CLIENTE',
                                    'subTitle' => '',
                                    'requiredAnswer' => true,
                                    'creationDate' => '2022-11-09T14:26:29',
                                ],
                            ],
                        ],
                    ],
                    'pagedSearchReturnData' => [
                        'page' => 1,
                        'pageSize' => 100,
                        'totalItems' => 1,
                    ],
                ],
            ], 200),
        ]));

        $result = Auvo::questionnaires()->getAll();

        $this->assertCount(1, $result);
        $this->assertSame('M&V - TELEVISOR', $result->first()['description']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/questionnaires')
                && ($request->data()['page'] ?? null) === 1
                && ($request->data()['pageSize'] ?? null) === 100;
        });
    }

    public function test_questionnaire_query_supports_id_filter(): void
    {
        Http::fake($this->fakeLoginResponses());

        $query = Auvo::questionnaires()->id(91617);

        $reflection = new \ReflectionClass($query);
        $filtersProperty = $reflection->getProperty('filters');
        $filtersProperty->setAccessible(true);
        $filters = $filtersProperty->getValue($query);

        $this->assertSame(91617, $filters['id']);
    }

    public function test_questionnaire_query_supports_task_ids_filter(): void
    {
        Http::fake($this->fakeLoginResponses());

        $query = Auvo::questionnaires()->taskIds([39795241, 123]);

        $reflection = new \ReflectionClass($query);
        $filtersProperty = $reflection->getProperty('filters');
        $filtersProperty->setAccessible(true);
        $filters = $filtersProperty->getValue($query);

        $this->assertSame([39795241, 123], $filters['taskIDs']);
    }

    public function test_questionnaire_query_supports_page_page_size_and_order_params(): void
    {
        Http::fake($this->fakeLoginResponses());

        $query = Auvo::questionnaires()->page(3)->pageSize(25)->order('desc');

        $reflection = new \ReflectionClass($query);
        $paramsProperty = $reflection->getProperty('params');
        $paramsProperty->setAccessible(true);
        $params = $paramsProperty->getValue($query);

        $this->assertSame(3, $params['page']);
        $this->assertSame(25, $params['pageSize']);
        $this->assertSame('desc', $params['order']);
    }

    public function test_questionnaire_query_finds_single_questionnaire_by_id(): void
    {
        Http::fake(array_merge($this->fakeLoginResponses(), [
            'api.test.com/v2/questionnaires/91617' => Http::response([
                'result' => [
                    'id' => 91617,
                    'description' => 'M&V - TELEVISOR',
                    'questions' => [],
                ],
            ], 200),
        ]));

        $result = Auvo::questionnaires()->first(91617);

        $this->assertSame('M&V - TELEVISOR', $result['description']);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/questionnaires/91617'));
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
