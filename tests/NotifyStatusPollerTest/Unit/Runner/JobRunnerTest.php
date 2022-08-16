<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Runner;

use Alphagov\Notifications\Exception\NotifyException;
use NotifyStatusPoller\Exception\NotificationNotFoundException;
use Throwable;
use Exception;
use NotifyStatusPoller\Command\Model\UpdateDocumentStatus;
use NotifyStatusPoller\Query\Model\GetNotifyStatus;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;
use PHPUnit\Framework\TestCase;
use NotifyStatusPoller\Command\Handler\UpdateDocumentStatusHandler;
use NotifyStatusPoller\Query\Handler\GetInProgressDocumentsHandler;
use NotifyStatusPoller\Query\Handler\GetNotifyStatusHandler;
use NotifyStatusPoller\Runner\JobRunner;

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
            ]),
            new UpdateDocumentStatus([
                'documentId' => 200,
                'notifyId' => 'ref-2',
                'notifyStatus' => 'status-2',
                'sendByMethod' => 'email',
            ]),
        ];

        $this->getInProgressDocumentsHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->willReturn($inProgressDocuments);

        $this->getNotifyStatusHandlerMock
            ->expects(self::exactly(2))
            ->method('handle')
            ->withConsecutive([$inProgressDocuments[0]], [$inProgressDocuments[1]])
            ->willReturnOnConsecutiveCalls($updateDocumentStatuses[0], $updateDocumentStatuses[1]);

        $this->updateDocumentStatusHandlerMock
            ->expects(self::exactly(2))
            ->method('handle')
            ->withConsecutive([$updateDocumentStatuses[0]], [$updateDocumentStatuses[1]]);

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
            ]),
            new UpdateDocumentStatus([
                'documentId' => 200,
                'notifyId' => 'ref-2',
                'notifyStatus' => 'status-2',
                'sendByMethod' => 'email',
            ]),
        ];

        $this->getInProgressDocumentsHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->willReturn($inProgressDocuments);

        $this->getNotifyStatusHandlerMock
            ->expects(self::exactly(2))
            ->method('handle')
            ->withConsecutive([$inProgressDocuments[0]], [$inProgressDocuments[1]])
            ->willReturnOnConsecutiveCalls(self::throwException($expectedException), $updateDocumentStatuses[1]);

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
            ]),
            new UpdateDocumentStatus([
                'documentId' => 200,
                'notifyId' => 'ref-2',
                'notifyStatus' => 'status-2',
                'sendByMethod' => 'email',
            ]),
        ];

        $this->getInProgressDocumentsHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->willReturn($inProgressDocuments);

        $this->getNotifyStatusHandlerMock
            ->expects(self::exactly(2))
            ->method('handle')
            ->withConsecutive([$inProgressDocuments[0]], [$inProgressDocuments[1]])
            ->willReturnOnConsecutiveCalls($updateDocumentStatuses[0], $updateDocumentStatuses[1]);

        // Throw an exception on the first execution but expect flow to continue
        $this->updateDocumentStatusHandlerMock
            ->expects(self::exactly(2))
            ->method('handle')
            ->withConsecutive([$updateDocumentStatuses[0]], [$updateDocumentStatuses[1]])
            // PHPUnit is deprecating self::at, but we need to throw an exception on the first call.
            // Current workaround, see: https://github.com/sebastianbergmann/phpunit/issues/4297
            ->willReturnOnConsecutiveCalls(self::throwException($expectedException), null)
        ;

        $this->jobRunner->run();

        self::assertTrue(
            $this->logger->hasCriticalThatContains($expectedException->getMessage()),
            var_export($this->logger->records, true)
        );
        self::assertCount(1, $this->logger->recordsByLevel[LogLevel::CRITICAL]);
    }
}
