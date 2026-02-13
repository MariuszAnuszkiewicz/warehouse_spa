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
        $conn = $this->entityManager->getConnection();

        $conn->executeStatement(
            'DELETE FROM order_products WHERE product_id = :productId AND order_id = :orderId',
            [
                'productId' => $productId,
                'orderId' => $orderId,
            ]
        );

        $quantityProductsInOrder = $this->countProductsInOrder($orderId);

        if ($quantityProductsInOrder < 1) {
            $conn->executeStatement(
                'DELETE FROM orders WHERE id = :orderId',
                [
                    'orderId' => $orderId,
                ]
            );
        }
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

    private function updateOrderProducts(int $orderId, int $oldProductId, string $newProductName)
    {
        $conn = $this->entityManager->getConnection();
        try {

            $stockRecords = $this->stockRepository->findByProductNames([$newProductName]);
            $product = $stockRecords[0]?->getProduct();

            if (!$product) {
                throw new \InvalidArgumentException('Product not found');
            }

            $newProductId = $product->getId();

            $conn->executeStatement(
                'DELETE FROM order_products WHERE order_id = :orderId AND product_id = :oldProductId',
                [
                    'orderId' => $orderId,
                    'oldProductId' => $oldProductId,
                ]
            );

            $conn->executeStatement(
                'INSERT INTO order_products (order_id, product_id) VALUES (:orderId, :newProductId)',
                [
                    'orderId' => $orderId,
                    'newProductId' => $newProductId,
                ]
            );

        } catch (\Throwable $e) {
            throw $e;
        }

        $this->entityManager->clear();
    }

    private function updateLocationProducts(string $newProductName, string $newLocationName): void
    {
        $conn = $this->entityManager->getConnection();
        try {
            $stockRecords = $this->stockRepository->findByProductNames([$newProductName]);
            $product = $stockRecords[0]?->getProduct();

            if (!$product) {
                throw new \InvalidArgumentException('Product not found');
            }

            $newProductId = $product->getId();

            $locations = $this->locationRepository->findBy(['name' => $newLocationName]);
            if (!$locations) {
                throw new \InvalidArgumentException('Locations not found');
            }

            $newLocationId = $locations[0]->getId();

            $conn->executeStatement(
                'DELETE FROM location_products WHERE product_id = :pid',
                ['pid' => $newProductId]
            );

            $conn->executeStatement(
                'INSERT INTO location_products (product_id, location_id) VALUES (:pid, :lid)',
                [
                    'pid' => $newProductId,
                    'lid' => $newLocationId
                ]
            );

        } catch (\Throwable $e) {
            throw $e;
        }

        $this->entityManager->clear();
    }

    private function updateNote(int $orderId, string|null $note)
    {
        $conn = $this->entityManager->getConnection();

        try {
            $conn->executeStatement(
                'UPDATE orders SET note = :note WHERE id = :id',
                [
                    'id' => $orderId,
                    'note' => $note,
                ]
            );

        } catch (\Throwable $e) {
            throw $e;
        }
    }

    private function updateQuantityInOrder(int $orderId, int $quantityInOrder)
    {
        $conn = $this->entityManager->getConnection();

        try {
            $conn->executeStatement(
                'UPDATE orders SET quantity_in_order = :quantityInOrder WHERE id = :id',
                [
                    'id' => $orderId,
                    'quantityInOrder' => $quantityInOrder,
                ]
            );

        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function updateOrdersTableWithRelationships(
        int $orderId,
        int $quantityInOrder,
        int $oldProductId,
        string $newProductName,
        string $newLocationName,
        string $note
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
            // update field note within table orders
            $this->updateNote($orderId, $note);
            // update field describe in method name within table orders
            $this->updateQuantityInOrder($orderId, $quantityInOrder);
            $conn->commit();

        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }
}