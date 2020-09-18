<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Query\Handler;

use Alphagov\Notifications\Client as NotifyClient;
use NotifyStatusPoller\Command\Model\UpdateDocumentStatus;
use NotifyStatusPoller\Query\Model\GetNotifyStatus;

class GetNotifyStatusHandler
{
    private NotifyClient $notifyClient;

    public function __construct(NotifyClient $notifyClient)
    {
        $this->notifyClient = $notifyClient;
    }

    /**
     * @param GetNotifyStatus $query
     * @return UpdateDocumentStatus
     *
     * See https://github.com/sebastianbergmann/phpunit/issues/4297
     * PHPUnit is deprecating self::at and we need a way to trigger an exception.
     * Therefore this method doesn't have a return type explicitly declared.
     */
    public function handle(GetNotifyStatus $query)
    {
        $response = $this->notifyClient->getNotification($query->getNotifyId());

        return new UpdateDocumentStatus([
            'documentId' => $query->getDocumentId(),
            'notifyId' => $query->getNotifyId(),
            'notifyStatus' => $response['status'],
        ]);
    }
}
