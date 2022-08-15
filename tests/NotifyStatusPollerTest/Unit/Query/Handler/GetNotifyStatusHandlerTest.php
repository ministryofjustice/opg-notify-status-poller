<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Query\Handler;

use Alphagov\Notifications\Exception\NotifyException;
use NotifyStatusPoller\Command\Model\UpdateDocumentStatus;
use NotifyStatusPoller\Exception\NotificationNotFoundException;
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
            'type' => 'email',
        ];

        $this->notifyClientMock->expects(self::once())->method('getNotification')->willReturn($response);

        $updateDocumentStatus = $handler->handle($getNotifyStatus);

        self::assertInstanceOf(UpdateDocumentStatus::class, $updateDocumentStatus);
        self::assertSame($response['id'], $updateDocumentStatus->getNotifyId());
        self::assertSame($response['status'], $updateDocumentStatus->getNotifyStatus());
        self::assertSame($getNotifyStatus->getNotifyId(), $updateDocumentStatus->getNotifyId());
        self::assertSame($response['type'], $updateDocumentStatus->getSendByMethod());
    }

    public function test_handle_returns_error_when_notification_retrieval_unsuccessful(): void
    {
        $getNotifyStatus = new GetNotifyStatus([
            'documentId' => '1234',
            'notifyId' => '1234',
        ]);
        $handler = new GetNotifyStatusHandler($this->notifyClientMock);
        $response = [
            'id' => $getNotifyStatus->getNotifyId(),
            'status' => '',
        ];

        $this->notifyClientMock->expects(self::once())->method('getNotification')->willReturn($response);

        self::expectException(NotificationNotFoundException::class);
        self::expectExceptionMessage(
            sprintf('Notification not found for document ID: ' , $response['id'])
        );

        $handler->handle($getNotifyStatus);
    }
}
