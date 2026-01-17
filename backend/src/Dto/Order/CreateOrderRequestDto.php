<?php

namespace App\Dto\Order;

use Symfony\Component\Validator\Constraints as Assert;

class CreateOrderRequestDto
{
    /**
     * @param CreateOrderItemDto[] $items
     */
    public function __construct(
        #[Assert\Count(min: 1)]
        #[Assert\Valid]
        public array $items,
    ) {}
}