<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Command\Handler;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use NotifyStatusPoller\Authentication\JwtAuthentication;
use NotifyStatusPoller\Command\Model\UpdateDocumentStatus;
use NotifyStatusPoller\Mapper\NotifyStatus;
use UnexpectedValueException;

class UpdateDocumentStatusHandler
{
    private NotifyStatus $notifyStatusMapper;
    private GuzzleClient $guzzleClient;
    private JwtAuthentication $jwtAuthenticator;
    private string $updateEndpointUrl;

    public function __construct(
        NotifyStatus $notifyStatusMapper,
        GuzzleClient $guzzleClient,
        JwtAuthentication $jwtAuthenticator,
        string $updateEndpointUrl
    ) {
        $this->notifyStatusMapper = $notifyStatusMapper;
        $this->guzzleClient = $guzzleClient;
        $this->jwtAuthenticator = $jwtAuthenticator;
        $this->updateEndpointUrl = $updateEndpointUrl;
    }

    /**
     * @param UpdateDocumentStatus $command
     * @return null
     * @throws GuzzleException
     */
    public function handle(UpdateDocumentStatus $command)
    {
        $payload = [
            'documentId' => $command->getDocumentId(),
            'notifySendId' => $command->getNotifyId(),
            'notifyStatus' => $this->notifyStatusMapper->toSirius($command->getNotifyStatus()),
        ];

        $guzzleResponse = $this->guzzleClient->put(
            $this->updateEndpointUrl,
            ['headers' => $this->jwtAuthenticator->buildHeaders(), 'json' => $payload ]
        );

        if ($guzzleResponse->getStatusCode() !== 204) {
            throw new UnexpectedValueException(
                sprintf('Expected status "%s" but received "%s"', 204, $guzzleResponse->getStatusCode())
            );
        }

        // See https://github.com/sebastianbergmann/phpunit/issues/4297
        // PHPUnit is deprecating self::at and we need a way to trigger an exception.
        // Replace with `void` if/when they implement a better replacement for `at`.
        return null;
    }
}
