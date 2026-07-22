<?php

namespace Jcf\Auvo\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jcf\Auvo\Exceptions\NotFoundException;
use Jcf\Auvo\Facades\Auvo;
use Jcf\Auvo\Tests\TestCase;

class HttpClientRetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['auvo.retry' => 3, 'auvo.retry_delay' => 1]);
        $this->app->forgetInstance('auvo');
        $this->app->forgetInstance('auvo.auth');
    }

    public function test_it_retries_server_errors_up_to_a_successful_third_attempt(): void
    {
        Http::fake([
            'api.test.com/v2/login/' => $this->loginResponse(),
            'api.test.com/v2/tasks*' => Http::sequence()
                ->push('temporary failure', 500)
                ->push('temporary failure', 500)
                ->push(['result' => ['entityList' => [['taskID' => 123]]]], 200),
        ]);

        $response = Auvo::tasks()->taskId(123)->get();

        $this->assertSame(123, $response->entityList()->first()['taskID']);

        $taskRequests = collect();
        Http::assertSent(function (Request $request) use ($taskRequests): bool {
            if (str_contains($request->url(), '/tasks')) {
                $taskRequests->push($request);
            }

            return true;
        });

        $this->assertCount(3, $taskRequests);
    }

    public function test_it_does_not_retry_not_found_responses(): void
    {
        Http::fake([
            'api.test.com/v2/login/' => $this->loginResponse(),
            'api.test.com/v2/tasks*' => Http::response('not found', 404),
        ]);

        $this->expectException(NotFoundException::class);

        try {
            Auvo::tasks()->taskId(321)->get();
        } finally {
            $taskRequests = collect();
            Http::assertSent(function (Request $request) use ($taskRequests): bool {
                if (str_contains($request->url(), '/tasks')) {
                    $taskRequests->push($request);
                }

                return true;
            });

            $this->assertCount(1, $taskRequests);
        }
    }

    private function loginResponse(): mixed
    {
        return Http::response([
            'result' => [
                'authenticated' => true,
                'accessToken' => 'test-access-token',
                'created' => Carbon::now()->format('Y-m-d H:i:s'),
                'expiration' => Carbon::now()->addMinutes(30)->format('Y-m-d H:i:s'),
                'message' => 'OK',
            ],
        ], 200);
    }
}
