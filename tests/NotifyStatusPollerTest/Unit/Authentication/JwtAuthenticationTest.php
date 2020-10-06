<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Authentication;

use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use NotifyStatusPoller\Authentication\JwtAuthentication;
use UnexpectedValueException;
use PHPUnit\Framework\TestCase;

class JwtAuthenticationTest extends TestCase
{
    private const JWT_SECRET = 'test';
    private const SESSION_DATA = 'test@test.com';

    private JwtAuthentication $authenticator;

    public function setUp(): void
    {
        parent::setUp();
        $this->authenticator = new JwtAuthentication(
            self::JWT_SECRET,
            self::SESSION_DATA
        );
    }

    public function testBuildHeadersSuccess(): void
    {
        $actualResult = $this->authenticator->buildHeaders();
        $token = explode(' ',$actualResult['Authorization'])[1];

        self::assertArrayHasKey("Authorization", $actualResult);

        $decoded_jwt=(array)JWT::decode($token,self::JWT_SECRET, array('HS256'));

        self::assertArrayHasKey('session-data', $decoded_jwt);
        self::assertEquals('test@test.com', $decoded_jwt['session-data']);
        self::assertArrayHasKey('iat', $decoded_jwt);
        self::assertArrayHasKey('exp', $decoded_jwt);
    }

}
