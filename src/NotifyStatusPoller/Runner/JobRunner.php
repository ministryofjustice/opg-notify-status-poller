<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Runner;

use Psr\Log\LoggerInterface;
use NotifyStatusPoller\Logging\Context;

class JobRunner
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function run(): void
    {
        $logExtras = ['context' => Context::NOTIFY_POLLER];
        $this->logger->info('Start...', $logExtras);
    }
}
