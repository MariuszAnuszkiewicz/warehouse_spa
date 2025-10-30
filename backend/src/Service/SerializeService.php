<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class SerializeService
{
    public function __construct(
        protected SerializerInterface $serializer
    ) {}

    public function dataSerialize(array|object $data): string
    {
        $circularRefHandler = fn($data) => $data->getId() ? [$data] : '';

        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => $circularRefHandler
        ];

        return $this->serializer->serialize($data, 'json', $context);
    }
}