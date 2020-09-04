<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Query\Handler;

use NotifyStatusPoller\Command\Model\UpdateDocumentStatus;
use NotifyStatusPoller\Query\Handler\GetNotifyStatusHandler;
use NotifyStatusPoller\Query\Model\GetNotifyStatus;
use PHPUnit\Framework\TestCase;
use Alphagov\Notifications\Client as NotifyClient;

class GetNotifyStatusHandlerTest extends TestCase
{
    private $notifyClientMock;

    public function setUp(): void
    {
        $this->notifyClientMock = $this->createMock(NotifyClient::class);
    }

    public function test_handle_returns_populated_model_on_success(): void
    {
        $getNotifyStatus = new GetNotifyStatus([
            'documentId' => '1234',
            'notifyId' => '1234',
        ]);
        $handler = new GetNotifyStatusHandler($this->notifyClientMock);
        $response = [
            'id' => $getNotifyStatus->getNotifyId(),
            'status' => 'notify-status',
        ];

        $this->notifyClientMock->expects(self::once())->method('getNotification')->willReturn($response);

        $updateDocumentStatus = $handler->handle($getNotifyStatus);

        self::assertInstanceOf(UpdateDocumentStatus::class, $updateDocumentStatus);
        self::assertSame($response['id'], $updateDocumentStatus->getNotifyId());
        self::assertSame($response['status'], $updateDocumentStatus->getNotifyStatus());
        self::assertSame($getNotifyStatus->getNotifyId(), $updateDocumentStatus->getNotifyId());
    }
}
