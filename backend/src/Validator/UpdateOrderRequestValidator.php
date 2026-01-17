<?php


namespace App\Validator;

use Symfony\Component\Validator\Constraints as Assert;


class UpdateOrderRequestValidator
{
    public static function getConstraints(): Assert\Collection
    {
        return new Assert\Collection([
            'product' => new Assert\Required([
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\Collection([
                        'oldProductId' => [
                            new Assert\NotNull(),
                            new Assert\Type('integer'),
                        ],
                        'productName' => [
                            new Assert\NotBlank(),
                            new Assert\Type('string'),
                        ],
                    ])
                ])
            ]),
            'location' => new Assert\Required([
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\Collection([
                        'oldProductId' => [
                            new Assert\NotNull(),
                            new Assert\Type('integer'),
                        ],
                        'locationName' => [
                            new Assert\NotBlank(),
                            new Assert\Type('string'),
                        ],
                    ])
                ])
            ]),
            'order' => new Assert\Required([
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\Collection([
                        'orderId' => [
                            new Assert\NotNull(),
                            new Assert\Type('integer'),
                        ],
                        'isPick' => [
                            new Assert\NotNull(),
                            new Assert\Type('bool'),
                        ],
                        'quantityInOrder' => [
                            new Assert\NotNull(),
                            new Assert\Type('integer'),
                            new Assert\Positive(),
                        ],
                    ])
                ])
            ]),
        ]);
    }
}