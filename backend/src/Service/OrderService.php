<?php

declare(strict_types=1);

namespace App\Service;

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
                    ->setQuantityInOrder($data->quantity)
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

    private function updateOrderProducts(int $orderId, int $oldProductId, string $newProductName): void
    {
        $order = $this->orderRepository->find($orderId);

        $oldProduct = $this->productRepository->find($oldProductId);
        if (!$order || !$oldProduct) {
            throw new \InvalidArgumentException('Order or Old Product not found');
        }

        $stockRecords = $this->stockRepository->findByProductNames([$newProductName]);
        $newProduct = $stockRecords[0]?->getProduct();

        if (!$newProduct) {
            throw new \InvalidArgumentException('New Product not found');
        }

        $order->removeProduct($oldProduct);
        $order->addProduct($newProduct);

        $this->entityManager->flush();
    }

    private function updateLocationProducts(string $newProductName, string $newLocationName): void
    {
        $stockRecords = $this->stockRepository->findByProductNames([$newProductName]);
        $product = $stockRecords[0]?->getProduct();

        if (!$product) {
            throw new \InvalidArgumentException('Product not found');
        }

        $location = $this->locationRepository->findOneBy(['name' => $newLocationName]);
        if (!$location) {
            throw new \InvalidArgumentException('Location not found');
        }

        $product->getLocations()->clear();
        $product->addLocation($location);

        $this->entityManager->flush();
    }

    public function updateNote(int $orderId, string $note)
    {
        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            throw new \Exception("Order not found: $orderId");
        }

        $order->setNote($note);
        $order->setUpdatedAt(date_create());
        $this->entityManager->flush();
    }

    private function updateQuantityInOrder(int $orderId, int $quantityInOrder): void
    {
        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            throw new \Exception("Nie znaleziono zamówienia o ID: $orderId");
        }
        $order->setQuantityInOrder($quantityInOrder);
        $order->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();
    }

    public function updateOrdersTableWithRelationships(
        int $orderId,
        int $quantityInOrder,
        int $oldProductId,
        string $newProductName,
        string $newLocationName,
    ): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();
        try {
            // update table location_products
            $this->updateLocationProducts(
                $newProductName,
                $newLocationName
            );
            // update table order_products
            $this->updateOrderProducts(
                $orderId,
                $oldProductId,
                $newProductName
            );
            // update field describe in method name within table orders
            $this->updateQuantityInOrder($orderId, $quantityInOrder);
            $conn->commit();

        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}