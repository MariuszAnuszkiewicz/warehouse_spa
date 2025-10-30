<?php

namespace App\DataFixtures;

use App\Entity\Stock;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StockFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getStockData() as $data) {
            $stock = (new Stock())
                ->setProductName($data['product_name'])
                ->setEan13($data['ean13'])
                ->setQuantityInStock($data['quantity_in_stock']);

            $manager->persist($stock);
        }

        $manager->flush();
    }

    private function getStockData(): array
    {
        return [
            [
                'product_name' => 'Product_1',
                'ean13' => '9370942873429',
                'quantity_in_stock' => 2400
            ],
            [
                'product_name' => 'Product_2',
                'ean13' => '1370943873426',
                'quantity_in_stock' => 11000
            ],
            [
                'product_name' => 'Product_3',
                'ean13' => '9094338744268',
                'quantity_in_stock' => 27000
            ],
            [
                'product_name' => 'Product_4',
                'ean13' => '3270943873426',
                'quantity_in_stock' => 72000
            ],
            [
                'product_name' => 'Product_5',
                'ean13' => '7530943873421',
                'quantity_in_stock' => 65000
            ],
            [
                'product_name' => 'Product_6',
                'ean13' => '0980943873421',
                'quantity_in_stock' => 19000
            ],
            [
                'product_name' => 'Product_7',
                'ean13' => '1230938987342',
                'quantity_in_stock' => 275000
            ],
            [
                'product_name' => 'Product_8',
                'ean13' => '9930943873421',
                'quantity_in_stock' => 1250
            ],
            [
                'product_name' => 'Product_9',
                'ean13' => '7530843873421',
                'quantity_in_stock' => 850
            ],
            [
                'product_name' => 'Product_10',
                'ean13' => '0530943873421',
                'quantity_in_stock' => 1298
            ],
        ];
    }
}
