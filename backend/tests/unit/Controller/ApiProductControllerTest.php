<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\ApiProductController;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\LogService;
use App\Service\SerializeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiProductControllerTest extends TestCase
{
    private $controller;
    private $mockLogService;
    private $mockSerializeService;
    private $mockEntityManager;
    private $mockProductRepository;

    protected function setUp(): void
    {
        $this->mockEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->mockLogService = $this->createMock(LogService::class);
        $this->mockSerializeService = $this->createMock(SerializeService::class);
        $this->mockProductRepository = $this->createMock(ProductRepository::class);

        $this->controller = new ApiProductController(
            $this->mockLogService,
            $this->mockSerializeService
        );

        $this->controller->setContainer(new Container());
    }

    public function testIndexReturnsSerializedProducts(): void
    {
        $products = [['stock_id' => 1]];
        $serializedJson = json_encode($products);

        $this->mockProductRepository
            ->method('productsWithRelationships')
            ->willReturn($products);

        $this->mockSerializeService
            ->method('dataSerialize')
            ->with($products)
            ->willReturn($serializedJson);

        $response = $this->controller->index($this->mockProductRepository);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('products', $data);
        $this->assertEquals($serializedJson, $data['products']);
    }

    public function testIndexThrowsExceptionWhenNoProducts(): void
    {
        $this->mockProductRepository
            ->method('productsWithRelationships')
            ->willReturn([]);

        $this->mockLogService
            ->expects($this->once())
            ->method('logException')
            ->with($this->isInstanceOf(\RuntimeException::class));

        $response = $this->controller->index($this->mockProductRepository);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['error']);
        $this->assertEquals('The products could not be found.', $data['message']);
    }

    public function testShowReturnsSerializedProduct(): void
    {
        $product = ['id' => 1, 'name' => 'Test Product'];
        $serializedJson = json_encode($product);

        $this->mockProductRepository
            ->method('productByIdWithRelationships')
            ->with(1)
            ->willReturn($product);

        $this->mockSerializeService
            ->method('dataSerialize')
            ->with($product)
            ->willReturn($serializedJson);

        $response = $this->controller->show($this->mockProductRepository, 1);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['product' => $serializedJson], $data);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testShowReturnsErrorIfProductNotFound(): void
    {
        $this->mockProductRepository
            ->method('productByIdWithRelationships')
            ->with(999)
            ->willReturn([]);

        $this->mockLogService
            ->expects($this->once())
            ->method('logException')
            ->with($this->isInstanceOf(\Throwable::class));

        $response = $this->controller->show($this->mockProductRepository, 999);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertStringContainsString('could not be found', $data['message']);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}