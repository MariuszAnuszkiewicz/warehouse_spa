<?php

namespace App\Dto\Order;

use Symfony\Component\Validator\Constraints as Assert;

class ProductDto
{
    #[Assert\NotNull]
    #[Assert\Type('integer')]
    public int $oldProductId;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Length(min: 2, max: 255)]
    public string $productName;
}