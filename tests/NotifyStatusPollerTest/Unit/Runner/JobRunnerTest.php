<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Runner;

use Exception;
use NotifyStatusPoller\Command\Handler\UpdateDocumentStatusHandler;
use NotifyStatusPoller\Query\Handler\GetInProgressDocumentsHandler;
use NotifyStatusPoller\Query\Handler\GetNotifyStatusHandler;
use NotifyStatusPoller\Runner\JobRunner;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class JobRunnerTest extends TestCase
{
    private JobRunner $jobRunner;
    private LoggerInterface $loggerMock;
    private GetNotifyStatusHandler $getNotifyStatusHandlerMock;
    private GetInProgressDocumentsHandler $getInProgressDocumentsHandler;
    private UpdateDocumentStatusHandler $updateDocumentStatusHandler;

    public function setUp(): void
    {
        $this->getInProgressDocumentsHandler = $this->createMock(GetInProgressDocumentsHandler::class);
        $this->getNotifyStatusHandlerMock = $this->createMock(GetNotifyStatusHandler::class);
        $this->updateDocumentStatusHandler = $this->createMock(UpdateDocumentStatusHandler::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->jobRunner = new JobRunner(
            $this->getInProgressDocumentsHandler,
            $this->getNotifyStatusHandlerMock,
            $this->updateDocumentStatusHandler,
            $this->loggerMock
        );
    }

    /**
     * @throws Exception
     */
    public function testRunSuccess(): void
    {
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('info')
            ->withConsecutive(
                ['Start'],
                ['Finish']
            );

        $this->jobRunner->run();
    }
}
