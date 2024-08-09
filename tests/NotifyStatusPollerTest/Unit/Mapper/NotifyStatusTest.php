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
    public static function notifyStatusProvider(): array
    {
        return [
            ['failed', NotifyStatus::FAILED],
            ['virus-scan-failed', NotifyStatus::VIRUS_SCAN_FAILED],
            ['validation-failed', NotifyStatus::VALIDATION_FAILED],
            ['pending-virus-check', NotifyStatus::PENDING_VIRUS_CHECK],
            ['accepted', NotifyStatus::ACCEPTED],
            ['received', NotifyStatus::RECEIVED],
            ['cancelled', NotifyStatus::CANCELLED],
            ['technical-failure', NotifyStatus::TECHNICAL_FAILURE],
            ['permanent-failure', NotifyStatus::PERMANENT_FAILURE],
            ['temporary-failure', NotifyStatus::TEMPORARY_FAILURE],
            ['created', NotifyStatus::CREATED],
            ['sending', NotifyStatus::SENDING],
            ['delivered', NotifyStatus::DELIVERED],
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
