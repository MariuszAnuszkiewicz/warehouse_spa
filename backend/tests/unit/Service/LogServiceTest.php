<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\LogService;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class LogServiceTest extends TestCase
{
    private KernelInterface $kernel;
    private LoggerInterface $logger;
    private LogService $logService;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->logService = new LogService($this->kernel, $this->logger);
    }

    public function testStreamInitSetsUpLoggerCorrectly(): void
    {
        $this->kernel
            ->method('getProjectDir')
            ->willReturn('/app');
        $this->kernel
            ->method('getLogDir')
            ->willReturn('/app/var/log');

        $this->logService->streamInit();

        $ref = new \ReflectionClass($this->logService);
        $loggerProp = $ref->getProperty('logger');
        $loggerProp->setAccessible(true);
        $logger = $loggerProp->getValue($this->logService);

        $this->assertInstanceOf(Logger::class, $logger);
        $handlers = $logger->getHandlers();
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
    }

    public function testLogExceptionWritesErrorToLogger(): void
    {
        $textOfException = 'Something went wrong';

        $this->kernel
            ->method('getProjectDir')
            ->willReturn('/tmp');
        $this->kernel
            ->method('getLogDir')
            ->willReturn('/tmp/var/log');

        $exception = new \Exception($textOfException);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains($textOfException));

        $this->logger->error('Something went wrong: ' . $exception->getMessage(), [
            'exception' => $exception,
        ]);

        $this->logService->logException($exception);
    }
}



