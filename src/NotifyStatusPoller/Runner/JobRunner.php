<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Runner;

use NotifyStatusPoller\Command\Handler\UpdateDocumentStatusHandler;
use NotifyStatusPoller\Exception\NotificationNotFoundException;
use NotifyStatusPoller\Logging\Context;
use NotifyStatusPoller\Query\Handler\GetInProgressDocumentsHandler;
use NotifyStatusPoller\Query\Handler\GetNotifyStatusHandler;
use Psr\Log\LoggerInterface;
use Throwable;

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

        try {
            $inProgressResults = $this->getInProgressDocumentsHandler->handle();
        } catch (Throwable $e) {
            $this->logger
                ->critical(
                    (string)$e,
                    ['trace' => $e->getTraceAsString(), 'context' => Context::NOTIFY_POLLER]
                );

            return;
        }

        $this->logger->info('Updating', ['count' => count($inProgressResults), 'context' => Context::NOTIFY_POLLER]);

        $updatedCount = 0;
        $notifyStatusCounts = [];

        foreach ($inProgressResults as $getNotifyStatus) {
            try {
                $updateDocumentStatus = $this->getNotifyStatusHandler->handle($getNotifyStatus);
                $this->updateDocumentStatusHandler->handle($updateDocumentStatus);

                $updatedCount++;

                if (array_key_exists($updateDocumentStatus->getNotifyStatus(), $notifyStatusCounts)) {
                    $notifyStatusCounts[$updateDocumentStatus->getNotifyStatus()]++;
                } else {
                    $notifyStatusCounts[$updateDocumentStatus->getNotifyStatus()] = 1;
                }
            } catch (NotificationNotFoundException $e) {
                $this->logger
                    ->info(
                        $e->getMessage(),
                        ['context' => Context::NOTIFY_POLLER]
                    );
            } catch (Throwable $e) {
                $this->logger
                    ->critical(
                        (string)$e,
                        ['trace' => $e->getTraceAsString(), 'context' => Context::NOTIFY_POLLER]
                    );
            }
        }

        $this->logger->info('Finished', ['count' => $updatedCount, 'notify_status_counts' => $notifyStatusCounts, 'context' => Context::NOTIFY_POLLER]);
    }
}
