<?php

namespace App\Dto\Order;

use Symfony\Component\Validator\Constraints as Assert;

class CreateOrderItemDto
{
    public function __construct(
        #[Assert\Type('boolean')]
        public ?bool $isPick,

        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public string $name,

        #[Assert\Type('string')]
        public ?string $note,

        #[Assert\NotBlank]
        #[Assert\Type('integer')]
        public int $quantity,

        #[Assert\Type('integer')]
        public int $quantityInStock,
    ) {}
}