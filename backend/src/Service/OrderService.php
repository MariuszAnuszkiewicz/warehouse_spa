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

    public function create(Request $request): void
    {
        $dataContent = json_decode($request->getContent());
        $orders = [];
        foreach ($dataContent ?? [] as $data) {
            $stockArray = $this->stockRepository->findBy(['productName' => $data->name]);
            $productId = array_map(fn($stock) => $stock?->getProduct()->getId(), $stockArray)[0];
            $product = $this->productRepository->find($productId);

            $order = (new Order())
                ->addProduct($product)
                ->setQuantityInOrder($data->quantity ?? 0)
                ->setIsPick($data->isPick ?? false)
                ->setNote($data->note ?? '')
                ->setCreatedAt(date_create());

            $this->entityManager->persist($order);
            array_push($orders, $order);
        }
        $this->entityManager->flush();
        $this->createOrderProducts($orders);
        $this->stockService->reduceProductFromStock($dataContent);
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