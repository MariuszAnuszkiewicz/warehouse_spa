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
                'is_pick' => false,
                'note' => 'some note 1',
            ],
            [
                'is_pick' => false,
                'note' => 'some note 2',
            ],
            [
                'is_pick' => false,
                'note' => 'some note 3',
            ],
            [
                'is_pick' => false,
                'note' => 'some note 4',
            ],
            [
                'is_pick' => false,
                'note' => 'some note 5',
            ],
            [
                'is_pick' => false,
                'note' => 'some note 6',
            ],
            [
                'is_pick' => false,
                'note' => 'some note 7',
            ],
            [
                'is_pick' => false,
                'note' => 'some note 8',
            ],
            [
                'is_pick' => false,
                'note' => 'some note 9',
            ],
            [
                'is_pick' => false,
                'note' => 'some note 10',
            ]
        ];
    }
}
