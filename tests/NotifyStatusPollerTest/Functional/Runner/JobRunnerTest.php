<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Functional\Runner;

use Alphagov\Notifications\Client as NotifyClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use NotifyStatusPoller\Authentication\JwtAuthenticator;
use NotifyStatusPoller\Command\Handler\UpdateDocumentStatusHandler;
use NotifyStatusPoller\Query\Handler\GetInProgressDocumentsHandler;
use NotifyStatusPoller\Query\Handler\GetNotifyStatusHandler;
use NotifyStatusPoller\Runner\JobRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Throwable;

class JobRunnerTest extends TestCase
{
    private HandlerStack $guzzleHandlerStack;
    private LoggerInterface&MockObject $logger;
    private JobRunner $jobRunner;

    public function setUp(): void
    {
        global $config,
        $notifyStatusMapper,
        $updateDocumentStatusHandler;

        parent::setUp();

        $this->guzzleHandlerStack = HandlerStack::create();
        $this->logger = $this->createMock(LoggerInterface::class);

        $guzzleClient = new GuzzleClient(['handler' => $this->guzzleHandlerStack]);
        $notifyClient = new NotifyClient([
            'apiKey' => $config['notify']['api_key'],
            'httpClient' => $guzzleClient,
            'baseUrl' => $config['notify']['base_url'],
        ]);
        $jwtAuthenticator = new JwtAuthenticator(
            $config['sirius']['jwt_secret'],
            $config['sirius']['api_user_email']
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

        $this->logger
            ->expects($this->exactly(3))
            ->method('info')
            ->willReturnCallback(function ($msg, $context = []) use ($expectedProcessedJobCount): void {
                $this->assertContains($msg, ['Start', 'Updating', 'Finished']);

                if ($msg === 'Updating' || $msg === 'Finished') {
                    $this->assertEquals($expectedProcessedJobCount, $context['count']);
                }

                return;
            });

        $this->jobRunner->run();
    }
}
