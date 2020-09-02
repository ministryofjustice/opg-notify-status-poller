<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Runner;

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

    public function run(): void
    {
        $logExtras = ['context' => Context::NOTIFY_POLLER];

        $this->logger->info('Start', $logExtras);

        try {
            $inProgressResults = $this->getInProgressDocumentsHandler->handle();
        } catch (Throwable $e) {
            $logExtras = array_merge($logExtras, ['error' => (string)$e, 'trace' => $e->getTraceAsString()]);
            $this->logger->critical('Error querying documents', $logExtras);

            return;
        }

        foreach ($inProgressResults as $getNotifyStatus) {
            try {
                $updateDocumentStatus = $this->getNotifyStatusHandler->handle($getNotifyStatus);

                $this->updateDocumentStatusHandler->handle($updateDocumentStatus);
            } catch (Throwable $e) {
                $logExtras = array_merge($logExtras, ['error' => (string)$e, 'trace' => $e->getTraceAsString()]);
                $this->logger->critical('Error updating status', $logExtras);
            }
        }

        $this->logger->info('Finish', $logExtras);
    }
}
