<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\LogService;
use App\Service\OrderService;
use App\Service\SerializeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'app_api')]
class ApiOrderController extends AbstractController
{
    public function __construct(
       private LogService $logService,
       private SerializeService $serializeService
    ){}

    #[Route('/orders', name: '_orders', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): JsonResponse
    {
        try {
            $orders = $orderRepository->ordersWithRelationships();

            if (empty($orders)) {
                throw new \RuntimeException('The orders could not be found.');
            }

            return $this->json([
                'orders' => $this->serializeService->dataSerialize($orders)
            ]);

        } catch (\Throwable $e) {

            $this->logService->logException($e);

            return $this->json(
                [
                    'error' => true,
                    'message' => $e->getMessage()
                ], Response::HTTP_NOT_FOUND
            );
        }
    }

    #[Route('/order/{id}', name: '_order', methods: ['GET'])]
    public function show(OrderRepository $orderRepository, int $id): JsonResponse
    {
        try {
            $order = $orderRepository->find($id);

            if (empty($order)) {
                throw new \RuntimeException(sprintf('The order %s could not be found.', (string) $id));
            }

            return $this->json([
                'order' => $this->serializeService->dataSerialize($order)
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {

            $this->logService->logException($e);

            return $this->json(
                [
                    'error' => true,
                    'message' => $e->getMessage()
                ], Response::HTTP_NOT_FOUND
            );
        }
    }

    #[Route('/order/create', name: '_order_create', methods: ['POST'])]
    public function create(OrderService $orderService, Request $request): JsonResponse
    {
        $orderService->create($request);

        return $this->json(
            [
               'message' => 'order created successfully',
               'data' => json_decode($request->getContent(), true) ?? []
            ], Response::HTTP_CREATED
        );
    }
}