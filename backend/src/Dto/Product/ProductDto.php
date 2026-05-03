<?php

namespace App\Dto\Product;

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

    #[Assert\Type('integer')]
    public ?int $quantityInProduct = null;
}