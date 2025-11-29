<?php

namespace Jcf\Auvo\Tests\Unit;

use Carbon\Carbon;
use Jcf\Auvo\Models\Token;
use Jcf\Auvo\Tests\TestCase;

class TokenTest extends TestCase
{
    public function test_it_can_create_token_from_array_with_result(): void
    {
        $data = [
            'result' => [
                'authenticated' => true,
                'accessToken' => 'test-access-token',
                'created' => '2024-01-01 10:00:00',
                'expiration' => Carbon::now()->addMinutes(30)->format('Y-m-d H:i:s'),
                'message' => 'OK',
            ],
        ];

        $token = Token::fromArray($data);

        $this->assertTrue($token->authenticated);
        $this->assertEquals('test-access-token', $token->accessToken);
        $this->assertEquals('2024-01-01 10:00:00', $token->created);
        $this->assertNotNull($token->expiration);
        $this->assertEquals('OK', $token->message);
    }

    public function test_it_can_create_token_from_array_without_result_wrapper(): void
    {
        $data = [
            'authenticated' => true,
            'accessToken' => 'test-access-token',
            'created' => '2024-01-01 10:00:00',
            'expiration' => Carbon::now()->addMinutes(30)->format('Y-m-d H:i:s'),
            'message' => 'OK',
        ];

        $token = Token::fromArray($data);

        $this->assertTrue($token->authenticated);
        $this->assertEquals('test-access-token', $token->accessToken);
    }

    public function test_it_can_detect_expired_token(): void
    {
        $token = new Token(
            authenticated: true,
            accessToken: 'test-token',
            expiration: Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s'),
        );

        $this->assertTrue($token->isExpired());
    }

    public function test_it_can_detect_valid_token(): void
    {
        $token = new Token(
            authenticated: true,
            accessToken: 'test-token',
            expiration: Carbon::now()->addMinutes(15)->format('Y-m-d H:i:s'),
        );

        $this->assertFalse($token->isExpired());
    }

    public function test_token_without_expiration_is_considered_expired(): void
    {
        $token = new Token(
            authenticated: true,
            accessToken: 'test-token',
        );

        $this->assertTrue($token->isExpired());
    }

    public function test_it_can_convert_to_array(): void
    {
        $token = new Token(
            authenticated: true,
            accessToken: 'test-access',
            created: '2024-01-01 10:00:00',
            expiration: '2024-01-01 10:30:00',
            message: 'Success',
        );

        $array = $token->toArray();

        $this->assertIsArray($array);
        $this->assertTrue($array['authenticated']);
        $this->assertEquals('test-access', $array['accessToken']);
        $this->assertEquals('2024-01-01 10:00:00', $array['created']);
        $this->assertEquals('2024-01-01 10:30:00', $array['expiration']);
        $this->assertEquals('Success', $array['message']);
    }
}
