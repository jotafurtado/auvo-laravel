<?php

namespace Jcf\Auvo\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Jcf\Auvo\Auth\AuthManager;
use Jcf\Auvo\Exceptions\AuthenticationException;
use Jcf\Auvo\Exceptions\AuvoException;
use Jcf\Auvo\Models\Token;
use Jcf\Auvo\Tests\TestCase;

class AuthManagerTest extends TestCase
{
    public function test_it_can_sign_in(): void
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

        $auth = new AuthManager(
            baseUri: 'https://api.test.com/v2',
            apiKey: 'test-api-key',
            apiToken: 'test-api-token',
        );

        $token = $auth->signIn();

        $this->assertInstanceOf(Token::class, $token);
        $this->assertTrue($token->authenticated);
        $this->assertEquals('test-access-token', $token->accessToken);
    }

    public function test_it_throws_exception_on_failed_authentication(): void
    {
        Http::fake([
            'api.test.com/v2/login/' => Http::response(['error' => 'Invalid credentials'], 401),
        ]);

        $auth = new AuthManager(
            baseUri: 'https://api.test.com/v2',
            apiKey: 'wrong-key',
            apiToken: 'wrong-token',
        );

        $this->expectException(AuthenticationException::class);

        $auth->signIn();
    }

    public function test_it_throws_exception_when_authentication_is_not_successful(): void
    {
        Http::fake([
            'api.test.com/v2/login/' => Http::response([
                'result' => [
                    'authenticated' => false,
                    'message' => 'Invalid API credentials',
                ],
            ], 200),
        ]);

        $auth = new AuthManager(
            baseUri: 'https://api.test.com/v2',
            apiKey: 'test-api-key',
            apiToken: 'test-api-token',
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Autenticação falhou');

        $auth->signIn();
    }

    public function test_it_throws_exception_when_refresh_token_is_called(): void
    {
        $auth = new AuthManager(
            baseUri: 'https://api.test.com/v2',
            apiKey: 'test-api-key',
            apiToken: 'test-api-token',
        );

        $this->expectException(AuvoException::class);
        $this->expectExceptionMessage('não suporta refresh token');

        $auth->refreshToken();
    }

    public function test_it_can_get_valid_token(): void
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

        $auth = new AuthManager(
            baseUri: 'https://api.test.com/v2',
            apiKey: 'test-api-key',
            apiToken: 'test-api-token',
        );

        $token = $auth->getValidToken();

        $this->assertInstanceOf(Token::class, $token);
        $this->assertTrue($token->authenticated);
        $this->assertEquals('test-access-token', $token->accessToken);
    }

    public function test_it_makes_new_login_when_token_is_expired(): void
    {
        Http::fake([
            'api.test.com/v2/login/' => Http::sequence()
                ->push([
                    'result' => [
                        'authenticated' => true,
                        'accessToken' => 'first-token',
                        'created' => Carbon::now()->subMinutes(35)->format('Y-m-d H:i:s'),
                        'expiration' => Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'),
                        'message' => 'OK',
                    ],
                ], 200)
                ->push([
                    'result' => [
                        'authenticated' => true,
                        'accessToken' => 'new-token',
                        'created' => Carbon::now()->format('Y-m-d H:i:s'),
                        'expiration' => Carbon::now()->addMinutes(30)->format('Y-m-d H:i:s'),
                        'message' => 'OK',
                    ],
                ], 200),
        ]);

        $auth = new AuthManager(
            baseUri: 'https://api.test.com/v2',
            apiKey: 'test-api-key',
            apiToken: 'test-api-token',
        );

        // Primeiro login
        $auth->signIn();

        // Pega o token válido (deve fazer novo login pois o anterior está expirado)
        $token = $auth->getValidToken();

        $this->assertEquals('new-token', $token->accessToken);
    }

    public function test_it_can_get_access_token(): void
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

        $auth = new AuthManager(
            baseUri: 'https://api.test.com/v2',
            apiKey: 'test-api-key',
            apiToken: 'test-api-token',
        );

        $accessToken = $auth->getAccessToken();

        $this->assertEquals('test-access-token', $accessToken);
    }
}
