<?php

namespace App\Dto\Order;

use Symfony\Component\Validator\Constraints as Assert;

class OrderDto
{
    #[Assert\NotNull]
    #[Assert\Type('integer')]
    public int $orderId;

    #[Assert\NotNull]
    #[Assert\Type('bool')]
    public bool $isPick;

    #[Assert\NotNull]
    #[Assert\Type('integer')]
    #[Assert\Positive]
    public int $quantityInOrder;

    #[Assert\NotNull]
    #[Assert\Type('string')]
    public string $note;
}