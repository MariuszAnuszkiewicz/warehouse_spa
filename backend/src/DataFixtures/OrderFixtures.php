<?php

namespace App\DataFixtures;

use App\Entity\Order;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OrderFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $datetime = new \DateTime(date('Y-m-d H:i:s'));
        foreach ($this->getOrderData() as $key => $data) {
            $order = (new Order())
                ->setQuantityInOrder($data['product_quantity'])
                ->setIsPick($data['is_pick'])
                ->setNote($data['note'])
                ->setCreatedAt($datetime = new \DateTime(date('Y-m-d H:i:s')));

            $manager->persist($order);
        }
        $manager->flush();
    }

    private function getOrderData(): array
    {
        return [
            [
                'product_quantity' => 24,
                'is_pick' => false,
                'note' => 'some note 1',
            ],
            [
                'product_quantity' => 11,
                'is_pick' => false,
                'note' => 'some note 2',
            ],
            [
                'product_quantity' => 27,
                'is_pick' => false,
                'note' => 'some note 3',
            ],
            [
                'product_quantity' => 72,
                'is_pick' => false,
                'note' => 'some note 4',
            ],
            [
                'product_quantity' => 42,
                'is_pick' => false,
                'note' => 'some note 5',
            ],
            [
                'product_quantity' => 65,
                'is_pick' => false,
                'note' => 'some note 6',
            ],
            [
                'product_quantity' => 295,
                'is_pick' => false,
                'note' => 'some note 7',
            ],
            [
                'product_quantity' => 125,
                'is_pick' => false,
                'note' => 'some note 8',
            ],
            [
                'product_quantity' => 85,
                'is_pick' => false,
                'note' => 'some note 9',
            ],
            [
                'product_quantity' => 983,
                'is_pick' => false,
                'note' => 'some note 10',
            ]
        ];
    }
}
