<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use NotifyStatusPoller\Logging\Context;
use NotifyStatusPoller\Runner\JobRunner;

/** @var LoggerInterface|null $psrLoggerAdapter */
$psrLoggerAdapter = null;
/** @var JobRunner|null $jobRunner */
$jobRunner = null;
$doRunLoop = false;

require_once __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/../src/bootstrap/config.php';
require_once __DIR__ . '/../src/bootstrap/logging.php';

try {
    require_once __DIR__ . '/../src/bootstrap/services.php';

    while ($doRunLoop) {
        $jobRunner->run();

        sleep($config['job_runner']['sleep_time']);
    }
} catch (Throwable $e) {
    exception_handler($e);
}

$psrLoggerAdapter->info('Finished', ['context' => Context::NOTIFY_POLLER]);

exit(0);
