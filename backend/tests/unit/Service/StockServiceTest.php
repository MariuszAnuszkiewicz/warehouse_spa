<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Repository\StockRepository;
use App\Service\StockService;
use PHPUnit\Framework\TestCase;

class StockServiceTest extends TestCase
{
    private StockRepository $mockStockRepository;
    private StockService $stockService;

    protected function setUp(): void
    {
        $this->mockStockRepository = $this->createMock(StockRepository::class);
        $this->stockService = new StockService($this->mockStockRepository);
    }

    public function testReduceProductFromStockWithArrayData(): void
    {
        $data = [
            [
                'name' => 'Product A',
                'quantityInStock' => 10,
                'quantity' => 3
            ],
        ];

        $this->mockStockRepository
            ->expects($this->once())
            ->method('updateQuantityInStockByProduct')
            ->with(7, 'Product A');

        $this->stockService->reduceProductFromStock($data);
    }

    public function testReduceProductFromStockWithObjectData(): void
    {
        $data = new \ArrayObject([
            (object) [
                'name' => 'Product B',
                'quantityInStock' => 5,
                'quantity' => 2
            ]
        ]);

        $this->mockStockRepository
            ->expects($this->once())
            ->method('updateQuantityInStockByProduct')
            ->with(3, 'Product B');

        $this->stockService->reduceProductFromStock($data);
    }

    public function testReduceProductFromStockWithEmptyData(): void
    {
        $this->mockStockRepository
            ->expects($this->never())
            ->method('updateQuantityInStockByProduct');

        $this->stockService->reduceProductFromStock([]);
    }
}