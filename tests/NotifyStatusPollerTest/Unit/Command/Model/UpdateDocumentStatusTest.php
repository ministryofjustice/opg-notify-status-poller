<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Command\Model;

use PHPUnit\Framework\TestCase;
use NotifyStatusPoller\Exception\AggregateValidationException;
use NotifyStatusPoller\Command\Model\UpdateDocumentStatus;

class UpdateDocumentStatusTest extends TestCase
{
    public function testFromArraySuccess(): void
    {
        $data = [
            'notifyId' => '1',
            'notifyStatus' => 'accepted',
            'documentId' => '4545',
            'sendByMethod' => 'email'
        ];

        $command = new UpdateDocumentStatus($data);

        self::assertEquals($data['notifyId'], $command->getNotifyId());
        self::assertEquals($data['notifyStatus'], $command->getNotifyStatus());
        self::assertEquals($data['documentId'], $command->getDocumentId());
    }

    /**
     * @param array<string,string> $data
     * @param string               $expectedMessage
     * @dataProvider commandDataProvider
     */
    public function testFromArrayThrowsExceptionFailure(array $data, string $expectedMessage): void
    {
        self::expectException(AggregateValidationException::class);
        self::expectExceptionMessage($expectedMessage);

        new UpdateDocumentStatus($data);
    }

    /**
     * @return array<string,array<array<string,string>,string>>
     */
    public function commandDataProvider(): array
    {
        return [
            'missing notifyId' => [
                ['notifyStatus' => 'accepted', 'documentId' => '4545'],
                'Data doesn\'t contain a notifyId'
            ],
            'missing notifyStatus' => [
                ['notifyId' => '1', 'documentId' => '4545'],
                'Data doesn\'t contain a notifyStatus'
            ],
            'missing documentId' => [
                ['notifyId' => '1', 'notifyStatus' => 'accepted'],
                'Data doesn\'t contain a numeric documentId'
            ],
            'non-numeric documentId' => [
                ['notifyId' => '1', 'notifyStatus' => 'accepted', 'documentId' => 'word'],
                'Data doesn\'t contain a numeric documentId'
            ],
            'missing all' => [
                [],
                implode(', ', [
                        'Data doesn\'t contain a numeric documentId',
                        'Data doesn\'t contain a notifyId',
                        'Data doesn\'t contain a notifyStatus',
                    ]
                ),
            ],
        ];
    }
}
