<?php


namespace App\Controller;

use App\Service\LogService;
use App\Service\SerializeService;
use App\Repository\LocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'app_api')]
class ApiLocationController extends AbstractController
{
    public function __construct(
        private LogService $logService,
        private SerializeService $serializeService,
        private LocationRepository $locationRepository
    ){}

    #[Route('/locations', name: '_locations', methods: ['GET'])]
    public function index(): JsonResponse
    {
        try {
            $locations = $this->locationRepository->findAll();

            if (empty($locations)) {
                throw new \RuntimeException('The locations could not be found.');
            }

            return $this->json([
                'locations' => $this->serializeService->dataSerialize($locations)
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