<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\ApiStockController;
use App\Entity\Stock;
use App\Repository\StockRepository;
use App\Service\LogService;
use App\Service\SerializeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiStockControllerTest extends TestCase
{
    private ApiStockController $controller;
    private LogService $logService;
    private SerializeService $serializeService;
    private StockRepository $stockRepository;

    protected function setUp(): void
    {
        $this->logService       = $this->createMock(LogService::class);
        $this->serializeService = $this->createMock(SerializeService::class);
        $this->stockRepository  = $this->createMock(StockRepository::class);

        $this->controller = new ApiStockController(
            $this->logService,
            $this->serializeService
        );

        $this->controller->setContainer(new Container());
    }

    // --- index ---

    public function testIndexReturnsSerializedStocks(): void
    {
        $stocks = [new Stock(), new Stock()];
        $serialized = '[{"id":1},{"id":2}]';

        $this->stockRepository->method('findAll')->willReturn($stocks);
        $this->serializeService->method('dataSerialize')->with($stocks)->willReturn($serialized);

        $response = $this->controller->index($this->stockRepository);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('stocks', $data);
        $this->assertSame($serialized, $data['stocks']);
        $this->assertArrayNotHasKey('error', $data);
    }

    public function testIndexReturnsNotFoundWhenStocksEmpty(): void
    {
        $this->stockRepository->method('findAll')->willReturn([]);
        $this->logService->expects($this->once())->method('logException')
            ->with($this->isInstanceOf(\RuntimeException::class));

        $response = $this->controller->index($this->stockRepository);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('No stocks found.', $data['message']);
    }
}
