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
        $response = $this->guzzleClient->get($this->endpointUri, ['headers' => $this->jwtAuthenticator->buildHeaders()]);
        $documents = json_decode($response->getBody()->getContents(), true);
        $results = [];

        foreach ($documents as $doc) {
            var_dump($doc);
            $results[] = new GetNotifyStatus([
                'documentId' => $doc['id'],
                'notifyId' => $doc['notifyId'],
                //if I change this to $doc[notifyId] so it matches the group value being set in CorrespondenceController - works buyt functional test busts - need to amend
                //this to be NotifyId as this is the ID being set - need to change these in the tests then - once done
            ]);
        }

        return $results;
    }
}
