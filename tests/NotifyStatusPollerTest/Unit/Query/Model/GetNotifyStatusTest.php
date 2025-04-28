<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Query\Model;

use NotifyStatusPoller\Exception\AggregateValidationException;
use NotifyStatusPoller\Query\Model\GetNotifyStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GetNotifyStatusTest extends TestCase
{
    public function test_constructor_sets_properties(): void
    {
        $data = [
            'documentId' => 1234,
            'notifyId' => 'some-notify-id',
        ];
        $model = new GetNotifyStatus($data);

        self::assertEquals($data['documentId'], $model->getDocumentId());
        self::assertEquals($data['notifyId'], $model->getNotifyId());
    }
    /**
     * @param array $data
     * @param string|null $expectedExceptionMessage
     */
    #[DataProvider('constructorDataProvider')]
    public function test_constructor_throws_exception_for_invalid_data(
        array $data,
        string $expectedExceptionMessage
    ): void {
        self::expectException(AggregateValidationException::class);
        self::expectExceptionMessage($expectedExceptionMessage);

        new GetNotifyStatus($data);
    }

    /**
     * @return array<mixed>
     */
    public static function constructorDataProvider(): array
    {
        return [
            'missing documentId' => [
                ['notifyId' => 'some-reference'],
                'Data doesn\'t contain a numeric documentId'
            ],
            'non-numeric documentId' => [
                ['notifyId' => 'some-reference', 'documentId' => 'asdf'],
                'Data doesn\'t contain a numeric documentId'
            ],
            'missing notifyId' => [
                ['documentId' => 1234],
                'Data doesn\'t contain a notifyId'
            ],
        ];
    }
}

