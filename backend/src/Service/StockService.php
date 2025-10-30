<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;

class StockService
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected StockRepository $stockRepository
    ) {}

    public function reduceProductFromStock(array|object $dataContent): void
    {
        foreach ($dataContent ?? [] as $data) {
            if (is_array($data)) {
                $quantity = (int) $data['quantityInStock'] - (int) $data['quantity'];
                $this->stockRepository->updateQuantityInStockByProduct((int) $quantity, (string) $data['name']);
            }
            elseif (is_object($data)) {
                $quantity = (int) $data->quantityInStock - (int) $data->quantity;
                $this->stockRepository->updateQuantityInStockByProduct((int) $quantity, (string) $data->name);
            }
        }
    }
}