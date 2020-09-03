<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Mapper;

use UnexpectedValueException;

class NotifyStatus
{
    public const SIRIUS_REJECTED = 'rejected';
    public const SIRIUS_QUEUED = 'queued';
    public const SIRIUS_POSTING = 'posting';
    public const SIRIUS_POSTED = 'posted';

    public const PENDING_VIRUS_CHECK = 'pending-virus-check';
    public const VIRUS_SCAN_FAILED = 'virus-scan-failed';
    public const VALIDATION_FAILED = 'validation-failed';
    public const FAILED = 'failed';
    public const ACCEPTED = 'accepted';
    public const RECEIVED = 'received';

    public const STATUSES = [
        self::FAILED => self::SIRIUS_REJECTED,
        self::VIRUS_SCAN_FAILED => self::SIRIUS_REJECTED,
        self::VALIDATION_FAILED => self::SIRIUS_REJECTED,
        self::PENDING_VIRUS_CHECK => self::SIRIUS_QUEUED,
        self::ACCEPTED => self::SIRIUS_POSTING,
        self::RECEIVED => self::SIRIUS_POSTED,
    ];

    public function toSirius(string $notifyStatus): string
    {
        // NOTE the notifications documentation is unclear on the exact on the statuses for precompiled letters -
        // there's only 3 statuses which can't be right, and the ones listed
        // here https://docs.notifications.service.gov.uk/rest-api.html#status-precompiled-letter don't match up the
        // ones here https://docs.notifications.service.gov.uk/rest-api.html#get-the-status-of-one-message-response
        // Assumption is that statuses will be a combination of letter and precompiled letter statuses...
        if (!array_key_exists($notifyStatus, self::STATUSES)) {
            throw new UnexpectedValueException(sprintf('Unknown Notify status "%s"', $notifyStatus));
        }

        return self::STATUSES[$notifyStatus];
    }
}
