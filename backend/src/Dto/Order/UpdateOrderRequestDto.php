<?php

namespace App\Dto\Order;

use App\Dto\Location\LocationDto;
use App\Dto\Product\ProductDto;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateOrderRequestDto
{
    /**
     * @var ProductDto[]
     */
    #[Assert\NotBlank]
    #[Assert\Type('array')]
    #[Assert\Valid]
    public array $product = [];

    /**
     * @var LocationDto[]
     */
    #[Assert\NotBlank]
    #[Assert\Type('array')]
    #[Assert\Valid]
    public array $location = [];

    /**
     * @var OrderDto[]
     */
    #[Assert\NotBlank]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1, max: 1)]
    #[Assert\Valid]
    public array $order = [];
}