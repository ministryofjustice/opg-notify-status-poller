<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Runner;

use Exception;
use NotifyStatusPoller\Command\Handler\UpdateDocumentStatusHandler;
use NotifyStatusPoller\Command\Model\UpdateDocumentStatus;
use NotifyStatusPoller\Exception\NotificationNotFoundException;
use NotifyStatusPoller\Query\Handler\GetInProgressDocumentsHandler;
use NotifyStatusPoller\Query\Handler\GetNotifyStatusHandler;
use NotifyStatusPoller\Query\Model\GetNotifyStatus;
use NotifyStatusPoller\Runner\JobRunner;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;
use RuntimeException;
use Throwable;

class JobRunnerTest extends TestCase
{
    private JobRunner $jobRunner;
    private TestLogger $logger;
    private GetNotifyStatusHandler $getNotifyStatusHandlerMock;
    private GetInProgressDocumentsHandler $getInProgressDocumentsHandlerMock;
    private UpdateDocumentStatusHandler $updateDocumentStatusHandlerMock;

    public function setUp(): void
    {
        $this->getInProgressDocumentsHandlerMock = $this->createMock(GetInProgressDocumentsHandler::class);
        $this->getNotifyStatusHandlerMock = $this->createMock(GetNotifyStatusHandler::class);
        $this->updateDocumentStatusHandlerMock = $this->createMock(UpdateDocumentStatusHandler::class);
        $this->logger = new TestLogger();
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
            ->willReturnCallback(fn ($value) => match($value)
                {
                    $inProgressDocuments[0] => $updateDocumentStatuses[0],
                    $inProgressDocuments[1] => $updateDocumentStatuses[1],
                    default => throw new \LogicException()
                }
            );

        $this->updateDocumentStatusHandlerMock
            ->expects(self::exactly(2))
            ->method('handle')
            ->willReturnCallback(fn ($value) => match($value)
                {
                    $updateDocumentStatuses[0], $updateDocumentStatuses[1] => null,
                    default => throw new \LogicException()
                }
            );

        $this->jobRunner->run();

        self::assertFalse($this->logger->hasCriticalRecords(), var_export($this->logger->records, true));
        self::assertTrue($this->logger->hasInfoThatContains('Start'));
        self::assertTrue($this->logger->hasInfoThatContains('Finished'));
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

        $this->jobRunner->run();

        self::assertCount(1, $this->logger->recordsByLevel[LogLevel::CRITICAL]);
        self::assertTrue($this->logger->hasInfoThatContains('Start'));
        self::assertFalse($this->logger->hasInfoThatContains('Finished'));
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
            ->willReturnCallback(fn ($value) => match($value)
                {
                    $inProgressDocuments[0] => throw $expectedException,
                    $inProgressDocuments[1] => $updateDocumentStatuses[1],
                    default => throw new RuntimeException('Mock did not expect value ' . print_r($value, true))
                }
            );

        $this->updateDocumentStatusHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with($updateDocumentStatuses[1]);

        $this->jobRunner->run();

        self::assertTrue(
            $this->logger->hasCriticalThatContains($expectedException->getMessage()),
            var_export($this->logger->records, true)
        );

        self::assertCount(1, $this->logger->recordsByLevel[LogLevel::CRITICAL]);
        self::assertTrue($this->logger->hasInfoThatContains('Start'));
        self::assertTrue($this->logger->hasInfoThatContains('Finished'));
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

        $this->jobRunner->run();

        self::assertTrue($this->logger->hasInfoThatContains('Start'));
        self::assertTrue($this->logger->hasInfoThatContains('Updating'));
        self::assertTrue($this->logger->hasInfoThatContains($expectedException->getMessage()));
        self::assertTrue($this->logger->hasInfoThatContains('Finished'));
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

        $this->jobRunner->run();

        self::assertTrue(
            $this->logger->hasCriticalThatContains($expectedException->getMessage()),
            'Expected exception "' . $expectedException->getMessage() . '" was not logged: ' . var_export($this->logger->records, true)
        );
        self::assertCount(1, $this->logger->recordsByLevel[LogLevel::CRITICAL]);
    }
}
