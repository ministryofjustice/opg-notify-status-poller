<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Command\Model;

use NotifyStatusPoller\Exception\AggregateValidationException;

class UpdateDocumentStatus
{
    protected int $documentId;
    protected string $notifyId;
    protected string $notifyStatus;

    /**
     * @param array<string,mixed> $data
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

        if (empty($data['notifyStatus'])) {
            AggregateValidationException::addError('Data doesn\'t contain a notifyStatus');
        }

        AggregateValidationException::checkAndThrow();

        $this->documentId = (int)$data['documentId'];
        $this->notifyId = $data['notifyId'];
        $this->notifyStatus = $data['notifyStatus'];
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    public function getNotifyId(): string
    {
        return $this->notifyId;
    }

    public function getNotifyStatus(): string
    {
        return $this->notifyStatus;
    }
}
