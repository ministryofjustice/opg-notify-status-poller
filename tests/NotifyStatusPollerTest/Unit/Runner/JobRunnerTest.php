<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Runner;

use Exception;
use NotifyStatusPoller\Command\Model\UpdateDocumentStatus;
use NotifyStatusPoller\Query\Model\GetNotifyStatus;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;
use Throwable;
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
            ]),
            new UpdateDocumentStatus([
                'documentId' => 200,
                'notifyId' => 'ref-2',
                'notifyStatus' => 'status-2',
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
    }

    /**
     * @throws Throwable
     */
    public function test_run_thrown_exception_is_logged_and_continues(): void
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
            ]),
            new UpdateDocumentStatus([
                'documentId' => 200,
                'notifyId' => 'ref-2',
                'notifyStatus' => 'status-2',
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
