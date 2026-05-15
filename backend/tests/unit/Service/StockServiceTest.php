<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Repository\StockRepository;
use App\Service\StockService;
use PHPUnit\Framework\TestCase;

class StockServiceTest extends TestCase
{
    private StockRepository $stockRepository;
    private StockService $stockService;

    protected function setUp(): void
    {
        $this->stockRepository = $this->createMock(StockRepository::class);
        $this->stockService    = new StockService($this->stockRepository);
    }

    // --- array format ---

    public function testReduceFromArrayCallsRepositoryWithCorrectQuantity(): void
    {
        $this->stockRepository->expects($this->once())
            ->method('updateQuantityInStockByProduct')
            ->with(7, 'Product A');

        $this->stockService->reduceProductFromStock([
            ['name' => 'Product A', 'quantityInStock' => 10, 'quantity' => 3]
        ]);
    }

    public function testReduceFromArrayCallsRepositoryForEachItem(): void
    {
        $calls = [];
        $this->stockRepository->expects($this->exactly(2))
            ->method('updateQuantityInStockByProduct')
            ->willReturnCallback(function (int $qty, string $name) use (&$calls) {
                $calls[] = [$qty, $name];
            });

        $this->stockService->reduceProductFromStock([
            ['name' => 'Product A', 'quantityInStock' => 10, 'quantity' => 3],
            ['name' => 'Product B', 'quantityInStock' => 20, 'quantity' => 5],
        ]);

        $this->assertSame([[7, 'Product A'], [15, 'Product B']], $calls);
    }

    public function testReduceFromArrayCastsStringQuantitiesToInt(): void
    {
        $this->stockRepository->expects($this->once())
            ->method('updateQuantityInStockByProduct')
            ->with(8, 'Widget');

        $this->stockService->reduceProductFromStock([
            ['name' => 'Widget', 'quantityInStock' => '12', 'quantity' => '4']
        ]);
    }

    // --- object format ---

    public function testReduceFromObjectCallsRepositoryWithCorrectQuantity(): void
    {
        $this->stockRepository->expects($this->once())
            ->method('updateQuantityInStockByProduct')
            ->with(3, 'Product B');

        $this->stockService->reduceProductFromStock(new \ArrayObject([
            (object)['name' => 'Product B', 'quantityInStock' => 5, 'quantity' => 2]
        ]));
    }

    public function testReduceFromObjectCallsRepositoryForEachItem(): void
    {
        $calls = [];
        $this->stockRepository->expects($this->exactly(2))
            ->method('updateQuantityInStockByProduct')
            ->willReturnCallback(function (int $qty, string $name) use (&$calls) {
                $calls[] = [$qty, $name];
            });

        $this->stockService->reduceProductFromStock(new \ArrayObject([
            (object)['name' => 'Product C', 'quantityInStock' => 8, 'quantity' => 3],
            (object)['name' => 'Product D', 'quantityInStock' => 6, 'quantity' => 6],
        ]));

        $this->assertSame([[5, 'Product C'], [0, 'Product D']], $calls);
    }

    public function testReduceFromObjectCastsStringQuantitiesToInt(): void
    {
        $this->stockRepository->expects($this->once())
            ->method('updateQuantityInStockByProduct')
            ->with(6, 'Gadget');

        $this->stockService->reduceProductFromStock(new \ArrayObject([
            (object)['name' => 'Gadget', 'quantityInStock' => '10', 'quantity' => '4']
        ]));
    }

    // --- edge cases ---

    public function testReduceFromEmptyArrayNeverCallsRepository(): void
    {
        $this->stockRepository->expects($this->never())
            ->method('updateQuantityInStockByProduct');

        $this->stockService->reduceProductFromStock([]);
    }

    public function testReduceFromEmptyObjectNeverCallsRepository(): void
    {
        $this->stockRepository->expects($this->never())
            ->method('updateQuantityInStockByProduct');

        $this->stockService->reduceProductFromStock(new \ArrayObject([]));
    }
}
