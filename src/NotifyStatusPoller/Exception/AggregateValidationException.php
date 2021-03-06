<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Exception;

use InvalidArgumentException;

class AggregateValidationException extends InvalidArgumentException
{
    private static ?self $instance = null;

    /**
     * @var array<string>
     */
    private array $errors = [];

    private function __construct()
    {
        parent::__construct();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function addError(string $message): void
    {
        $instance = self::getInstance();

        $instance->errors[] = $message;
        $instance->message = implode(', ', $instance->errors);
    }

    public static function hasError(): bool
    {
        return count(self::getInstance()->errors) > 0;
    }

    public static function clearInstance(): void
    {
        self::$instance = null;
    }

    public static function checkAndThrow(): void
    {
        if (self::hasError()) {
            throw self::getInstance();
        }
    }
}
