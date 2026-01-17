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
                    ->setIsPick($data->isPick)
                    ->setNote($data->note)
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

    public function updateProductAndLocation(int $orderId, int $oldProductId, string $newProductName, string $newLocationName): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

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

            $conn->commit();

        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        $this->entityManager->clear();
    }
}