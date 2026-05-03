<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Location\LocationDto;
use App\Dto\Product\ProductDto;
use App\Dto\Order\OrderDto;
use App\Entity\Order;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\LocationRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class OrderService
{
    public function __construct(
        private CacheInterface $cache,
        private EntityManagerInterface $entityManager,
        private LocationRepository $locationRepository,
        private OrderRepository $orderRepository,
        private ProductRepository $productRepository,
        private StockRepository $stockRepository,
        private StockService $stockService,
        private SerializerInterface $serializer
    ) {}

    public function getAllOrders(): array
    {
        return $this->orderRepository->ordersWithRelationships();
    }

    public function getOrder(int $id): ?Order
    {
        return $this->orderRepository->find($id);
    }

    public function deleteSingleOrder(int $id): void
    {
        $this->orderRepository->removeById($id);
    }

    public function create(array|object $dataContent): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            $inputData = is_object($dataContent) ? $dataContent?->items : $dataContent['items'];

            foreach ($inputData ?? [] as $data) {
                $stockRecords = $this->stockRepository->findByProductNames([$data->name])[0];
                $order = (new Order())
                    ->addProduct($stockRecords->getProduct())
                    ->setIsPick($data->isPick ?? false)
                    ->setNote($data->note ?? '')
                    ->setCreatedAt(date_create());

                $this->entityManager->persist($order);
            }

            $this->entityManager->flush();
            $this->stockService->reduceProductFromStock($inputData);
            $conn->commit();

        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function deleteProductFromTheOrder(int $orderId, int $productId): void
    {
        $order = $this->orderRepository->find($orderId);
        $product = $this->productRepository->find($productId);

        if (!$order || !$product) {
            return;
        }

        $order->removeProduct($product);

        if ($order->getProducts()->isEmpty()) {
            $this->entityManager->remove($order);
        }

        $this->entityManager->flush();
    }

    public function countProductsInOrder(int $orderId): int
    {
        $conn = $this->entityManager->getConnection();

        $productCount = $conn->executeQuery(
            'SELECT COUNT(*) AS products_amount FROM order_products WHERE order_id = :orderId',
            ['orderId' => $orderId]
        )->fetchOne();

        return $productCount;
    }

    private function updateOrderProducts(OrderDto $orderDto, ProductDto $productDto): void
    {
        $order = $this->orderRepository->find($orderDto->orderId);

        $oldProduct = $this->productRepository->find($productDto->oldProductId);

        if (!$order || !$oldProduct) {
            throw new \InvalidArgumentException("Order (ID: $orderDto->orderId) or Old Product (ID: {$productDto->oldProductId}) not found");
        }

        $stockRecords = $this->stockRepository->findByProductNames([$productDto->productName]);
        $newProduct = $stockRecords[0]?->getProduct();

        if (!$newProduct) {
            throw new \InvalidArgumentException("New Product ({$productDto->productName}) not found in stock");
        }

        if ($oldProduct->getId() !== $newProduct->getId()) {
            $order->removeProduct($oldProduct);
            $order->addProduct($newProduct);
        }

        $newProduct->setQuantityInProduct($productDto->quantityInProduct);
        $newProduct->setUpdatedAt(new \DateTime());
    }

    private function updateLocationProducts(LocationDto $location, ProductDto $productDto): void
    {
        $stockRecords = $this->stockRepository->findByProductNames([$productDto->productName]);
        $product = $stockRecords[0]?->getProduct();

        if (!$product) {
            throw new \InvalidArgumentException('Product not found');
        }

        $locationObj = $this->locationRepository->findOneBy(['name' => $location->locationName]);
        if (!$locationObj) {
            throw new \InvalidArgumentException('Location not found');
        }

        $product->getLocations()->clear();
        $product->addLocation($locationObj);

        $this->entityManager->flush();
    }

    public function updateNote(int $orderId, string $note): void
    {
        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            throw new \Exception("Order not found: $orderId");
        }

        $order->setNote($note);
        $order->setUpdatedAt(date_create());
        $this->entityManager->flush();
    }

    public function updateIsPick(OrderDto $orderDto): void
    {
        $order = $this->orderRepository->find($orderDto->orderId);

        if (!$order) {
            throw new \Exception("Order not found: $orderDto->orderId");
        }

        $order->setIsPick($orderDto->isPick);
        $order->setUpdatedAt(date_create());
    }

    private function updateQuantityProducts(OrderDto $order, ProductDto $productDto): void
    {
        $order = $this->orderRepository->find($order->orderId);

        if (!$order) {
            throw new \Exception("No order with id found Id: $order->orderId");
        }

        $now = new \DateTime();
        $oldProduct = $this->productRepository->find($productDto->oldProductId);
        if ($oldProduct->getStock()->getProductName() !== $productDto->productName) {
            $stockRecords = $this->stockRepository->findByProductNames([$productDto->productName]);
            $newProductEntity = $stockRecords[0]?->getProduct();

            if ($newProductEntity) {
                $order->removeProduct($oldProduct);
                $order->addProduct($newProductEntity);
                $targetProduct = $newProductEntity;
            } else {
                $targetProduct = $oldProduct;
            }
        } else {
            $targetProduct = $oldProduct;
        }

        $targetProduct->setQuantityInProduct((int)$productDto->quantityInProduct);
        $targetProduct->setUpdatedAt($now);
    }

    public function updateOrdersTableWithRelationships(
        OrderDto $orderDto,
        LocationDto $location,
        ProductDto $productDto
    ): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();
        try {

            $this->updateLocationProducts(
                $location,
                $productDto
            );

            $this->updateOrderProducts(
                $orderDto,
                $productDto
            );

            $this->updateQuantityProducts(
                $orderDto,
                $productDto
            );

            $this->updateIsPick(
                $orderDto
            );

            $conn->commit();

        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}