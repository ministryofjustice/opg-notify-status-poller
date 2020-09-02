<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Query\Model;

use NotifyStatusPoller\Exception\AggregateValidationException;

class GetNotifyStatus
{
    protected int $documentId;
    protected string $notifyId;

    /**
     * GetNotifyStatus constructor.
     * @param array<string,string> $data
     */
    public function __construct(array $data)
    {
        AggregateValidationException::clearInstance();

        if (empty($data['documentId']) || !is_numeric($data['documentId'])) {
            AggregateValidationException::addError('Data doesn\'t contain a numeric documentId');
        }

        if (empty($data['notifyId'])) {
            AggregateValidationException::addError('Data doesn\'t contain a notifyId');
        }

        AggregateValidationException::checkAndThrow();

        $this->documentId = (int)$data['documentId'];
        $this->notifyId = $data['notifyId'];
    }

    public function getNotifyId(): string
    {
        return $this->notifyId;
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }
}
