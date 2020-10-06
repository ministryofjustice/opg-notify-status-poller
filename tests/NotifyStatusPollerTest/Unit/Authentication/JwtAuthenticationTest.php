<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Authentication;

use Firebase\JWT\JWT;
use NotifyStatusPoller\Authentication\JwtAuthentication;
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
        $headers = $this->authenticator->buildHeaders();

        self::assertArrayHasKey("Authorization", $headers);
        self::assertArrayHasKey("Content-type", $headers);

        $jwtToken = explode(' ',$headers['Authorization'])[1];
        $decodedJwt=(array)JWT::decode($jwtToken,self::JWT_SECRET, array('HS256'));

        self::assertArrayHasKey('session-data', $decodedJwt);
        self::assertEquals('test@test.com', $decodedJwt['session-data']);
        self::assertArrayHasKey('iat', $decodedJwt);
        self::assertArrayHasKey('exp', $decodedJwt);
    }

}
