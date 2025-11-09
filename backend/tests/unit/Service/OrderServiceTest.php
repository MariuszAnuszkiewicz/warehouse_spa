<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Stock;
use App\Repository\ProductRepository;
use App\Repository\StockRepository;
use App\Service\OrderService;
use App\Service\StockService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\TestCase;

class OrderServiceTest extends TestCase
{
    private $entityManager;
    private $productRepository;
    private $stockRepository;
    private $stockService;
    private $orderService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->stockRepository = $this->createMock(StockRepository::class);
        $this->stockService = $this->createMock(StockService::class);

        $this->orderService = new OrderService(
            $this->entityManager,
            $this->productRepository,
            $this->stockRepository,
            $this->stockService
        );
    }

    public function testCreateOrderSuccessfully(): void
    {
        $data = [
            (object)[
                'id' => 1,
                'name' => 'Test Product',
                'quantity' => 3,
                'isPick' => true,
                'note' => 'Urgent',
            ]
        ];

        $request = new Request([], [], [], [], [], [], json_encode($data));

        $datetime = new \DateTime(date('Y-m-d H:i:s'));

        $stock = (new Stock())
            ->setProductName('Test Product')
            ->setEan13('9370942873429')
            ->setQuantityInStock(23);

        $product = (new Product())
            ->setStock($stock)
            ->setCreatedAt($datetime);

        $stock = $this->createMock(Stock::class);
        $stock->method('getProduct')->willReturn($product);

        $this->stockRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['productName' => 'Test Product'])
            ->willReturn([$stock]);

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($product);

        $this->entityManager
            ->expects($this->atLeastOnce())
            ->method('persist')
            ->with($this->isInstanceOf(Order::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->stockService
            ->expects($this->once())
            ->method('reduceProductFromStock')
            ->with($data);

        $connection = $this->createMock(Connection::class);
        $statement = $this->createMock(Statement::class);

        $connection->method('prepare')->willReturn($statement);
        $this->entityManager->method('getConnection')->willReturn($connection);

        $this->orderService->create($request);
    }

    public function testCreateOrderProductsThrowsException(): void
    {
        $connection = $this->createMock(Connection::class);
        $this->entityManager->method('getConnection')->willReturn($connection);

        $this->expectNotToPerformAssertions();
        
        $statement = $this->createMock(Statement::class);
        $statement->method('executeStatement')->willThrowException(new \Exception('SQL error'));
        $connection->method('prepare')->willReturn($statement);

        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(1);

        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(2);

        $order->method('getProducts')->willReturn(new ArrayCollection([$product]));

        $this->orderService->createOrderProducts([$order]);
    }
}