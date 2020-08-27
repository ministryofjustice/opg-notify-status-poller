<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Runner;

use Exception;
use NotifyStatusPoller\Runner\JobRunner;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class JobRunnerTest extends TestCase
{
    private JobRunner $jobRunner;
    private LoggerInterface $loggerMock;

    public function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->jobRunner = new JobRunner(
            $this->loggerMock
        );
    }

    /**
     * @throws Exception
     */
    public function testRunSuccess(): void
    {
        $this->loggerMock->expects(self::once())->method('info')->with('Start...');

        $this->jobRunner->run();
    }
}
