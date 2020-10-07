<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Runner;

use Alphagov\Notifications\Exception\NotifyException;
use Exception;
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

        foreach ($inProgressResults as $getNotifyStatus) {
            try {
                $updateDocumentStatus = $this->getNotifyStatusHandler->handle($getNotifyStatus);
                $this->updateDocumentStatusHandler->handle($updateDocumentStatus);
                $updatedCount++;
            } catch (NotifyException $e) {
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

        $this->logger->info('Finished', ['count' => $updatedCount, 'context' => Context::NOTIFY_POLLER]);
    }
}
