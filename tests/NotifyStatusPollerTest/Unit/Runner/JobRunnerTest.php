<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Runner;

use Exception;
use InvalidArgumentException;
use NotifyStatusPoller\Command\Handler\UpdateDocumentStatusHandler;
use NotifyStatusPoller\Command\Model\UpdateDocumentStatus;
use NotifyStatusPoller\Exception\NotificationNotFoundException;
use NotifyStatusPoller\Logging\Context;
use NotifyStatusPoller\Mapper\NotifyStatus;
use NotifyStatusPoller\Query\Handler\GetInProgressDocumentsHandler;
use NotifyStatusPoller\Query\Handler\GetNotifyStatusHandler;
use NotifyStatusPoller\Query\Model\GetNotifyStatus;
use NotifyStatusPoller\Runner\JobRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class JobRunnerTest extends TestCase
{
    private JobRunner $jobRunner;
    private LoggerInterface&MockObject $logger;
    private GetNotifyStatusHandler $getNotifyStatusHandlerMock;
    private GetInProgressDocumentsHandler $getInProgressDocumentsHandlerMock;
    private UpdateDocumentStatusHandler $updateDocumentStatusHandlerMock;

    public function setUp(): void
    {
        $this->getInProgressDocumentsHandlerMock = $this->createMock(GetInProgressDocumentsHandler::class);
        $this->getNotifyStatusHandlerMock = $this->createMock(GetNotifyStatusHandler::class);
        $this->updateDocumentStatusHandlerMock = $this->createMock(UpdateDocumentStatusHandler::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->jobRunner = new JobRunner(
            $this->getInProgressDocumentsHandlerMock,
            $this->getNotifyStatusHandlerMock,
            $this->updateDocumentStatusHandlerMock,
            $this->logger
        );
    }

    /**
     * @throws Throwable
     */
    public function test_run_updates_status_success(): void
    {
        $inProgressDocuments = [
            new GetNotifyStatus([
                'documentId' => 100,
                'notifyId' => 'ref-1',
            ]),
            new GetNotifyStatus([
                'documentId' => 200,
                'notifyId' => 'ref-2',
            ]),
        ];
        $updateDocumentStatuses = [
            new UpdateDocumentStatus([
                'documentId' => 100,
                'notifyId' => 'ref-1',
                'notifyStatus' => 'accepted',
                'sendByMethod' => 'email',
                'recipientEmailAddress' => 'test@test.com',
            ]),
            new UpdateDocumentStatus([
                'documentId' => 200,
                'notifyId' => 'ref-2',
                'notifyStatus' => 'received',
                'sendByMethod' => 'email',
                'recipientEmailAddress' => 'test@test.com',
            ]),
        ];

        $this->getInProgressDocumentsHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->willReturn($inProgressDocuments);

        $this->getNotifyStatusHandlerMock
            ->expects(self::exactly(2))
            ->method('handle')
            ->willReturnCallback(
                fn ($value) => match($value) {
                    $inProgressDocuments[0] => $updateDocumentStatuses[0],
                    $inProgressDocuments[1] => $updateDocumentStatuses[1],
                    default => throw new \LogicException()
                }
            );

        $this->updateDocumentStatusHandlerMock
            ->expects(self::exactly(2))
            ->method('handle')
            ->willReturnCallback(
                fn ($value) => match($value) {
                    $updateDocumentStatuses[0], $updateDocumentStatuses[1] => null,
                    default => throw new \LogicException()
                }
            );

        $countMatcher = $this->exactly(3);
        $this->logger
            ->expects($countMatcher)
            ->method('info')
            ->with($this->callback(function ($msg) use ($countMatcher) {
                return $msg === match ($countMatcher->numberOfInvocations()) {
                    1 => 'Start',
                    2 => 'Updating',
                    3 => 'Finished',
                    default => throw new InvalidArgumentException('Method execution count not supported'),
                };
            }), $this->callback(function ($context) use ($countMatcher) {
                return $context === match ($countMatcher->numberOfInvocations()) {
                    1 => ['context' => Context::NOTIFY_POLLER],
                    2 => ['count' => 2, 'context' => Context::NOTIFY_POLLER],
                    3 => [
                        'count' => 2,
                        'notify_status_counts' => [
                            NotifyStatus::PENDING_VIRUS_CHECK => 0,
                            NotifyStatus::VIRUS_SCAN_FAILED => 0,
                            NotifyStatus::VALIDATION_FAILED => 0,
                            NotifyStatus::FAILED => 0,
                            NotifyStatus::ACCEPTED => 1,
                            NotifyStatus::RECEIVED => 1,
                            NotifyStatus::CANCELLED => 0,
                            NotifyStatus::TECHNICAL_FAILURE => 0,
                            NotifyStatus::PERMANENT_FAILURE => 0,
                            NotifyStatus::TEMPORARY_FAILURE => 0,
                            NotifyStatus::CREATED => 0,
                            NotifyStatus::SENDING => 0,
                            NotifyStatus::DELIVERED => 0,
                        ],
                        'context' => Context::NOTIFY_POLLER,
                    ],
                    default => throw new InvalidArgumentException('Method execution count not supported'),
                };
            }));

        $this->jobRunner->run();
    }

    /**
     * @throws Throwable
     */
    public function test_run_catch_exception_thrown_fetching_statuses_failure(): void
    {
        $expectedException = new Exception('Oops...');

        $this->getInProgressDocumentsHandlerMock
            ->method('handle')
            ->willThrowException($expectedException);

        $this->getNotifyStatusHandlerMock->expects(self::never())->method('handle');
        $this->updateDocumentStatusHandlerMock->expects(self::never())->method('handle');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->callback(fn ($msg) => in_array($msg, ['Start'])));

        $this->logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->stringContains($expectedException->getMessage()));

        $this->jobRunner->run();
    }

    /**
     * @throws Throwable
     */
    public function test_run_thrown_exception_from_notify_handler_is_logged_and_continues_success(): void
    {
        $expectedException = new Exception('Oops...');
        $inProgressDocuments = [
            new GetNotifyStatus([
                'documentId' => 100,
                'notifyId' => 'ref-1',
            ]),
            new GetNotifyStatus([
                'documentId' => 200,
                'notifyId' => 'ref-2',
            ]),
        ];
        $updateDocumentStatuses = [
            new UpdateDocumentStatus([
                'documentId' => 100,
                'notifyId' => 'ref-1',
                'notifyStatus' => 'status-1',
                'sendByMethod' => 'email',
                'recipientEmailAddress' => 'test@test.com'
            ]),
            new UpdateDocumentStatus([
                'documentId' => 200,
                'notifyId' => 'ref-2',
                'notifyStatus' => 'status-2',
                'sendByMethod' => 'email',
                'recipientEmailAddress' => 'test@test.com'
            ]),
        ];

        $this->getInProgressDocumentsHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->willReturn($inProgressDocuments);

        $this->getNotifyStatusHandlerMock
            ->expects(self::exactly(2))
            ->method('handle')
            ->willReturnCallback(
                fn ($value) => match($value) {
                    $inProgressDocuments[0] => throw $expectedException,
                    $inProgressDocuments[1] => $updateDocumentStatuses[1],
                    default => throw new RuntimeException('Mock did not expect value ' . print_r($value, true))
                }
            );

        $this->updateDocumentStatusHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($updateDocumentStatuses[1]);

        $this->logger
            ->expects($this->exactly(3))
            ->method('info')
            ->with($this->callback(fn ($msg) => in_array($msg, ['Start', 'Updating', 'Finished'])));

        $this->logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->stringContains($expectedException->getMessage()));

        $this->jobRunner->run();
    }

    /**
     * @throws Throwable
     */
    public function test_run_thrown_null_from_notify_handler_is_logged(): void
    {
        $expectedException = new NotificationNotFoundException('Oops');

        $inProgressDocuments = [
            new GetNotifyStatus([
                'documentId' => 100,
                'notifyId' => 'ref-1',
            ]),
        ];

        $this->getInProgressDocumentsHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->willReturn($inProgressDocuments);

        $this->getNotifyStatusHandlerMock
            ->method('handle')
            ->willThrowException($expectedException);

        $this->updateDocumentStatusHandlerMock->expects(self::never())->method('handle');

        $this->logger
            ->expects($this->exactly(4))
            ->method('info')
            ->with($this->callback(fn ($msg) => in_array($msg, ['Start', 'Updating', $expectedException->getMessage(), 'Finished'])));

        $this->jobRunner->run();
    }

    /**
     * @throws Throwable
     */
    public function test_run_thrown_exception_from_api_update_is_logged_and_continues_success(): void
    {
        $expectedException = new Exception('Oops...');
        $inProgressDocuments = [
            new GetNotifyStatus([
                'documentId' => 100,
                'notifyId' => 'ref-1',
            ]),
            new GetNotifyStatus([
                'documentId' => 200,
                'notifyId' => 'ref-2',
            ]),
        ];
        $updateDocumentStatuses = [
            new UpdateDocumentStatus([
                'documentId' => 100,
                'notifyId' => 'ref-1',
                'notifyStatus' => 'status-1',
                'sendByMethod' => 'email',
                'recipientEmailAddress' => 'test@test.com'
            ]),
            new UpdateDocumentStatus([
                'documentId' => 200,
                'notifyId' => 'ref-2',
                'notifyStatus' => 'status-2',
                'sendByMethod' => 'email',
                'recipientEmailAddress' => 'test@test.com'
            ]),
        ];

        $this->getInProgressDocumentsHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->willReturn($inProgressDocuments);

        $this->getNotifyStatusHandlerMock
            ->expects(self::exactly(2))
            ->method('handle')
            ->willReturnCallback(fn ($value) => match ($value) {
                $inProgressDocuments[0] => $updateDocumentStatuses[0],
                $inProgressDocuments[1] => $updateDocumentStatuses[1],
                default => throw new RuntimeException('Mock did not expect value ' . print_r($value, true))
            });

        // Throw an exception on the first execution but expect flow to continue
        $this->updateDocumentStatusHandlerMock
            ->expects(self::exactly(2))
            ->method('handle')
            ->willReturnCallback(fn ($value) => match ($value) {
                $updateDocumentStatuses[0] => throw $expectedException,
                $updateDocumentStatuses[1] => null,
                default => throw new RuntimeException('Mock did not expect value ' . print_r($value, true))
            });

        $this->logger
            ->expects($this->exactly(3))
            ->method('info')
            ->with($this->callback(fn ($msg) => in_array($msg, ['Start', 'Updating', 'Finished'])));

        $this->logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->stringContains($expectedException->getMessage()));

        $this->jobRunner->run();
    }
}
