<?php

declare(strict_types=1);

namespace App\Service;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class LogService
{
    const LOG_FILE_PATH = '/var/log/dev.log';

    public function __construct(
        private KernelInterface $kernel,
        private LoggerInterface $logger
    ) {}

    public function streamInit(): void
    {
        $this->kernel->getLogDir();
        $this->logger = new Logger('Errors: ');
        $handler = new StreamHandler($this->kernel->getProjectDir() . self::LOG_FILE_PATH, Logger::ERROR);
        $this->logger->pushHandler($handler);
    }

    public function logException(\Throwable $error): void
    {
        $trace = $error->getTrace()[0] ?? [];
        $controllerName = isset($trace['class']) ? substr(strrchr($trace['class'], "\\"), 1) : 'unknown';
        $methodName = $trace['function'] ?? 'unknown';

        $message = sprintf(
            '[%s] Exception in %s::%s - %s',
            date('Y-m-d H:i:s'),
            $controllerName,
            $methodName,
            $error->getMessage()
        );

        $this->streamInit();
        $this->logger->error($message);
    }
}