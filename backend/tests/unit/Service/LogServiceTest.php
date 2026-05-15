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

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    // --- streamInit ---

    public function testStreamInitReplacesLoggerWithMonologInstance(): void
    {
        $this->kernel->method('getProjectDir')->willReturn('/tmp');
        $this->kernel->method('getLogDir')->willReturn('/tmp/var/log');

        $logService = new LogService($this->kernel, $this->logger);
        $logService->streamInit();

        $prop = new \ReflectionProperty(LogService::class, 'logger');
        $prop->setAccessible(true);
        $newLogger = $prop->getValue($logService);

        $this->assertInstanceOf(Logger::class, $newLogger);
        $this->assertCount(1, $newLogger->getHandlers());
        $this->assertInstanceOf(StreamHandler::class, $newLogger->getHandlers()[0]);
    }

    // --- logException ---
    // streamInit() replaces $this->logger with a new Logger instance, so the mock logger
    // would be lost. We partial-mock streamInit() to a no-op so the mock logger stays active.

    private function makeLogService(): LogService
    {
        $logService = $this->getMockBuilder(LogService::class)
            ->setConstructorArgs([$this->kernel, $this->logger])
            ->onlyMethods(['streamInit'])
            ->getMock();

        $logService->method('streamInit');

        return $logService;
    }

    public function testLogExceptionCallsErrorWithExceptionMessage(): void
    {
        $logService = $this->makeLogService();
        $exception = new \RuntimeException('something went wrong');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('something went wrong'));

        $logService->logException($exception);
    }

    public function testLogExceptionMessageContainsTimestamp(): void
    {
        $logService = $this->makeLogService();
        $exception = new \RuntimeException('error');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->matchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/'));

        $logService->logException($exception);
    }

    public function testLogExceptionCallsStreamInitBeforeLogging(): void
    {
        $logService = $this->getMockBuilder(LogService::class)
            ->setConstructorArgs([$this->kernel, $this->logger])
            ->onlyMethods(['streamInit'])
            ->getMock();

        $logService->expects($this->once())->method('streamInit');
        $this->logger->method('error');

        $logService->logException(new \RuntimeException('test'));
    }

    public function testLogExceptionMessageContainsCallerClassName(): void
    {
        $logService = $this->makeLogService();
        $exception = new \RuntimeException('caller test');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('LogServiceTest'));

        $logService->logException($exception);
    }
}
