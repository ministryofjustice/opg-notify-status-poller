<?php

declare(strict_types=1);

use Laminas\Log\Formatter\Json;
use Laminas\Log\Logger;
use Laminas\Log\PsrLoggerAdapter;
use Laminas\Log\Writer\Stream;
use NotifyStatusPoller\Logging\Context;

$doRunLoop = true;

// Setup logging
$formatter = new Json();
$logger = new Logger();
$writer = new Stream("php://stdout");
$writer->setFormatter($formatter);
$logger->addWriter($writer);
$psrLoggerAdapter = new PsrLoggerAdapter($logger);

// Set custom handlers
function shutdown_handler(): void
{
    global $psrLoggerAdapter, $doRunLoop;

    $psrLoggerAdapter->info("Stopping", ['context' => Context::NOTIFY_POLLER]);
    $doRunLoop = false;
}

function exception_handler(Throwable $e): void
{
    global $psrLoggerAdapter;

    $psrLoggerAdapter->critical(
        'Exception: ' . $e->getMessage(),
        [
            'context' => Context::NOTIFY_POLLER,
            'stacktrace' => $e->getTraceAsString(),
        ]
    );

    exit(1);
}

function error_handler(int $errno, string $errstr, string $errfile, int $errline): bool
{
    global $psrLoggerAdapter;

    $extras = [
        'context' => Context::NOTIFY_POLLER,
        'errorno' => $errno,
        'errfile' => $errfile,
        'errline' => $errline,
    ];

    switch ($errno) {
        case E_NOTICE:
        case E_WARNING:
        case E_USER_WARNING:
        case E_USER_NOTICE:
            $psrLoggerAdapter->warning('Error: ' . $errstr, $extras);
            break;

        case E_USER_ERROR:
            $psrLoggerAdapter->critical('Fatal Error: ' . $errstr, $extras);
            exit(1);

        default:
            $psrLoggerAdapter->critical('Unknown Error: ' . $errstr, $extras);
            exit(1);
    }

    return true;
}

pcntl_async_signals(true);
pcntl_signal(SIGINT, 'shutdown_handler');
pcntl_signal(SIGTERM, 'shutdown_handler');

set_error_handler('error_handler');
set_exception_handler('exception_handler');
