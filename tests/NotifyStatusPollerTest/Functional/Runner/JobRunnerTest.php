<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Functional\Runner;

use Alphagov\Notifications\Client as NotifyClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use NotifyStatusPoller\Command\Handler\UpdateDocumentStatusHandler;
use NotifyStatusPoller\Query\Handler\GetInProgressDocumentsHandler;
use NotifyStatusPoller\Query\Handler\GetNotifyStatusHandler;
use NotifyStatusPoller\Runner\JobRunner;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;

class JobRunnerTest extends TestCase
{
    private HandlerStack $guzzleHandlerStack;
    private TestLogger $logger;
    private JobRunner $jobRunner;

    public function setUp(): void
    {
        global $config,
               $notifyStatusMapper,
               $updateDocumentStatusHandler;

        parent::setUp();

        $this->guzzleHandlerStack = HandlerStack::create();
        $this->logger = new TestLogger();

        $guzzleClient = new GuzzleClient(['handler' => $this->guzzleHandlerStack]);
        $notifyClient = new NotifyClient([
            'apiKey' => $config['notify']['api_key'],
            'httpClient' => $guzzleClient,
            'baseUrl' => $config['notify']['base_url'],
        ]);
        $getInProgressDocumentsHandler = new GetInProgressDocumentsHandler(
            $guzzleClient,
            $config['sirius']['in_progress_documents_endpoint']
        );
        $getNotifyStatusHandler = new GetNotifyStatusHandler($notifyClient);
        $updateDocumentStatusHandler = new UpdateDocumentStatusHandler(
            $notifyStatusMapper,
            $guzzleClient,
            $config['sirius']['update_status_endpoint']
        );

        $this->jobRunner = new JobRunner(
            $getInProgressDocumentsHandler,
            $getNotifyStatusHandler,
            $updateDocumentStatusHandler,
            $this->logger
        );
    }

    public function testRunSuccess()
    {
        $this->jobRunner->run();

        self::assertTrue($this->logger->hasInfoThatContains('Start'));
        self::assertArrayNotHasKey(LogLevel::CRITICAL, $this->logger->recordsByLevel);
    }
}
