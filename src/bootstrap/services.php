<?php

declare(strict_types=1);

use Alphagov\Notifications\Client;
use GuzzleHttp\Client as GuzzleClient;
use NotifyStatusPoller\Runner\JobRunner;
use NotifyStatusPoller\Mapper\NotifyStatus;
use Psr\Log\LoggerInterface;

// Make IDEs not show errors...
/** @var array<mixed> $config */
/** @var LoggerInterface $psrLoggerAdapter */

if (empty($config)) {
    throw new InvalidArgumentException('No config found');
}

$notifyGuzzleClient = new GuzzleClient();

$notifyClient = new Client(
    [
        'apiKey' => $config['notify']['api_key'],
        'httpClient' => $notifyGuzzleClient,
        'baseUrl' => $config['notify']['base_url'],
    ]
);

$guzzleClient = new GuzzleClient([]);

$jobRunner = new JobRunner($psrLoggerAdapter);
