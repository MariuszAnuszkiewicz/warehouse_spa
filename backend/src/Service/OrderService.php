<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderService
{
    public function __construct(
        private CacheInterface $cache,
        private EntityManagerInterface $entityManager,
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
        try {
            $conn->beginTransaction();
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
            $this->createOrderProducts($orders);
            $this->stockService->reduceProductFromStock($dataContent);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
        }
    }

    public function createOrderProducts(array $orders)
    {
        try {
            $sql = 'INSERT INTO order_products (order_id, product_id) VALUES (:orderId, :productId)';
            $stmt = $this->entityManager->getConnection()->prepare($sql);

            foreach ($orders ?? [] as $order) {
                $stmt->executeStatement([
                    'orderId' => $order->getId(),
                    'productId' => $order->getProducts()->getId(),
                ]);
            }
        } catch (\Throwable $e) {
            return new JsonResponse(
                [
                   'error' => true,
                   'message' => $e->getMessage()
                ], Response::HTTP_NOT_FOUND
            );
        }
    }
}