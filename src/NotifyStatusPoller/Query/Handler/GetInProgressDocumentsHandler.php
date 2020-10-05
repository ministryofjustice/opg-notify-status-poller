<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Query\Handler;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use NotifyStatusPoller\Authentication\JwtAuthentication;
use NotifyStatusPoller\Exception\AggregateValidationException;
use NotifyStatusPoller\Query\Model\GetNotifyStatus;

class GetInProgressDocumentsHandler
{
    private GuzzleClient $guzzleClient;
    private string $endpointUri;
    private JwtAuthentication $jwtAuthenticator;

    public function __construct(GuzzleClient $guzzleClient, string $endpointUri, JwtAuthentication $jwtAuthenticator)
    {
        $this->guzzleClient = $guzzleClient;
        $this->endpointUri = $endpointUri;
        $this->jwtAuthenticator = $jwtAuthenticator;
    }

    /**
     * @return GetNotifyStatus[]
     * @throws GuzzleException
     * @throws AggregateValidationException
     *
     * See https://github.com/sebastianbergmann/phpunit/issues/4297
     * PHPUnit is deprecating self::at and we need a way to trigger an exception.
     * Therefore this method doesn't have a return type explicitly declared.
     */
    public function handle()
    {
        var_dump($this->jwtAuthenticator->buildHeaders());
        $response = $this->guzzleClient->get($this->endpointUri, ['headers' => $this->jwtAuthenticator->buildHeaders()]);
        $documents = json_decode($response->getBody()->getContents(), true);
        $results = [];

        foreach ($documents as $doc) {
            $results[] = new GetNotifyStatus([
                'documentId' => $doc['id'],
                'notifyId' => $doc['notifySendId'],
            ]);
        }

        return $results;
    }
}
