<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Functional\Runner;

use Alphagov\Notifications\Client as NotifyClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use NotifyStatusPoller\Authentication\JwtAuthentication;
use NotifyStatusPoller\Command\Handler\UpdateDocumentStatusHandler;
use NotifyStatusPoller\Query\Handler\GetInProgressDocumentsHandler;
use NotifyStatusPoller\Query\Handler\GetNotifyStatusHandler;
use NotifyStatusPoller\Runner\JobRunner;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;
use Throwable;

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
        $jwtAuthenticator = new JwtAuthentication(
            $config['sirius']['jwt_secret'],
            $config['sirius']['session_data']
        );
        $getInProgressDocumentsHandler = new GetInProgressDocumentsHandler(
            $guzzleClient,
            $config['sirius']['in_progress_documents_endpoint'],
            $jwtAuthenticator
        );
        $getNotifyStatusHandler = new GetNotifyStatusHandler($notifyClient);
        $updateDocumentStatusHandler = new UpdateDocumentStatusHandler(
            $notifyStatusMapper,
            $guzzleClient,
            $jwtAuthenticator,
            $config['sirius']['update_status_endpoint']
        );

        $this->jobRunner = new JobRunner(
            $getInProgressDocumentsHandler,
            $getNotifyStatusHandler,
            $updateDocumentStatusHandler,
            $this->logger
        );
    }

    /**
     * @throws Throwable
     */
    public function testRunSuccess(): void
    {
        $expectedProcessedJobCount = 3;
        $this->jobRunner->run();

        self::assertTrue($this->logger->hasInfoThatContains('Start'));
        self::assertTrue($this->logger->hasInfoThatContains('Updating'));
        self::assertTrue($this->logger->hasInfoThatContains('Finished'));

        $infoLogRecords =  $this->logger->recordsByLevel[LogLevel::INFO];
        self::assertEquals($expectedProcessedJobCount, $infoLogRecords[1]['context']['count']);
        self::assertEquals($expectedProcessedJobCount, $infoLogRecords[2]['context']['count']);

        self::assertArrayNotHasKey(LogLevel::CRITICAL, $this->logger->recordsByLevel);
    }
}
