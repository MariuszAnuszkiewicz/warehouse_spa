<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\ApiStockController;
use App\Entity\Stock;
use App\Repository\StockRepository;
use App\Service\LogService;
use App\Service\SerializeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiStockControllerTest extends TestCase
{
    private $controller;
    private $mockLogService;
    private $mockSerializeService;
    private $mockEntityManager;
    private $mockStockRepository;

    protected function setUp(): void
    {
        $this->mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->mockLogService = $this->createMock(LogService::class);
        $this->mockSerializeService = $this->createMock(SerializeService::class);
        $this->mockStockRepository = $this->createMock(StockRepository::class);

        $this->controller = new ApiStockController(
            $this->mockLogService,
            $this->mockSerializeService
        );

        $this->controller->setContainer(new Container());
    }

    public function testIndexReturnsSerializedStocks(): void
    {
        $stocks = [['id' => 1, 'name' => 'Test_Product', 'ean' => '9370942873429']];
        $serialized = [['id' => 1, 'name' => 'Test_Product (serialized)', 'ean' => '9370942873429']];
        $serializedJson = json_encode($serialized);

        $this->mockStockRepository
            ->method('findAll')
            ->willReturn($stocks);

        $this->mockSerializeService
            ->method('dataSerialize')
            ->with($stocks)
            ->willReturn($serializedJson);

        $response = $this->controller->index($this->mockStockRepository);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('stocks', $data);
        $this->assertEquals($serializedJson, $data['stocks']);
    }

    public function testIndexThrowsExceptionWhenNoStocks(): void
    {
        $this->mockStockRepository
            ->method('findAll')
            ->willReturn([]);

        $this->mockLogService
            ->expects($this->once())
            ->method('logException');

        $response = $this->controller->index($this->mockStockRepository);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['error']);
        $this->assertEquals('No stocks found.', $data['message']);
    }
}
