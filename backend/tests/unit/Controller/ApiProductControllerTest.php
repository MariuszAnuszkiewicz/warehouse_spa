<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\ApiProductController;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\LogService;
use App\Service\SerializeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiProductControllerTest extends TestCase
{
    private ApiProductController $controller;
    private LogService $logService;
    private SerializeService $serializeService;
    private ProductRepository $productRepository;

    protected function setUp(): void
    {
        $this->logService         = $this->createMock(LogService::class);
        $this->serializeService   = $this->createMock(SerializeService::class);
        $this->productRepository  = $this->createMock(ProductRepository::class);

        $this->controller = new ApiProductController(
            $this->logService,
            $this->serializeService
        );

        $this->controller->setContainer(new Container());
    }

    // --- index ---

    public function testIndexReturnsSerializedProducts(): void
    {
        $products = [new Product(), new Product()];
        $serialized = '[{"id":1},{"id":2}]';

        $this->productRepository->method('productsWithRelationships')->willReturn($products);
        $this->serializeService->method('dataSerialize')->with($products)->willReturn($serialized);

        $response = $this->controller->index($this->productRepository);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('products', $data);
        $this->assertSame($serialized, $data['products']);
        $this->assertArrayNotHasKey('error', $data);
    }

    public function testIndexReturnsNotFoundWhenProductsEmpty(): void
    {
        $this->productRepository->method('productsWithRelationships')->willReturn([]);
        $this->logService->expects($this->once())->method('logException')
            ->with($this->isInstanceOf(\RuntimeException::class));

        $response = $this->controller->index($this->productRepository);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('The products could not be found.', $data['message']);
    }

    // --- show ---

    public function testShowReturnsSerializedProduct(): void
    {
        $product = new Product();
        $serialized = '{"id":1}';

        $this->productRepository->method('productByIdWithRelationships')->with(1)->willReturn([$product]);
        $this->serializeService->method('dataSerialize')->with([$product])->willReturn($serialized);

        $response = $this->controller->show($this->productRepository, 1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame($serialized, $data['product']);
        $this->assertArrayNotHasKey('error', $data);
    }

    public function testShowReturnsNotFoundWhenProductMissing(): void
    {
        $this->productRepository->method('productByIdWithRelationships')->with(999)->willReturn([]);
        $this->logService->expects($this->once())->method('logException')
            ->with($this->isInstanceOf(\RuntimeException::class));

        $response = $this->controller->show($this->productRepository, 999);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertStringContainsString('999', $data['message']);
        $this->assertStringContainsString('could not be found', $data['message']);
    }
}
