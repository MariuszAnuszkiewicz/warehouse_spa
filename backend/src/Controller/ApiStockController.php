<?php

namespace App\Controller;

use App\Repository\StockRepository;
use App\Service\LogService;
use App\Service\SerializeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'app_api')]
class ApiStockController extends AbstractController
{
    public function __construct(
       private LogService $logService,
       private SerializeService $serializeService
    ){}

    #[Route('/stocks', name: '_stocks', methods: ['GET'])]
    public function index(StockRepository $stockRepository): JsonResponse
    {
        try {
            $stocks = $stockRepository->findAll();

            if (empty($stocks)) {
                throw new \RuntimeException('No stocks found.');
            }

            return $this->json([
                'stocks' => $this->serializeService->dataSerialize($stocks)
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {

            $this->logService->logException($e);

            return $this->json([
                'error' => true,
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }
}