<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\Location\LocationDto;
use App\Dto\Order\OrderDto;
use App\Dto\Product\ProductDto;
use App\Entity\Location;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Stock;
use App\Repository\LocationRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\StockRepository;
use App\Service\OrderService;
use App\Service\StockService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class OrderServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private LocationRepository $locationRepository;
    private OrderRepository $orderRepository;
    private ProductRepository $productRepository;
    private StockRepository $stockRepository;
    private StockService $stockService;
    private SerializerInterface $serializer;
    private CacheInterface $cache;
    private Connection $connection;
    private OrderService $orderService;

    protected function setUp(): void
    {
        $this->entityManager    = $this->createMock(EntityManagerInterface::class);
        $this->locationRepository = $this->createMock(LocationRepository::class);
        $this->orderRepository  = $this->createMock(OrderRepository::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->stockRepository  = $this->createMock(StockRepository::class);
        $this->stockService     = $this->createMock(StockService::class);
        $this->serializer       = $this->createMock(SerializerInterface::class);
        $this->cache            = $this->createMock(CacheInterface::class);
        $this->connection       = $this->createMock(Connection::class);

        $this->entityManager->method('getConnection')->willReturn($this->connection);

        $this->orderService = new OrderService(
            $this->cache,
            $this->entityManager,
            $this->locationRepository,
            $this->orderRepository,
            $this->productRepository,
            $this->stockRepository,
            $this->stockService,
            $this->serializer
        );
    }

    // --- getAllOrders ---

    public function testGetAllOrdersReturnsResultFromRepository(): void
    {
        $orders = [new Order(), new Order()];
        $this->orderRepository->expects($this->once())
            ->method('ordersWithRelationships')
            ->willReturn($orders);

        $this->assertSame($orders, $this->orderService->getAllOrders());
    }

    // --- getOrder ---

    public function testGetOrderReturnsOrderWhenFound(): void
    {
        $order = new Order();
        $this->orderRepository->expects($this->once())->method('find')->with(1)->willReturn($order);

        $this->assertSame($order, $this->orderService->getOrder(1));
    }

    public function testGetOrderReturnsNullWhenNotFound(): void
    {
        $this->orderRepository->method('find')->willReturn(null);

        $this->assertNull($this->orderService->getOrder(999));
    }

    // --- deleteSingleOrder ---

    public function testDeleteSingleOrderDelegatesToRepository(): void
    {
        $this->orderRepository->expects($this->once())->method('removeById')->with(42);

        $this->orderService->deleteSingleOrder(42);
    }

    // --- create ---

    public function testCreateFromArrayPersistsOrderAndCommits(): void
    {
        $stock = $this->createMock(Stock::class);
        $stock->method('getProduct')->willReturn(new Product());

        $this->stockRepository->expects($this->once())
            ->method('findByProductNames')
            ->with(['Widget'])
            ->willReturn([$stock]);

        $this->connection->expects($this->once())->method('beginTransaction');
        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Order::class));
        $this->entityManager->expects($this->once())->method('flush');
        $this->stockService->expects($this->once())->method('reduceProductFromStock');
        $this->connection->expects($this->once())->method('commit');

        $this->orderService->create(['items' => [(object)['name' => 'Widget', 'isPick' => true, 'note' => 'urgent']]]);
    }

    public function testCreateFromObjectPersistsOrderAndCommits(): void
    {
        $stock = $this->createMock(Stock::class);
        $stock->method('getProduct')->willReturn(new Product());

        $this->stockRepository->expects($this->once())
            ->method('findByProductNames')
            ->with(['Gadget'])
            ->willReturn([$stock]);

        $this->connection->expects($this->once())->method('beginTransaction');
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');
        $this->stockService->expects($this->once())->method('reduceProductFromStock');
        $this->connection->expects($this->once())->method('commit');

        $data = new \stdClass();
        $data->items = [(object)['name' => 'Gadget', 'isPick' => false, 'note' => '']];
        $this->orderService->create($data);
    }

    public function testCreateRollsBackAndRethrowsOnException(): void
    {
        $this->stockRepository->method('findByProductNames')->willThrowException(new \Exception('DB error'));

        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->once())->method('rollBack');
        $this->connection->expects($this->never())->method('commit');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('DB error');

        $this->orderService->create(['items' => [(object)['name' => 'Ghost', 'isPick' => false, 'note' => '']]]);
    }

    // --- deleteProductFromTheOrder ---

    public function testDeleteProductFromOrderRemovesProductAndFlushes(): void
    {
        $product = new Product();
        $order = $this->createMock(Order::class);
        $order->method('getProducts')->willReturn(new ArrayCollection([new Product()]));

        $this->orderRepository->method('find')->with(1)->willReturn($order);
        $this->productRepository->method('find')->with(2)->willReturn($product);
        $order->expects($this->once())->method('removeProduct')->with($product);
        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->never())->method('remove');

        $this->orderService->deleteProductFromTheOrder(1, 2);
    }

    public function testDeleteProductFromOrderRemovesOrderWhenEmpty(): void
    {
        $product = new Product();
        $order = $this->createMock(Order::class);
        $order->method('getProducts')->willReturn(new ArrayCollection());

        $this->orderRepository->method('find')->willReturn($order);
        $this->productRepository->method('find')->willReturn($product);
        $this->entityManager->expects($this->once())->method('remove')->with($order);
        $this->entityManager->expects($this->once())->method('flush');

        $this->orderService->deleteProductFromTheOrder(1, 2);
    }

    public function testDeleteProductFromOrderDoesNothingWhenOrderNotFound(): void
    {
        $this->orderRepository->method('find')->willReturn(null);
        $this->productRepository->method('find')->willReturn(new Product());
        $this->entityManager->expects($this->never())->method('flush');

        $this->orderService->deleteProductFromTheOrder(99, 1);
    }

    public function testDeleteProductFromOrderDoesNothingWhenProductNotFound(): void
    {
        $this->orderRepository->method('find')->willReturn(new Order());
        $this->productRepository->method('find')->willReturn(null);
        $this->entityManager->expects($this->never())->method('flush');

        $this->orderService->deleteProductFromTheOrder(1, 99);
    }

    // --- countProductsInOrder ---

    public function testCountProductsInOrderReturnsCount(): void
    {
        $result = $this->createMock(Result::class);
        $result->method('fetchOne')->willReturn(3);

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                'SELECT COUNT(*) AS products_amount FROM order_products WHERE order_id = :orderId',
                ['orderId' => 5]
            )
            ->willReturn($result);

        $this->assertSame(3, $this->orderService->countProductsInOrder(5));
    }

    // --- updateNote ---

    public function testUpdateNoteSetsNoteAndUpdatedAt(): void
    {
        $order = new Order();
        $this->orderRepository->method('find')->with(1)->willReturn($order);
        $this->entityManager->expects($this->once())->method('flush');

        $this->orderService->updateNote(1, 'new note');

        $this->assertSame('new note', $order->getNote());
        $this->assertNotNull($order->getUpdatedAt());
    }

    public function testUpdateNoteThrowsWhenOrderNotFound(): void
    {
        $this->orderRepository->method('find')->willReturn(null);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Order not found: 99');

        $this->orderService->updateNote(99, 'note');
    }

    // --- updateIsPick ---

    public function testUpdateIsPickSetsValueAndUpdatedAt(): void
    {
        $order = new Order();
        $this->orderRepository->method('find')->with(7)->willReturn($order);

        $dto = new OrderDto();
        $dto->orderId = 7;
        $dto->isPick = true;

        $this->orderService->updateIsPick($dto);

        $this->assertTrue($order->getIsPick());
        $this->assertNotNull($order->getUpdatedAt());
    }

    public function testUpdateIsPickThrowsWhenOrderNotFound(): void
    {
        $this->orderRepository->method('find')->willReturn(null);
        $this->expectException(\Exception::class);

        $dto = new OrderDto();
        $dto->orderId = 99;
        $dto->isPick = false;

        $this->orderService->updateIsPick($dto);
    }

    // --- updateOrdersTableWithRelationships ---

    public function testUpdateOrdersTableCommitsOnSuccess(): void
    {
        $orderDto = new OrderDto();
        $orderDto->orderId = 1;
        $orderDto->isPick = true;

        $locationDto = new LocationDto();
        $locationDto->oldProductId = 2;
        $locationDto->locationName = 'Shelf A';

        $productDto = new ProductDto();
        $productDto->oldProductId = 2;
        $productDto->productName = 'Widget';
        $productDto->quantityInProduct = 5;

        $stockOfOldProduct = $this->createMock(Stock::class);
        $stockOfOldProduct->method('getProductName')->willReturn('Widget');

        $oldProduct = $this->createMock(Product::class);
        $oldProduct->method('getId')->willReturn(2);
        $oldProduct->method('getLocations')->willReturn(new ArrayCollection());
        $oldProduct->method('getStock')->willReturn($stockOfOldProduct);

        $newProduct = $this->createMock(Product::class);
        $newProduct->method('getId')->willReturn(2);
        $newProduct->method('getLocations')->willReturn(new ArrayCollection());

        $stock = $this->createMock(Stock::class);
        $stock->method('getProduct')->willReturn($newProduct);

        $order = $this->createMock(Order::class);
        $location = new Location();

        $this->orderRepository->method('find')->willReturn($order);
        $this->productRepository->method('find')->willReturn($oldProduct);
        $this->stockRepository->method('findByProductNames')->willReturn([$stock]);
        $this->locationRepository->method('findOneBy')->with(['name' => 'Shelf A'])->willReturn($location);
        $this->entityManager->method('flush');

        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->once())->method('commit');
        $this->connection->expects($this->never())->method('rollBack');

        $this->orderService->updateOrdersTableWithRelationships($orderDto, $locationDto, $productDto);
    }

    public function testUpdateOrdersTableRollsBackOnException(): void
    {
        $orderDto = new OrderDto();
        $orderDto->orderId = 1;
        $orderDto->isPick = false;

        $locationDto = new LocationDto();
        $locationDto->oldProductId = 1;
        $locationDto->locationName = 'Unknown';

        $productDto = new ProductDto();
        $productDto->oldProductId = 1;
        $productDto->productName = 'Ghost';
        $productDto->quantityInProduct = 1;

        $this->stockRepository->method('findByProductNames')->willReturn([null]);

        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->expects($this->once())->method('rollBack');
        $this->connection->expects($this->never())->method('commit');

        $this->expectException(\Throwable::class);

        $this->orderService->updateOrdersTableWithRelationships($orderDto, $locationDto, $productDto);
    }

    public function testUpdateOrdersTableSwapsProductWhenDifferent(): void
    {
        $orderDto = new OrderDto();
        $orderDto->orderId = 1;
        $orderDto->isPick = false;

        $locationDto = new LocationDto();
        $locationDto->oldProductId = 2;
        $locationDto->locationName = 'Shelf B';

        $productDto = new ProductDto();
        $productDto->oldProductId = 2;
        $productDto->productName = 'NewWidget';
        $productDto->quantityInProduct = 3;

        $stockOfOldProduct = $this->createMock(Stock::class);
        $stockOfOldProduct->method('getProductName')->willReturn('OldWidget');

        $oldProduct = $this->createMock(Product::class);
        $oldProduct->method('getId')->willReturn(2);
        $oldProduct->method('getLocations')->willReturn(new ArrayCollection());
        $oldProduct->method('getStock')->willReturn($stockOfOldProduct);

        $newProduct = $this->createMock(Product::class);
        $newProduct->method('getId')->willReturn(99);
        $newProduct->method('getLocations')->willReturn(new ArrayCollection());

        $stock = $this->createMock(Stock::class);
        $stock->method('getProduct')->willReturn($newProduct);

        $order = $this->createMock(Order::class);

        $order->expects($this->exactly(2))->method('removeProduct')->with($oldProduct);
        $order->expects($this->exactly(2))->method('addProduct')->with($newProduct);

        $this->orderRepository->method('find')->willReturn($order);
        $this->productRepository->method('find')->willReturn($oldProduct);
        $this->stockRepository->method('findByProductNames')->willReturn([$stock]);
        $this->locationRepository->method('findOneBy')->willReturn(new Location());
        $this->entityManager->method('flush');
        $this->connection->method('beginTransaction');
        $this->connection->method('commit');

        $this->orderService->updateOrdersTableWithRelationships($orderDto, $locationDto, $productDto);
    }
}
