<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Query\Handler;

use Alphagov\Notifications\Client as NotifyClient;
use Alphagov\Notifications\Exception\NotifyException;
use Exception;
use NotifyStatusPoller\Command\Model\UpdateDocumentStatus;
use NotifyStatusPoller\Exception\NotificationNotFoundException;
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
     * @throws NotificationNotFoundException
     *
     * See https://github.com/sebastianbergmann/phpunit/issues/4297
     * PHPUnit is deprecating self::at and we need a way to trigger an exception.
     * Therefore this method doesn't have a return type explicitly declared.
     */
    public function handle(GetNotifyStatus $query)
    {
        $response = $this->notifyClient->getNotification($query->getNotifyId());

        if (empty($response['status'])) {
            throw new NotificationNotFoundException("Notification not found for document ID: " . $query->getDocumentId());
        }

        return new UpdateDocumentStatus([
            'documentId' => $query->getDocumentId(),
            'notifyId' => $query->getNotifyId(),
            'notifyStatus' => $response['status'],
        ]);
    }
}
