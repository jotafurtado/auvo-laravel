<?php

namespace Jcf\Auvo\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Jcf\Auvo\Exceptions\AuvoException;
use Jcf\Auvo\Facades\Auvo;
use Jcf\Auvo\Models\Token;
use Jcf\Auvo\Tests\TestCase;

class ClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->forgetInstance('auvo');
        $this->app->forgetInstance('auvo.auth');
    }

    public function test_it_can_authenticate_and_get_http_client(): void
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

        $client = Auvo::getHttpClient();

        $this->assertInstanceOf(\Illuminate\Http\Client\PendingRequest::class, $client);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.test.com/v2/login/'
                && $request->method() === 'POST';
        });
    }

    public function test_it_can_get_auth_manager(): void
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

        $authManager = Auvo::auth();

        $this->assertInstanceOf(\Jcf\Auvo\Auth\AuthManager::class, $authManager);
    }

    public function test_it_can_sign_in_via_facade(): void
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

        $token = Auvo::auth()->signIn();

        $this->assertInstanceOf(Token::class, $token);
        $this->assertEquals('test-access-token', $token->accessToken);
    }

    public function test_it_throws_exception_when_credentials_are_missing(): void
    {
        config([
            'auvo.api_key' => null,
            'auvo.api_token' => null,
        ]);

        $this->app->forgetInstance('auvo.auth');

        $this->expectException(AuvoException::class);
        $this->expectExceptionMessage('credenciais de autenticação');

        $this->app->make('auvo.auth');
    }

    public function test_it_can_get_access_token_via_facade(): void
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

        $accessToken = Auvo::auth()->getAccessToken();

        $this->assertEquals('test-access-token', $accessToken);
    }

    public function test_it_can_get_users_query_builder(): void
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

        $usersQuery = Auvo::users();

        $this->assertInstanceOf(\Jcf\Auvo\Query\UserQuery::class, $usersQuery);
    }

    public function test_it_can_get_tasks_query_builder(): void
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

        $tasksQuery = Auvo::tasks();

        $this->assertInstanceOf(\Jcf\Auvo\Query\TaskQuery::class, $tasksQuery);
    }

    public function test_it_can_get_customers_query_builder(): void
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

        $customersQuery = Auvo::customers();

        $this->assertInstanceOf(\Jcf\Auvo\Query\CustomerQuery::class, $customersQuery);
    }

    public function test_it_can_get_teams_query_builder(): void
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

        $teamsQuery = Auvo::teams();

        $this->assertInstanceOf(\Jcf\Auvo\Query\TeamQuery::class, $teamsQuery);
    }
}
