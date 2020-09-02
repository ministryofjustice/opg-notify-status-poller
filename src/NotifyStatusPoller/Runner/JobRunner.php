<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Runner;

use NotifyStatusPoller\Query\Model\GetNotifyStatus;
use Throwable;
use Psr\Log\LoggerInterface;
use NotifyStatusPoller\Command\Handler\UpdateDocumentStatusHandler;
use NotifyStatusPoller\Query\Handler\GetInProgressDocumentsHandler;
use NotifyStatusPoller\Query\Handler\GetNotifyStatusHandler;
use NotifyStatusPoller\Logging\Context;

class JobRunner
{
    private LoggerInterface $logger;
    /**
     * @var GetInProgressDocumentsHandler
     */
    private GetInProgressDocumentsHandler $getInProgressDocumentsHandler;
    /**
     * @var GetNotifyStatusHandler
     */
    private GetNotifyStatusHandler $getNotifyStatusHandler;
    /**
     * @var UpdateDocumentStatusHandler
     */
    private UpdateDocumentStatusHandler $updateDocumentStatusHandler;

    public function __construct(
        GetInProgressDocumentsHandler $getInProgressDocumentsHandler,
        GetNotifyStatusHandler $getNotifyStatusHandler,
        UpdateDocumentStatusHandler $updateDocumentStatusHandler,
        LoggerInterface $logger
    ) {
        $this->getInProgressDocumentsHandler = $getInProgressDocumentsHandler;
        $this->getNotifyStatusHandler = $getNotifyStatusHandler;
        $this->updateDocumentStatusHandler = $updateDocumentStatusHandler;
        $this->logger = $logger;
    }

    /**
     * @throws Throwable
     */
    public function run(): void
    {
        $this->logger->info('Start', ['context' => Context::NOTIFY_POLLER]);

        $inProgressResults = $this->getInProgressDocumentsHandler->handle();

        foreach ($inProgressResults as $getNotifyStatus) {
            $this->updateStatus($getNotifyStatus);
        }
    }

    /**
     * @param GetNotifyStatus $getNotifyStatus
     */
    private function updateStatus(GetNotifyStatus $getNotifyStatus): void
    {
        try {
            $updateDocumentStatus = $this->getNotifyStatusHandler->handle($getNotifyStatus);
            $this->updateDocumentStatusHandler->handle($updateDocumentStatus);
        } catch (Throwable $e) {
            $this->logger
                ->critical(
                    (string)$e,
                    ['trace' => $e->getTraceAsString(), 'context' => Context::NOTIFY_POLLER]
                );
        }
    }
}
