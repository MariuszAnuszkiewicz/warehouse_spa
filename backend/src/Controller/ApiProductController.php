<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\LogService;
use App\Service\SerializeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'app_api')]
class ApiProductController extends AbstractController
{
    public function __construct(
        private LogService $logService,
        private SerializeService $serializeService
    ){}

    #[Route('/products', name: '_products', methods: ['GET'])]
    public function index(ProductRepository $productRepository): JsonResponse
    {
        try {
            $products = $productRepository->productsWithRelationships();

            if (empty($products)) {
                throw new \RuntimeException('The products could not be found.');
            }

            return $this->json([
                'products' => $this->serializeService->dataSerialize($products)
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

    #[Route('/product/{id}', name: '_product', methods: ['GET'])]
    public function show(ProductRepository $productRepository, int $id): JsonResponse
    {
        try {
            $product = $productRepository->productByIdWithRelationships($id);

            if (empty($product)) {
                throw new \RuntimeException(sprintf('The product %s could not be found.', (string) $id));
            }

            return $this->json([
                'product' => $this->serializeService->dataSerialize($product)
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
}