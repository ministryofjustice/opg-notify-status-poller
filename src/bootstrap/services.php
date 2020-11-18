<?php

declare(strict_types=1);

use NotifyStatusPoller\Authentication\JwtAuthenticator;
use Psr\Log\LoggerInterface;
use Alphagov\Notifications\Client;
use GuzzleHttp\Client as GuzzleClient;
use NotifyStatusPoller\Command\Handler\UpdateDocumentStatusHandler;
use NotifyStatusPoller\Query\Handler\GetInProgressDocumentsHandler;
use NotifyStatusPoller\Query\Handler\GetNotifyStatusHandler;
use NotifyStatusPoller\Runner\JobRunner;
use NotifyStatusPoller\Mapper\NotifyStatus;

// Make IDEs not show errors...
/** @var array<mixed> $config */
/** @var LoggerInterface $psrLoggerAdapter */

if (empty($config)) {
    throw new InvalidArgumentException('No config found');
}

$notifyStatusMapper = new NotifyStatus();
$guzzleClient = new GuzzleClient();
$notifyClient = new Client(
    [
        'apiKey' => $config['notify']['api_key'],
        'httpClient' => $guzzleClient,
        'baseUrl' => $config['notify']['base_url'],
    ]
);

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

$jobRunner = new JobRunner(
    $getInProgressDocumentsHandler,
    $getNotifyStatusHandler,
    $updateDocumentStatusHandler,
    $psrLoggerAdapter
);
