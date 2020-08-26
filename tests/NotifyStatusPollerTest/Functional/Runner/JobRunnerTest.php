<?php

namespace NotifyStatusPollerTest\Functional\Runner;

use NotifyStatusPoller\Runner\JobRunner;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class JobRunnerTest extends TestCase
{
    private TestLogger $logger;
    private JobRunner $jobRunner;

    public function setUp(): void
    {
        $this->logger = new TestLogger();
        $this->jobRunner = new JobRunner(
            $this->logger
        );
    }

    public function testRunSuccess()
    {
        $this->jobRunner->run();
        self::assertTrue($this->logger->hasInfoThatContains('Start...'));
    }
}
