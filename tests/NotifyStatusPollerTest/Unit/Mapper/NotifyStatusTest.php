<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Mapper;

use NotifyStatusPoller\Mapper\NotifyStatus;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class NotifyStatusTest extends TestCase
{
    /**
     * @param string $notifyStatus
     * @param string $expectedResult
     * @dataProvider notifyStatusProvider
     */
    public function testToSiriusSuccess(string $notifyStatus, string $expectedResult)
    {
        $mapper = new NotifyStatus();
        $actualResult = $mapper->toSirius($notifyStatus);

        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return array<array<string>>
     */
    public function notifyStatusProvider(): array
    {
        return [
            ['failed', 'rejected'],
            ['virus-scan-failed', 'rejected'],
            ['validation-failed', 'rejected'],
            ['pending-virus-check', 'queued'],
            ['accepted', 'posting'],
            ['received', 'posted'],
            ['cancelled', 'rejected'],
            ['technical-failure', 'rejected'],
            ['permanent-failure', 'rejected'],
            ['temporary-failure', 'rejected'],
            ['created', 'posting'],
            ['sending', 'posting'],
            ['delivered', 'posted'],
        ];
    }

    public function testToSiriusUnknownStatusFailure()
    {
        $mapper = new NotifyStatus();
        $unknownStatus = 'unforeseen consequence';

        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage(sprintf('Unknown Notify status "%s"', $unknownStatus));

        $mapper->toSirius($unknownStatus);
    }
}
