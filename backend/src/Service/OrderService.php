<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Repository\LocationRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;

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

    public function filteringStockByName(array $dataContent): array
    {
        if (is_array($dataContent)) {
            $productNames = array_map(fn($d) => $d->name ?? $d['name'], $dataContent);
        }

        $stockRecords = $this->stockRepository->findByProductNames($productNames);

        $stockByName = [];
        foreach ($stockRecords as $stock) {
            $stockByName[$stock->getProductName()] = $stock;
        }

        return $stockByName;
    }

    public function create(Request $request): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            $dataContent = json_decode($request->getContent());
            $stockByName = $this->filteringStockByName($dataContent);

            $orders = [];
            foreach ($dataContent ?? [] as $data) {
                if (!$stockByName[$data->name]) {
                    continue;
                }

                $order = (new Order())
                    ->addProduct($stockByName[$data->name]->getProduct())
                    ->setQuantityInOrder($data->quantity)
                    ->setIsPick($data->isPick)
                    ->setNote($data->note)
                    ->setCreatedAt(date_create());

                $this->entityManager->persist($order);
                array_push($orders, $order);
            }

            $this->entityManager->flush();
            $this->stockService->reduceProductFromStock($dataContent);
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