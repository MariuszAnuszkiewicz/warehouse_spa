<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Repository\LocationRepository;
use App\Repository\OrderRepository;
use App\Repository\StockRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public const PRODUCT_REFERENCE = 'product-reference';

    public function __construct(
        public LocationRepository $locationRepository,
        public OrderRepository $orderRepository,
        public StockRepository $stockRepository,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $datetime = new \DateTime(date('Y-m-d H:i:s'));
        $stocks = $this->stockRepository->findAll();

        for ($i = 0; $i < 10; $i++) {
            $product = (new Product())
                ->setQuantityInProduct(rand(1, 50))
                ->setStock($stocks[$i])
                ->setCreatedAt($datetime);

            $manager->persist($product);

            $this->setReference(self::PRODUCT_REFERENCE, $product);
            $productRef = $this->getReference(self::PRODUCT_REFERENCE, Product::class);

            $locations = $this->locationRepository->findAll();
            if (!empty($locations)) {
                $productRef->addLocation($locations[rand(0, count($locations) - 1)]);
            }

            $orders = $this->orderRepository->findAll();
            if (!empty($orders)) {
                $productRef->addOrder($orders[rand(0, count($orders) - 1)]);
            }
        }
        $manager->persist($productRef);
        $manager->flush();
    }
}