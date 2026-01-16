<?php

namespace App\Controller;

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
       private SerializeService $serializeService,
       private OrderService $orderService
    ){}

    #[Route('/orders', name: '_orders', methods: ['GET'])]
    public function index(): JsonResponse
    {
        try {

            $orders = $this->orderService->getAllOrders();

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
    public function show(int $id): JsonResponse
    {
        try {
            $order = $orders = $this->orderService->getOrder((int) $id);

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

    #[Route('/order/del', name: '_order_delete', methods: ['DELETE'])]
    public function deleteOrder(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?? [];

        foreach ($data['order_ids'] as $ids) {
            $this->orderService->deleteSingleOrder($ids);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/order/del/product/{id}', name: '_order_delete_product', methods: ['DELETE'])]
    public function deleteProduct(Request $request): Response
    {
        $productId = (int) $request->get('id');
        $data = json_decode($request->getContent(), true);
        $orderId = (int) $data['order_id'];

        $this->orderService->deleteProductFromTheOrder($orderId, $productId);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/order/create', name: '_order_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->orderService->create($request);

        return $this->json(
            [
               'message' => 'order created successfully',
               'data' => json_decode($request->getContent(), true) ?? []
            ], Response::HTTP_CREATED
        );
    }

    #[Route('/order/update', name: '_order_update', methods: ['PUT', 'POST', 'DELETE'])]
    public function update(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $orderField = array_map(null, $data['order'])[0];
        $locationFields = array_map(null, $data['location']);

        foreach ($data['product'] as $i => $productFields) {
            $this->orderService->updateProductAndLocation(
                (int) $orderField['orderId'],
                (int) $productFields['oldProductId'],
                (string) $productFields['productName'],
                (string) $locationFields[$i]['locationName']
            );
        }

        return $this->json(
            [
                'message' => 'order updated successfully',
                'data' => json_decode($request->getContent(), true) ?? []
            ], Response::HTTP_CREATED
        );
    }
}