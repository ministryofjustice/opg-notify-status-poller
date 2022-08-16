<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Command\Model;

use NotifyStatusPoller\Exception\AggregateValidationException;

class UpdateDocumentStatus
{
    protected int $documentId;
    protected string $notifyId;
    protected string $notifyStatus;
    protected string $sendByMethod;

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

        if (empty($data['sendByMethod'])) {
            AggregateValidationException::addError('Data doesn\'t contain a sendByMethod');
        }

        AggregateValidationException::checkAndThrow();

        $this->documentId = (int)$data['documentId'];
        $this->notifyId = $data['notifyId'];
        $this->notifyStatus = $data['notifyStatus'];
        $this->sendByMethod = $data['sendByMethod'];
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

    public function getSendByMethod(): string
    {
        return $this->sendByMethod;
    }
}
