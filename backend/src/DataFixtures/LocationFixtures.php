<?php

namespace App\DataFixtures;

use App\Entity\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LocationFixtures extends Fixture
{
    public const LOCATION_REFERENCE = 'location-reference';

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getLocationData() as $data) {
            $location = (new Location())
                ->setName($data['name']);

            $manager->persist($location);
            $this->setReference(self::LOCATION_REFERENCE, $location);
        }

        $manager->flush();
    }


    private function getLocationData(): array
    {
        return [
            [
                'name' => 'Palette 1'
            ],
            [
                'name' => 'Palette 2'
            ],
            [
                'name' => 'Palette 3'
            ],
            [
                'name' => 'Palette 4'
            ],
            [
                'name' => 'Palette 5'
            ],
            [
                'name' => 'Rack A1'
            ],
            [
                'name' => 'Rack A2'
            ],
            [
                'name' => 'Rack A3'
            ],
            [
                'name' => 'Rack A4'
            ],
            [
                'name' => 'Rack A5'
            ],
            [
                'name' => 'Rack B1'
            ],
            [
                'name' => 'Rack B2'
            ],
            [
                'name' => 'Rack B3'
            ],
            [
                'name' => 'Rack B4'
            ],
            [
                'name' => 'Rack B5'
            ],
            [
                'name' => 'Rack C1'
            ],
            [
                'name' => 'Rack C2'
            ],
            [
                'name' => 'Rack C3'
            ],
            [
                'name' => 'Rack C4'
            ],
            [
                'name' => 'Rack C5'
            ],
            [
                'name' => 'Rack D1'
            ],
            [
                'name' => 'Rack D2'
            ],
            [
                'name' => 'Rack D3'
            ],
            [
                'name' => 'Rack D4'
            ],
            [
                'name' => 'Rack D5'
            ],
            [
                'name' => 'Rack E1'
            ],
            [
                'name' => 'Rack E2'
            ],
            [
                'name' => 'Rack E3'
            ],
            [
                'name' => 'Rack E4'
            ],
            [
                'name' => 'Rack E5'
            ],
            [
                'name' => 'Rack F1'
            ],
            [
                'name' => 'Rack F2'
            ],
            [
                'name' => 'Rack F3'
            ],
            [
                'name' => 'Rack F4'
            ],
            [
                'name' => 'Rack F5'
            ],
            [
                'name' => 'Rack G1'
            ],
            [
                'name' => 'Rack G2'
            ],
            [
                'name' => 'Rack G3'
            ],
            [
                'name' => 'Rack G4'
            ],
            [
                'name' => 'Rack G5'
            ],
            [
                'name' => 'Rack H1'
            ],
            [
                'name' => 'Rack H2'
            ],
            [
                'name' => 'Rack H3'
            ],
            [
                'name' => 'Rack H4'
            ],
            [
                'name' => 'Rack H5'
            ],
            [
                'name' => 'Rack I1'
            ],
            [
                'name' => 'Rack I2'
            ],
            [
                'name' => 'Rack I3'
            ],
            [
                'name' => 'Rack I4'
            ],
            [
                'name' => 'Rack I5'
            ],
            [
                'name' => 'Rack J1'
            ],
            [
                'name' => 'Rack J2'
            ],
            [
                'name' => 'Rack J3'
            ],
            [
                'name' => 'Rack J4'
            ],
            [
                'name' => 'Rack J5'
            ],
            [
                'name' => 'Rack K1'
            ],
            [
                'name' => 'Rack K2'
            ],
            [
                'name' => 'Rack K3'
            ],
            [
                'name' => 'Rack K4'
            ],
            [
                'name' => 'Rack K5'
            ],
        ];
    }
}
