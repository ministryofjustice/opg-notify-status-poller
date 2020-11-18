<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Authentication;

use Firebase\JWT\JWT;
use NotifyStatusPoller\Authentication\JwtAuthenticator;
use PHPUnit\Framework\TestCase;

class JwtAuthenticatorTest extends TestCase
{
    private const JWT_SECRET = 'test';
    private const API_USER_EMAIL = 'test@test.com';

    private JwtAuthenticator $authenticator;

    public function setUp(): void
    {
        parent::setUp();
        $this->authenticator = new JwtAuthenticator(
            self::JWT_SECRET,
            self::API_USER_EMAIL
        );
    }

    public function testCreateTokenSuccess(): void
    {
        $headers = $this->authenticator->createToken();

        self::assertArrayHasKey("Authorization", $headers);
        self::assertArrayHasKey("Content-Type", $headers);

        $jwtToken = explode(' ',$headers['Authorization'])[1];
        $decodedJwt=(array)JWT::decode($jwtToken,self::JWT_SECRET, array('HS256'));

        self::assertArrayHasKey('session-data', $decodedJwt);
        self::assertEquals(self::API_USER_EMAIL, $decodedJwt['session-data']);
        self::assertArrayHasKey('iat', $decodedJwt);
        self::assertArrayHasKey('exp', $decodedJwt);
    }

}
